<?php

namespace Simply_Static;

class Migrate_Settings {
	/**
	 * Migrate settings
	 *
	 */
	public static function migrate() {
		$options = get_option( 'simply-static' );

		if ( isset( $options['debugging_mode'] ) && $options['debugging_mode'] == '1' ) {
			$options['debugging_mode'] = true;
		} else {
			$options['debugging_mode'] = false;
		}

		if ( isset( $options['force_replace_url'] ) && $options['force_replace_url'] == 'on' ) {
			$options['force_replace_url'] = true;
		} else {
			$options['force_replace_url'] = false;
		}

		if ( isset( $options['clear_directory_before_export'] ) && $options['clear_directory_before_export'] == 'on' ) {
			$options['clear_directory_before_export'] = true;
		} else {
			$options['clear_directory_before_export'] = false;
		}

		if ( isset( $options['http_basic_auth_digest'] ) && $options['http_basic_auth_digest'] ) {
			$decoded = base64_decode( $options['http_basic_auth_digest'] );
			$decoded = explode( ':', $decoded );

			$options['http_basic_auth_username'] = $decoded[0];
			$options['http_basic_auth_password'] = $decoded[1];
		}

		if ( isset( $options['urls_to_exclude'] ) ) {
			if ( is_array( $options['urls_to_exclude'] ) ) {
				$urls_to_exclude = [];

				foreach ( $options['urls_to_exclude'] as $url => $data ) {
					$urls_to_exclude[] = $url;
				}

				$options['urls_to_exclude'] = implode( "\n", $urls_to_exclude );
			} else {
				$options['urls_to_exclude'] = '';
			}
		}

		if ( isset( $options['temp_files_dir'] ) ) {
			// Check if directory exists, if not, create it.
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';

			// Check if directory exists.
			if ( ! is_dir( $temp_dir ) ) {
				wp_mkdir_p( $temp_dir );
			}

			$options['temp_files_dir'] = trailingslashit( $temp_dir );
		}

		// Migrate options to always underscore instead of dash.
		if ( isset( $options['deployment-provider'] ) ) {
			unset( $options['deployment-provider'] );
		}

		if ( isset( $options['tiiny-email'] ) ) {
			$options['tiiny_email'] = $options['tiiny-email'];
			unset( $options['tiiny-email'] );
		}

		if ( isset( $options['tiiny-subdomain'] ) ) {
			$options['tiiny_subdomain'] = $options['tiiny-subdomain'];
			unset( $options['tiiny-subdomain'] );
		}

		if ( isset( $options['tiiny-domain-suffix'] ) ) {
			$options['tiiny_domain_suffix'] = $options['tiiny-domain-suffix'];
			unset( $options['tiiny-domain-suffix'] );
		}

		if ( isset( $options['tiiny-password'] ) ) {
			$options['tiiny_password'] = $options['tiiny-password'];
			unset( $options['tiiny-password'] );
		}

		if ( isset( $options['tiiny-password'] ) ) {
			$options['tiiny_password'] = $options['tiiny-password'];
			unset( $options['tiiny-password'] );
		}

		if ( isset( $options['cdn-api-key'] ) ) {
			$options['cdn_api_key'] = $options['cdn-api-key'];
			unset( $options['cdn-api-key'] );
		}

		if ( isset( $options['cdn-storage-host'] ) ) {
			$options['cdn_storage_host'] = $options['cdn-storage-host'];
			unset( $options['cdn-storage-host'] );
		}

		if ( isset( $options['cdn-access-key'] ) ) {
			$options['cdn_access_key'] = $options['cdn-access-key'];
			unset( $options['cdn-access-key'] );
		}

		if ( isset( $options['cdn-pull-zone'] ) ) {
			$options['cdn_pull_zone'] = $options['cdn-pull-zone'];
			unset( $options['cdn-pull-zone'] );
		}

		if ( isset( $options['cdn-storage-zone'] ) ) {
			$options['cdn_storage_zone'] = $options['cdn-storage-zone'];
			unset( $options['cdn-storage-zone'] );
		}

		if ( isset( $options['cdn-directory'] ) ) {
			$options['cdn_directory'] = $options['cdn-directory'];
			unset( $options['cdn-directory'] );
		}

		if ( isset( $options['github-account-type'] ) ) {
			$options['github_account_type'] = $options['github-account-type'];
			unset( $options['github-account-type'] );
		}

		if ( isset( $options['github-user'] ) ) {
			$options['github_user'] = $options['github-user'];
			unset( $options['github-user'] );
		}

		if ( isset( $options['github-email'] ) ) {
			$options['github_email'] = $options['github-email'];
			unset( $options['github-email'] );
		}

		if ( isset( $options['github-personal-access-token'] ) ) {
			$options['github_personal_access_token'] = $options['github-personal-access-token'];
			unset( $options['github-personal-access-token'] );
		}

		if ( isset( $options['github-repository'] ) ) {
			$options['github_repository'] = $options['github-repository'];
			unset( $options['github-repository'] );
		}

		if ( isset( $options['github-existing-repository'] ) && $options['github-existing-repository'] === 'yes' ) {
			$options['github_existing_repository'] = true;
			unset( $options['github-existing-repository'] );
		} else {
			$options['github_existing_repository'] = false;
			unset( $options['github-existing-repository'] );
		}

		if ( isset( $options['github-repository-visibility'] ) ) {
			$options['github_repository_visibility'] = $options['github-repository-visibility'];
			unset( $options['github-repository-visibility'] );
		}

		if ( isset( $options['github-branch'] ) ) {
			$options['github_branch'] = $options['github-branch'];
			unset( $options['github-branch'] );
		}

		if ( isset( $options['github-repository-reset'] ) ) {
			unset( $options['github-repository-reset'] );
		}

		if ( isset( $options['github-webhook-url'] ) ) {
			$options['github_webhook_url'] = $options['github-webhook-url'];
			unset( $options['github-webhook-url'] );
		}

		if ( isset( $options['fix-cors'] ) ) {
			$options['fix_cors'] = $options['fix-cors'];
			unset( $options['fix-cors'] );
		}

		if ( isset( $options['static-url'] ) ) {
			$options['static_url'] = $options['static-url'];
			unset( $options['static-url'] );
		}

		if ( isset( $options['use-forms'] ) && $options['use-forms'] == 'yes' ) {
			$options['use_forms'] = true;
			unset( $options['use-forms'] );
		} else {
			$options['use_forms'] = false;
			unset( $options['use-forms'] );
		}

		if ( isset( $options['use-comments'] ) && $options['use-comments'] == 'yes' ) {
			$options['use_comments'] = true;
			unset( $options['use-comments'] );

			// Modify default WordPress comments.
			$require_registration = get_option( 'comment_registration' );
			$require_name_mail    = get_option( 'require_name_email' );

			if ( 1 == $require_registration ) {
				update_option( 'comment_registration', 0 );
			}

			if ( 0 == $require_name_mail ) {
				update_option( 'comment_registration', 1 );
			}

		} else {
			$options['use_comments'] = false;
			unset( $options['use-comments'] );
		}

		if ( isset( $options['comment-redirect'] ) ) {
			$options['comment_redirect'] = $options['comment-redirect'];
			unset( $options['comment-redirect'] );
		}

		if ( isset( $options['use-search'] ) && $options['use-search'] == 'yes' ) {
			$options['use_search'] = true;
			unset( $options['use-search'] );
		} else {
			$options['use_search'] = false;
			unset( $options['use-search'] );
		}

		if ( isset( $options['search-type'] ) ) {
			$options['search_type'] = $options['search-type'];
			unset( $options['search-type'] );
		}

		if ( isset( $options['search-index-title'] ) ) {
			$options['search_index_title'] = $options['search-index-title'];
			unset( $options['search-index-title'] );
		}

		if ( isset( $options['search-index-content'] ) ) {
			$options['search_index_content'] = $options['search-index-content'];
			unset( $options['search-index-content'] );
		}

		if ( isset( $options['search-index-excerpt'] ) ) {
			$options['search_index_excerpt'] = $options['search-index-excerpt'];
			unset( $options['search-index-excerpt'] );
		}

		if ( isset( $options['search-excludable'] ) ) {
			$search_urls_to_exclude = [];

			foreach ( $options['search-excludable'] as $url => $data ) {
				$search_urls_to_exclude[] = $url;
			}

			$options['search_excludable'] = implode( "\n", $search_urls_to_exclude );
			unset( $options['search-excludable'] );
		}

		if ( isset( $options['algolia-app-id'] ) ) {
			$options['algolia_app_id'] = $options['algolia-app-id'];
			unset( $options['algolia-app-id'] );
		}

		if ( isset( $options['algolia-admin-api-key'] ) ) {
			$options['algolia_admin_api_key'] = $options['algolia-admin-api-key'];
			unset( $options['algolia-admin-api-key'] );
		}

		if ( isset( $options['algolia-search-api-key'] ) ) {
			$options['algolia_search_api_key'] = $options['algolia-search-api-key'];
			unset( $options['algolia-search-api-key'] );
		}

		if ( isset( $options['algolia-index'] ) ) {
			$options['algolia_index'] = $options['algolia-index'];
			unset( $options['algolia-index'] );
		}

		if ( isset( $options['algolia-selector'] ) ) {
			$options['algolia_selector'] = $options['algolia-selector'];
			unset( $options['algolia-selector'] );
		}

		// Set version for upgrade.
		$options['version'] = SIMPLY_STATIC_VERSION;

		update_option( 'simply-static', $options );

		// Table validation.
		Page::create_or_update_table();

		// Clear options cache.
		wp_cache_flush();
	}
}
