<?php

namespace Simply_Static;

class Complianz_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'complianz';

	/**
	 * @var null|Url_Extractor
	 */
	protected $extractor = null;

	/**
	 * Can this integration run?
	 *
	 * @return bool
	 */
	public function can_run() {
		return class_exists( 'COMPLIANZ' );
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
	public function run() {
		define( 'SS_COMPLIANZ_URL', plugin_dir_url( __FILE__ ) );
		add_action( 'ss_after_setup_task', [ $this, 'save_cookie_data' ] );
		add_action( 'ss_after_setup_task', [ $this, 'save_policy_consent_html' ] );
		add_action( 'ss_after_setup_task', [ $this, 'save_banner_json' ] );
		add_filter( 'simply_static_content_before_save', [ $this, 'maybe_find_block_or_shortcode' ], 99, 2 );
		add_filter( 'script_loader_src', [ $this, 'change_script' ], 20, 2 );
	}

	/**
	 * Add the banner data to a config URL.
	 * @return void
	 */
	public function save_banner_json() {
		if ( ! function_exists( 'cmplz_rest_api_banner_data' ) ) {
			return;
		}

		$dummy_request = new \WP_REST_Request(); // Need dummy request for the function to work. Request is not used.
		$data          = cmplz_rest_api_banner_data( $dummy_request );

		$this->create_config( 'complianz-banner.json', wp_json_encode( $data ) );
	}

	/**
	 * Code copied from function 'cmplz_rest_api_manage_consent_html' from Complianz.
	 *
	 * @return void
	 */
	public function save_policy_consent_html() {
		if ( ! defined( 'COMPLIANZ' ) ) {
			return;
		}

		$html = '';

		// Copied code START
		$consent_type = apply_filters( 'cmplz_user_consenttype', \COMPLIANZ::$company->get_default_consenttype() );
		$path = trailingslashit( cmplz_path ).'cookiebanner/templates/';
		$banner_html = cmplz_get_template( "cookiebanner.php", array( 'consent_type' => $consent_type ), $path);
		$banner_html = apply_filters("cmplz_banner_html", $banner_html);
		if ( preg_match( '/<!-- categories start -->(.*?)<!-- categories end -->/s', $banner_html,  $matches ) ) {
			$html      = $matches[0];
			$banner_id = apply_filters( 'cmplz_user_banner_id', cmplz_get_default_banner_id() );
			$banner = cmplz_get_cookiebanner(  $banner_id );
			$cookie_settings = $banner->get_html_settings();

			foreach($cookie_settings as $fieldname => $value ) {
				if ( isset($value['text']) ) $value = $value['text'];
				if ( is_array($value) ) continue;
				$html = str_replace( '{'.$fieldname.'}', $value, $html );
			}
		}
		// Copied code END

		$this->create_config( 'complianz-consent.html', $html );

		// In case we receive DNT from browser in JS.
		$do_not_track = cmplz_sprintf( _x( "We have received a privacy signal from your browser. For this reason we have set your privacy settings on this website to strictly necessary. If you want to have full functionality, please consider excluding %s from your privacy settings.",
			"cookie policy", "complianz-gdpr" ), site_url() );

		$this->create_config( 'complianz-do-not-track.html', $do_not_track );

	}

	/**
	 * Try to find Complianz shortcode or block.
	 *
	 * @param string        $content Page content.
	 * @param Url_Extractor $extractor Extractor.
	 *
	 * @return string
	 */
	public function maybe_find_block_or_shortcode( $content, $extractor ) {
		$page    = $extractor->get_static_page();
		$post_id = $page->post_id;

		if ( ! $post_id ) {
			return $content;
		}

		$post = get_post( $post_id );
		$this->create_configs_from_blocks( $post->post_content, $post_id );

		return $content;
	}

	/**
	 * Create config for each block found on the page.
	 *
	 * @param string  $content Page content (not used here, but passed).
	 * @param integer $post_id Page ID (Post ID).
	 *
	 * @return void
	 */
	public function create_configs_from_blocks( $content, $post_id ) {
		if ( has_block('complianz/consent-area', $content ) ) {
			$blocks = parse_blocks( $content );
			foreach ( $blocks as $block ) {
				if ( $block['blockName'] === 'complianz/consent-area' ) {
					$consent_block_id = $block['attrs']['blockId'];
					$output = $block['attrs']['consentedContent'];
					$config_name = sprintf( 'complianz-consent-area-%d-%s.html', $post_id, $consent_block_id );
					$this->create_config( $config_name, $output );
					break;
				}
			}
		} else {
			Util::debug_log('NO COMPLIANZ BLOCK');
			Util::debug_log($content);
		}
	}

	/**
	 * Create Config for any Complianz data.
	 *
	 * @param string $config_name File name with extension.
	 * @param string $content Content to be stored in config.
	 *
	 * @return string
	 */
	public function create_config( $config_name, $content ) {
		$filesystem = Util::get_file_system();

		// Get config file path.
		$upload_dir  = wp_upload_dir();
		$config_dir  = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR;
		$config_file = $config_dir . $config_name;

		// Delete old data.
		if ( file_exists( $config_file ) ) {
			wp_delete_file( $config_file );
		}

		// Check if directory exists.
		if ( ! is_dir( $config_dir ) ) {
			wp_mkdir_p( $config_dir );
		}

		$filesystem->put_contents( $config_file, $content );

		return $config_file;
	}


	/**
	 * Save Cookie Data to a file.
	 *
	 * @return string
	 */
	public function save_cookie_data() {
		$cookie_data = $this->get_cookie_data();

		$config_name = 'complianz-cookie-data.json';

		return $this->create_config( $config_name, $cookie_data );
	}

	/**
	 * Return the cookie data that Complianz sends through REST API.
	 *
	 * @return false|string
	 */
	public function get_cookie_data() {
		$cookie_blocker = \COMPLIANZ::$cookie_blocker;
		$cookie_blocker->load_cookie_data();
		$response = wp_json_encode( $cookie_blocker->cookie_list );
		return $response;
	}

	/**
	 * Change the Complianz script to be loaded from our plugin.
	 * We use a modofied script to work with static sites.
	 *
	 * @param string $src URL to the script.
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	public function change_script( $src, $handle ) {
		if ( 'cmplz-cookiebanner' !== $handle ) {
			return $src;
		}

		return trailingslashit( SS_COMPLIANZ_URL ) . 'complianz/complianz.js';
	}
}