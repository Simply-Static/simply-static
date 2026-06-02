<?php

namespace Simply_Static;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transfer the static export to Hetzner Object Storage (or any S3-compatible
 * provider) using the dependency-free S3_Client.
 *
 * Files are uploaded in batches via the canProcessPages trait so that even very
 * large sites stay within PHP execution limits.
 */
class Transfer_Hetzner_Task extends Task {

	use canProcessPages;

	use canTransfer;

	/**
	 * Task name.
	 *
	 * @var string
	 */
	protected static $task_name = 'transfer_hetzner';

	/**
	 * Archive (temp) directory.
	 *
	 * @var string
	 */
	protected $archive_dir = '';

	/**
	 * Object key prefix (subdirectory inside the bucket), without trailing slash.
	 *
	 * @var string
	 */
	protected $subdirectory = '';

	/**
	 * Cached S3 client instance.
	 *
	 * @var S3_Client|null
	 */
	protected $client = null;

	/**
	 * Upload a batch of files to Hetzner Object Storage.
	 *
	 * @return bool|\WP_Error True when finished, false when more batches remain, WP_Error on failure.
	 */
	public function perform() {
		$client = $this->get_client();

		if ( is_wp_error( $client ) ) {
			$this->save_status_message( $client->get_error_message(), 'error' );

			return $client;
		}

		$this->archive_dir  = $this->options->get_archive_dir();
		$this->subdirectory = $this->get_subdirectory();

		$done = $this->process_pages();

		if ( $done ) {
			$this->maybe_send_webhook();

			$destination = $this->options->get( 'hetzner_bucket' );
			$this->save_status_message(
				sprintf(
					/* translators: %s: bucket name */
					__( 'Files transferred to Hetzner bucket: %s', 'simply-static' ),
					$destination
				),
				'hetzner_done'
			);

			do_action( 'ss_finished_transferring_files_to_hetzner', $this->subdirectory, $this );

			self::delete_total_pages();
		}

		return $done;
	}

	/**
	 * Runs once before the upload batches start.
	 *
	 * Used to optionally empty the bucket before a fresh export.
	 *
	 * @return void
	 */
	public function cleanup() {
		if ( $this->options->get( 'hetzner_empty' ) ) {
			$client = $this->get_client();

			if ( ! is_wp_error( $client ) ) {
				$this->save_status_message( __( 'Emptying Hetzner bucket before export…', 'simply-static' ), 'hetzner_empty' );

				$result = $client->delete_all( $this->get_subdirectory_prefix() );

				if ( is_wp_error( $result ) ) {
					Util::debug_log( '[Hetzner] Unable to empty bucket: ' . $result->get_error_message() );
				} else {
					Util::debug_log( '[Hetzner] Emptied bucket. Objects removed: ' . $result );
				}
			}
		}

		self::delete_total_pages();
	}

	/**
	 * Upload a single page/file to the bucket.
	 *
	 * @param Page $static_page Page object.
	 *
	 * @return void
	 * @throws \Exception When the upload fails (so the page is retried).
	 */
	protected function process_page( $static_page ) {
		$file_path        = $this->get_page_file_path( $static_page );
		$origin_file_path = Util::combine_path( $this->archive_dir, $file_path );

		if ( ! file_exists( $origin_file_path ) ) {
			Util::debug_log( '[Hetzner] Cannot find file: ' . $origin_file_path );
			$static_page->set_error_message( 'Unable to find file in archive' );

			return;
		}

		$body = file_get_contents( $origin_file_path );

		if ( false === $body ) {
			throw new \Exception( 'Unable to read file: ' . $file_path );
		}

		$key      = $this->get_object_key( $file_path );
		$response = $this->client->put_object(
			$key,
			$body,
			$this->get_content_type( $origin_file_path ),
			(bool) $this->options->get( 'hetzner_acl_public' )
		);

		if ( is_wp_error( $response ) ) {
			Util::debug_log( '[Hetzner] Upload failed for ' . $key . ': ' . $response->get_error_message() );

			throw new \Exception( $response->get_error_message() );
		}

		$static_page->last_transferred_at = Util::formatted_datetime();
		$static_page->save();

		Util::debug_log( '[Hetzner] Uploaded: ' . $key );

		do_action( 'simply_static_page_file_transferred', $static_page, $this->subdirectory );
	}

	/**
	 * Status message shown while uploading.
	 *
	 * @param int $processed Number of files processed.
	 * @param int $total     Total number of files.
	 *
	 * @return string
	 */
	protected function processed_pages_message( $processed, $total ) {
		if ( ! $total && 'update' === $this->get_generate_type() ) {
			return __( 'No new/updated files to transfer', 'simply-static' );
		}

		return sprintf(
			/* translators: 1: processed files, 2: total files */
			__( 'Transferred %1$d of %2$d files to Hetzner', 'simply-static' ),
			$processed,
			$total
		);
	}

