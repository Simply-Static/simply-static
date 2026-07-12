<?php

namespace Simply_Static;

/**
 * Enforce compatibility between Simply Static and Simply Static Pro.
 *
 * The guard runs before either plugin's normal plugins_loaded callback. An
 * incompatible Pro version remains active so it can recover automatically
 * after an update, but none of its Core integration hooks run in the current
 * request.
 */
final class Pro_Compatibility {

	/** Core release that introduced the current page-processing contract. */
	const CURRENT_CORE_VERSION = '3.8.0';

	/** Minimum Pro release compatible with the current processing contract. */
	const CURRENT_PRO_VERSION = '2.5.0';

	/** Historical Core compatibility boundary. */
	const LEGACY_CORE_VERSION = '3.5.2';

	/** Historical minimum Pro version. */
	const LEGACY_PRO_VERSION = '2.0.1';

	/**
	 * Return the minimum Pro version required by a Core release.
	 *
	 * @param string $core_version Simply Static version.
	 * @return string
	 */
	public static function required_pro_version( $core_version ) {
		return version_compare( (string) $core_version, self::CURRENT_CORE_VERSION, '>=' )
			? self::CURRENT_PRO_VERSION
			: self::LEGACY_PRO_VERSION;
	}

	/**
	 * Stop an incompatible active Pro version from booting in this request.
	 *
	 * Optional version arguments are test seams; WordPress invokes this method
	 * without arguments and the installed plugin constants are used.
	 *
	 * @param string|null $core_version Core version override.
	 * @param string|null $pro_version  Pro version override.
	 * @return bool True when Pro may boot or is inactive; false when blocked.
	 */
	public static function enforce( $core_version = null, $pro_version = null ) {
		$core_version = null === $core_version && defined( 'SIMPLY_STATIC_VERSION' )
			? (string) SIMPLY_STATIC_VERSION
			: (string) $core_version;

		if ( '' === $core_version || version_compare( $core_version, self::LEGACY_CORE_VERSION, '<' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pro_basename  = 'simply-static-pro/simply-static-pro.php';
		$pro_active    = function_exists( 'is_plugin_active' ) && is_plugin_active( $pro_basename );
		$pro_netactive = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $pro_basename );

		if ( ! $pro_active && ! $pro_netactive ) {
			return true;
		}

		if ( null === $pro_version ) {
			$pro_version = defined( 'SIMPLY_STATIC_PRO_VERSION' ) ? (string) SIMPLY_STATIC_PRO_VERSION : '';
		}

		$required_pro_version = self::required_pro_version( $core_version );
		if ( '' !== $pro_version && version_compare( $pro_version, $required_pro_version, '>=' ) ) {
			return true;
		}

		// Pro registers these callbacks at the default priority while its main
		// file is loaded. Remove them before plugins_loaded reaches priority 10.
		remove_action( 'plugins_loaded', 'ssp_run_plugin', 10 );
		remove_filter( 'simply_static_crawlers', 'ssp_register_crawler', 10 );
		remove_action( 'rest_api_init', 'ssp_rest_api_init', 10 );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'network_admin_notices', array( __CLASS__, 'network_admin_notice' ) );

		return false;
	}

	/** Display the site-admin compatibility notice. */
	public static function admin_notice() {
		self::render_notice( false );
	}

	/** Display the network-admin compatibility notice. */
	public static function network_admin_notice() {
		self::render_notice( true );
	}

	/**
	 * Render a safely escaped compatibility notice.
	 *
	 * @param bool $network_admin Whether this is the network dashboard.
	 * @return void
	 */
	private static function render_notice( $network_admin ) {
		$required_version = self::required_pro_version(
			defined( 'SIMPLY_STATIC_VERSION' ) ? (string) SIMPLY_STATIC_VERSION : self::CURRENT_CORE_VERSION
		);

		$message = $network_admin
			? __( 'Simply Static Pro did not load network-wide because this version of Simply Static requires Simply Static Pro %1$s or newer. Please update Simply Static Pro to continue using Pro features.', 'simply-static' )
			: __( 'Simply Static Pro did not load because this version of Simply Static requires Simply Static Pro %1$s or newer. Please update Simply Static Pro to continue using Pro features.', 'simply-static' );

		echo '<div class="notice notice-error"><p>'
			. esc_html( sprintf( $message, $required_version ) )
			. '</p></div>';
	}
}
