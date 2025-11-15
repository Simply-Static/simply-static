<?php

namespace Simply_Static;

class SS_Adminbar_Integration extends Integration {

	/**
	 * Given plugin handler ID.
	 *
	 * @var string Handler ID.
	 */
	protected $id = 'ss-adminbar';

	protected $active_by_default = true;

	public function __construct() {
		$this->name        = __( 'Admin Bar (Core)', 'simply-static' );
		$this->description = __( 'Adds an admin bar integration for Simply Static to see the current status of static exports.', 'simply-static' );
		$this->requires_ui_reload = true;
	}

	/**
	 * Run the integration.
	 *
	 * @return void
	 */
 public function run() {
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_item' ), 100 );
        add_action( 'wp_ajax_ss_admin_get_status', array( $this, 'get_export_status' ) );

		// Lightweight, inline assets only when admin bar is present.
		add_action( 'admin_footer', array( $this, 'print_admin_bar_inline_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_admin_bar_inline_assets' ) );
	}

	public function add_admin_bar_item( $admin_bar ) {
		// Only show for users allowed by UAM for Admin Bar visibility (adminbar context).
		$cap_adminbar = apply_filters( 'ss_user_capability', 'publish_pages', 'adminbar' );
		if ( ! current_user_can( $cap_adminbar ) ) {
			return;
		}
		// Get settings page.
		$generate_settings = esc_url( get_admin_url() . 'admin.php?page=simply-static-generate' );

		$admin_bar->add_node( [
			'id'    => 'ss-admin-bar',
			'title' => '<span class="ab-icon"></span><span class="ab-label">' . esc_html__( 'Simply Static', 'simply-static' ) . '</span>',
			'href'  => $generate_settings,
			'meta'  => [
				'id'    => 'ss-admin-bar',
				'title' => __( 'Simply Static', 'simply-static' ),
			],
		] );

		// First submenu item: current export progress (Idle/Waiting/Running)
		try {
			$job = Plugin::instance()->get_archive_creation_job();
			$status_prefix = __( 'Status: ', 'simply-static' );
			$status_label = __( 'Idle', 'simply-static' );
			$status_class = 'ss-status-idle';
			if ( method_exists( $job, 'is_running' ) && $job->is_running() ) {
				$status_label = __( 'Running', 'simply-static' );
				$status_class = 'ss-status-running';
			} elseif ( method_exists( $job, 'is_paused' ) && $job->is_paused() ) {
				$status_label = __( 'Waiting', 'simply-static' );
				$status_class = 'ss-status-waiting';
			}
			$admin_bar->add_node( [
				'id'     => 'ss-admin-bar-status',
				'parent' => 'ss-admin-bar',
				'title'  => $status_prefix . $status_label,
				'href'   => $generate_settings,
				'meta'   => [ 'class' => 'ss-status ' . $status_class ],
			] );
		} catch ( \Throwable $e ) {
			// Fail silently if job not available
		}

		// Add submenu items: Diagnostics and Settings
		// Diagnostics (respect UAM diagnostics context)
		$cap_diagnostics = apply_filters( 'ss_user_capability', 'publish_pages', 'diagnostics' );
		if ( current_user_can( $cap_diagnostics ) ) {
			$admin_bar->add_node( [
				'id'     => 'ss-admin-bar-diagnostics',
				'parent' => 'ss-admin-bar',
				'title'  => __( 'Diagnostics', 'simply-static' ),
				'href'   => esc_url( get_admin_url() . 'admin.php?page=simply-static-diagnostics' ),
			] );
		}

		// Settings (not available in Network Admin and requires manage options)
		if ( ! is_network_admin() ) {
			$cap_settings = apply_filters( 'ss_user_capability', 'manage_options', 'settings' );
			if ( current_user_can( $cap_settings ) ) {
				$admin_bar->add_node( [
					'id'     => 'ss-admin-bar-settings',
					'parent' => 'ss-admin-bar',
					'title'  => __( 'Settings', 'simply-static' ),
					'href'   => esc_url( get_admin_url() . 'admin.php?page=simply-static-settings' ),
				] );
			}
		}
	}