	/**
	 * Build (and cache) the configured S3 client.
	 *
	 * @return S3_Client|\WP_Error
	 */
	protected function get_client() {
		if ( $this->client instanceof S3_Client ) {
			return $this->client;
		}

		$access_key = $this->options->get( 'hetzner_access_key' );
		$secret_key = $this->options->get( 'hetzner_secret_key' );
		$bucket     = $this->options->get( 'hetzner_bucket' );
		$region     = $this->options->get( 'hetzner_region' );
		$endpoint   = $this->get_endpoint( $region );

		$missing = array();

		if ( empty( $access_key ) ) {
			$missing[] = __( 'Access Key', 'simply-static' );
		}

		if ( empty( $secret_key ) ) {
			$missing[] = __( 'Secret Key', 'simply-static' );
		}

		if ( empty( $bucket ) ) {
			$missing[] = __( 'Bucket', 'simply-static' );
		}

		if ( empty( $endpoint ) ) {
			$missing[] = __( 'Endpoint/Region', 'simply-static' );
		}

		if ( ! empty( $missing ) ) {
			return new \WP_Error(
				'hetzner_missing_settings',
				sprintf(
					/* translators: %s: comma-separated list of missing setting names */
					__( 'Hetzner deployment is missing required settings: %s.', 'simply-static' ),
					implode( ', ', $missing )
				)
			);
		}

		$config = apply_filters( 'simply_static_hetzner_client_config', array(
			'access_key' => $access_key,
			'secret_key' => $secret_key,
			'region'     => $region ? $region : 'fsn1',
			'endpoint'   => $endpoint,
			'bucket'     => $bucket,
			'path_style' => (bool) $this->options->get( 'hetzner_path_style' ),
		) );

		$this->client = new S3_Client( $config );

		return $this->client;
	}

	/**
	 * Resolve the endpoint URL.
	 *
	 * Uses an explicit custom endpoint when provided, otherwise derives the
	 * standard Hetzner regional endpoint from the selected region/location.
	 *
	 * @param string $region Region/location code.
	 *
	 * @return string
	 */
	protected function get_endpoint( $region ) {
		$endpoint = trim( (string) $this->options->get( 'hetzner_endpoint' ) );

		if ( '' !== $endpoint ) {
			return $endpoint;
		}

		if ( empty( $region ) ) {
			return '';
		}

		return sprintf( 'https://%s.your-objectstorage.com', $region );
	}

	/**
	 * Get the configured subdirectory, normalised (no leading/trailing slash).
	 *
	 * @return string
	 */
	protected function get_subdirectory() {
		$subdirectory = (string) $this->options->get( 'hetzner_subdirectory' );
		$subdirectory = str_replace( '\\', '/', $subdirectory );

		return trim( $subdirectory, '/' );
	}

	/**
	 * Get the key prefix used when emptying the bucket (with trailing slash).
	 *
	 * @return string
	 */
	protected function get_subdirectory_prefix() {
		$subdirectory = $this->get_subdirectory();

		return '' === $subdirectory ? '' : $subdirectory . '/';
	}

	/**
	 * Build the destination object key for a given relative file path.
	 *
	 * @param string $file_path Relative file path within the archive.
	 *
	 * @return string
	 */
	protected function get_object_key( $file_path ) {
		$file_path = str_replace( '\\', '/', $file_path );
		$file_path = ltrim( $file_path, '/' );

		if ( '' !== $this->subdirectory ) {
			$file_path = $this->subdirectory . '/' . $file_path;
		}

		return $file_path;
	}

	/**
	 * Best-effort MIME type detection for a file.
	 *
	 * @param string $file_path Absolute path to the file.
	 *
	 * @return string
	 */
	protected function get_content_type( $file_path ) {
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		$map = array(
			'html'  => 'text/html',
			'htm'   => 'text/html',
			'css'   => 'text/css',
			'js'    => 'application/javascript',
			'mjs'   => 'application/javascript',
			'json'  => 'application/json',
			'xml'   => 'application/xml',
			'rss'   => 'application/rss+xml',
			'txt'   => 'text/plain',
			'svg'   => 'image/svg+xml',
			'png'   => 'image/png',
			'jpg'   => 'image/jpeg',
			'jpeg'  => 'image/jpeg',
			'gif'   => 'image/gif',
			'webp'  => 'image/webp',
			'avif'  => 'image/avif',
			'ico'   => 'image/x-icon',
			'woff'  => 'font/woff',
			'woff2' => 'font/woff2',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'eot'   => 'application/vnd.ms-fontobject',
			'pdf'   => 'application/pdf',
			'mp4'   => 'video/mp4',
			'webm'  => 'video/webm',
			'mp3'   => 'audio/mpeg',
			'zip'   => 'application/zip',
			'wasm'  => 'application/wasm',
			'map'   => 'application/json',
		);

		if ( isset( $map[ $extension ] ) ) {
			return $map[ $extension ];
		}

		// Fall back to WordPress' own detection for anything else.
		$checked = wp_check_filetype( basename( $file_path ) );

		if ( ! empty( $checked['type'] ) ) {
			return $checked['type'];
		}

		return 'application/octet-stream';
	}

	/**
	 * Fire the optional completion webhook.
	 *
	 * @return void
	 */
	protected function maybe_send_webhook() {
		$webhook_url = $this->options->get( 'hetzner_webhook_url' );

		if ( empty( $webhook_url ) ) {
			return;
		}

		Util::debug_log( '[Hetzner] Sending completion webhook to ' . $webhook_url );

		wp_remote_post( $webhook_url, array(
			'timeout'  => 15,
			'blocking' => false,
			'body'     => array(
				'status' => 'success',
				'bucket' => $this->options->get( 'hetzner_bucket' ),
				'time'   => Util::formatted_datetime(),
			),
		) );
	}
}
