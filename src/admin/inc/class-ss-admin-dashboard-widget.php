<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Dashboard Widget for Simply Static.
 *
 * Shows latest export stats and promotes Simply Static Pro / Studio
 * on the free version.
 */
class Admin_Dashboard_Widget {

	/**
	 * Contains instance or null
	 *
	 * @var Admin_Dashboard_Widget|null
	 */
	private static $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return Admin_Dashboard_Widget
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		$capability = apply_filters( 'ss_user_capability', 'publish_pages', 'generate' );

		if ( ! current_user_can( $capability ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'simply_static_dashboard_widget',
			__( 'Simply Static — Latest Export', 'simply-static' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 *
	 * @return void
	 */
	public function render_widget() {
		$options    = Options::instance();
		$start_time = $options->get( 'archive_start_time' );
		$end_time   = $options->get( 'archive_end_time' );

		$this->render_styles();

		if ( empty( $start_time ) && empty( $end_time ) ) {
			$this->render_no_export();
		} else {
			$this->render_export_stats( $options, $start_time, $end_time );
		}

		if ( ! defined( 'SIMPLY_STATIC_PRO_VERSION' ) ) {
			$this->render_upsell();
		}
	}

	/**
	 * Render inline styles for the widget.
	 *
	 * @return void
	 */
	private function render_styles() {
		?>
		<style>
			#simply_static_dashboard_widget .ss-widget-stats {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
				margin-bottom: 16px;
			}
			#simply_static_dashboard_widget .ss-widget-stat {
				background: #f6f7f7;
				border-radius: 4px;
				padding: 12px;
			}
			#simply_static_dashboard_widget .ss-widget-stat .ss-stat-label {
				font-size: 11px;
				text-transform: uppercase;
				color: #646970;
				margin: 0 0 4px;
				letter-spacing: 0.5px;
			}
			#simply_static_dashboard_widget .ss-widget-stat .ss-stat-value {
				font-size: 18px;
				font-weight: 600;
				color: #1d2327;
				margin: 0;
			}
			#simply_static_dashboard_widget .ss-widget-no-export {
				text-align: center;
				padding: 20px 0;
				color: #646970;
			}
			#simply_static_dashboard_widget .ss-widget-no-export .dashicons {
				font-size: 36px;
				width: 36px;
				height: 36px;
				color: #c3c4c7;
				margin-bottom: 8px;
			}
			#simply_static_dashboard_widget .ss-widget-status {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 500;
				margin-bottom: 12px;
			}
			#simply_static_dashboard_widget .ss-widget-status.ss-status-complete {
				background: #d1fadf;
				color: #0a7227;
			}
			#simply_static_dashboard_widget .ss-widget-status.ss-status-running {
				background: #fef0c7;
				color: #93370d;
			}
			#simply_static_dashboard_widget .ss-widget-actions {
				margin-top: 12px;
				text-align: right;
			}
			#simply_static_dashboard_widget .ss-widget-upsell {
				border-top: 1px solid #c3c4c7;
				margin-top: 16px;
				padding-top: 16px;
			}
			#simply_static_dashboard_widget .ss-widget-upsell-cards {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
			}
			#simply_static_dashboard_widget .ss-widget-upsell-card {
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 12px;
				text-decoration: none;
				color: #1d2327;
				display: block;
				transition: border-color 0.2s, box-shadow 0.2s;
			}
			#simply_static_dashboard_widget .ss-widget-upsell-card:hover {
				border-color: #2271b1;
				box-shadow: 0 0 0 1px #2271b1;
			}
			#simply_static_dashboard_widget .ss-widget-upsell-card .ss-upsell-title {
				font-weight: 600;
				font-size: 13px;
				margin: 0 0 4px;
				color: #2271b1;
			}
			#simply_static_dashboard_widget .ss-widget-upsell-card .ss-upsell-desc {
				font-size: 12px;
				color: #646970;
				margin: 0;
			}
		</style>
		<?php
	}

	/**
	 * Render the "no export yet" state.
	 *
	 * @return void
	 */
	private function render_no_export() {
		?>
		<div class="ss-widget-no-export">
			<span class="dashicons dashicons-media-default"></span>
			<p><?php esc_html_e( 'No export has been run yet.', 'simply-static' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=simply-static-generate' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Run Your First Export', 'simply-static' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render export statistics.
	 *
	 * @param Options $options    Options instance.
	 * @param string  $start_time Export start time.
	 * @param string  $end_time   Export end time.
	 *
	 * @return void
	 */
	private function render_export_stats( $options, $start_time, $end_time ) {
		$is_complete = ! empty( $start_time ) && ! empty( $end_time );
		$total_pages = Page::query()->count();

		// Calculate duration.
		$duration_string = '—';
		if ( $is_complete && $start_time && $end_time ) {
			$duration        = strtotime( $end_time ) - strtotime( $start_time );
			$duration_string = gmdate( 'H:i:s', max( 0, $duration ) );
		}

		// Delivery method.
		$delivery_method = $options->get( 'delivery_method' );
		$delivery_label  = $this->get_delivery_label( $delivery_method );

		// Format date.
		$date_display = '—';
		if ( $start_time ) {
			$date_display = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start_time ) );
		}
		?>
		<div>
			<?php if ( $is_complete ) : ?>
				<span class="ss-widget-status ss-status-complete"><?php esc_html_e( 'Completed', 'simply-static' ); ?></span>
			<?php else : ?>
				<span class="ss-widget-status ss-status-running"><?php esc_html_e( 'In Progress', 'simply-static' ); ?></span>
			<?php endif; ?>

			<div class="ss-widget-stats">
				<div class="ss-widget-stat">
					<p class="ss-stat-label"><?php esc_html_e( 'Pages', 'simply-static' ); ?></p>
					<p class="ss-stat-value"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></p>
				</div>
				<div class="ss-widget-stat">
					<p class="ss-stat-label"><?php esc_html_e( 'Duration', 'simply-static' ); ?></p>
					<p class="ss-stat-value"><?php echo esc_html( $duration_string ); ?></p>
				</div>
				<div class="ss-widget-stat">
					<p class="ss-stat-label"><?php esc_html_e( 'Date', 'simply-static' ); ?></p>
					<p class="ss-stat-value" style="font-size:13px;"><?php echo esc_html( $date_display ); ?></p>
				</div>
				<div class="ss-widget-stat">
					<p class="ss-stat-label"><?php esc_html_e( 'Delivery', 'simply-static' ); ?></p>
					<p class="ss-stat-value" style="font-size:13px;"><?php echo esc_html( $delivery_label ); ?></p>
				</div>
			</div>
		</div>
		<div class="ss-widget-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=simply-static-generate' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View Export', 'simply-static' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render upsell cards for Pro and Studio (free version only).
	 *
	 * @return void
	 */
	private function render_upsell() {
		?>
		<div class="ss-widget-upsell">
			<div class="ss-widget-upsell-cards">
 			<a href="<?php echo esc_url( 'https://simplystatic.com/pricing/?utm_source=wordpress&utm_medium=dashboard-widget&utm_campaign=upsell&utm_content=pro' ); ?>" target="_blank" rel="noopener noreferrer" class="ss-widget-upsell-card">
					<p class="ss-upsell-title"><?php esc_html_e( 'Simply Static Pro', 'simply-static' ); ?> →</p>
					<p class="ss-upsell-desc"><?php esc_html_e( 'GitHub & CDN deployment, forms, search, and more.', 'simply-static' ); ?></p>
				</a>
 			<a href="<?php echo esc_url( 'https://simplystatic.com/static-studio/?utm_source=wordpress&utm_medium=dashboard-widget&utm_campaign=upsell&utm_content=studio' ); ?>" target="_blank" rel="noopener noreferrer" class="ss-widget-upsell-card">
 				<p class="ss-upsell-title"><?php esc_html_e( 'Simply Static Studio', 'simply-static' ); ?> →</p>
					<p class="ss-upsell-desc"><?php esc_html_e( 'Managed Static WordPress hosting with one-click deploys.', 'simply-static' ); ?></p>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Get a human-readable label for the delivery method.
	 *
	 * @param string $method Delivery method slug.
	 *
	 * @return string
	 */
	private function get_delivery_label( $method ) {
		$labels = apply_filters( 'ss_dashboard_widget_delivery_labels', array(
			'zip'          => __( 'ZIP Archive', 'simply-static' ),
			'local'        => __( 'Local Directory', 'simply-static' ),
			'github'       => __( 'GitHub', 'simply-static' ),
			'cdn'          => __( 'CDN', 'simply-static' ),
			'bunnycdn'     => __( 'BunnyCDN', 'simply-static' ),
			'tiiny'        => __( 'Tiiny Host', 'simply-static' ),
			'simply-cdn'   => __( 'Simply CDN', 'simply-static' ),
			'aws-s3'       => __( 'Amazon S3', 'simply-static' ),
			'digitalocean' => __( 'DigitalOcean', 'simply-static' ),
		) );

		return isset( $labels[ $method ] ) ? $labels[ $method ] : __( 'Unknown', 'simply-static' );
	}
}