	/**
	 * Get information if an export is running.
	 *
	 * @return void
	 */
 public function get_export_status() {
        // Validate nonce.
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'ss-admin-bar-nonce' ) ) {
            wp_die( 'Security check failed' );
        }

		// Permission check aligned with admin bar visibility (generate capability)
		$cap_generate = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );
		if ( ! current_user_can( $cap_generate ) ) {
			wp_send_json_error( [ 'status' => 'forbidden' ], 403 );
		}

		// Default status
		$status = 'error';

		try {
			$job = Plugin::instance()->get_archive_creation_job();
			if ( method_exists( $job, 'is_running' ) && $job->is_running() ) {
				$status = 'running';
			} elseif ( method_exists( $job, 'is_paused' ) && $job->is_paused() ) {
				$status = 'waiting';
			} else {
				$status = 'idle';
			}
			wp_send_json_success( [ 'status' => $status ] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'status' => $status ] );
		}
	}

	/**
	 * Return if the dependency is active.
	 *
	 * @return boolean
	 */
	public function dependency_active() {
		return class_exists( 'Simply_Static\Plugin' );
	}

	/**
	 * Print minimal inline CSS/JS for admin bar status color and on-demand updates.
	 * Only prints when admin bar is showing and user is logged in.
	 *
	 * @return void
	 */
	public function print_admin_bar_inline_assets() {
		if ( ! function_exists( 'is_admin_bar_showing' ) || ! is_admin_bar_showing() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		// Only output assets for users allowed to see the admin bar integration.
		$cap_generate = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );
		if ( ! current_user_can( $cap_generate ) ) {
			return;
		}
		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( 'ss-admin-bar-nonce' );
		$running  = esc_js( __( 'Running', 'simply-static' ) );
		$idle     = esc_js( __( 'Idle', 'simply-static' ) );
		$waiting  = esc_js( __( 'Waiting', 'simply-static' ) );
		$status_prefix = esc_js( __( 'Status: ', 'simply-static' ) );
		?>
		<style id="ss-admin-bar-inline-css">
			#wp-admin-bar-ss-admin-bar-status > .ab-item.ss-status-running { color: #46b450 !important; }
			#wp-admin-bar-ss-admin-bar-status > .ab-item.ss-status-waiting { color: #ffb900 !important; }
			#wp-admin-bar-ss-admin-bar-status > .ab-item.ss-status-idle { color: #a0a5aa !important; }
			/* Simply Static top-level icon */
			#wpadminbar #wp-admin-bar-ss-admin-bar > .ab-item .ab-icon {
				display: inline-block;
				margin-right: 6px;
			}
			#wpadminbar #wp-admin-bar-ss-admin-bar > .ab-item .ab-icon:before {
				content: "";
				background-image: url('<?php echo esc_url( SIMPLY_STATIC_URL . '/assets/simply-static-icon.svg' ); ?>');
				background-repeat: no-repeat;
				background-position: center center;
				background-size: 16px 16px;
				display: inline-block;
				width: 20px;
				height: 20px;
				opacity: 0.9;
				vertical-align: middle;
			}
			/* Ensure label aligns nicely */
			#wpadminbar #wp-admin-bar-ss-admin-bar > .ab-item .ab-label { line-height: 20px; }
		</style>
		<script id="ss-admin-bar-inline-js">
		(function(){
			var loaded=false;
			function updateStatus(label, cls){
				var item = document.querySelector('#wp-admin-bar-ss-admin-bar-status > .ab-item');
				if(!item) return;
				item.textContent = '<?php echo $status_prefix; ?>' + label;
				item.classList.remove('ss-status-running','ss-status-waiting','ss-status-idle');
				item.classList.add('ss-status', cls);
			}
			function fetchStatus(){
				if(loaded) return; loaded=true;
				var xhr = new XMLHttpRequest();
				xhr.open('POST','<?php echo esc_url( $ajax_url ); ?>');
				xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
				xhr.onload = function(){
					try{
						var res = JSON.parse(xhr.responseText);
						var s = res && res.data ? res.data.status : 'idle';
						if(s==='running') updateStatus('<?php echo $running; ?>','ss-status-running');
						else if(s==='waiting') updateStatus('<?php echo $waiting; ?>','ss-status-waiting');
						else updateStatus('<?php echo $idle; ?>','ss-status-idle');
					}catch(e){ /* noop */ }
				};
				xhr.send('action=ss_admin_get_status&security=<?php echo esc_attr( $nonce ); ?>');
			}
			function onOpen(){ fetchStatus(); }
			var root = document.getElementById('wp-admin-bar-ss-admin-bar');
			if(!root) return;
			root.addEventListener('mouseenter', onOpen, { once: true });
			root.addEventListener('focusin', onOpen, { once: true });
			root.addEventListener('click', onOpen, { once: true });
		})();
		</script>
		<?php
	}
}
