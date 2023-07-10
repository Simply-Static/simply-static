<?php

namespace Simply_Static;

class Migrate_Settings {
	/**
	 * Migrate settings
	 *
	 */
	public static function migrate() {
		$options = get_option( 'simply-static' );

		if ( $options['debugging_mode'] == '1' ) {
			$options['debugging_mode'] = true;
		} else {
			$options['debugging_mode'] = false;
		}

		if ( $options['force_replace_url'] == 'on' ) {
			$options['force_replace_url'] = true;
		} else {
			$options['force_replace_url'] = false;
		}

		if ( $options['clear_directory_before_export'] == 'on' ) {
			$options['clear_directory_before_export'] = true;
		} else {
			$options['clear_directory_before_export'] = false;
		}

		if ( $options['http_basic_auth_digest'] ) {
			$decoded = base64_decode( $options['http_basic_auth_digest'] );
			$decoded = explode( ':', $decoded );

			$options['http_basic_auth_username'] = $decoded[0];
			$options['http_basic_auth_password'] = $decoded[1];
		}

		if ( $options['urls_to_exclude'] ) {
			$urls_to_exclude = [];

			foreach ( $options['urls_to_exclude'] as $url => $data ) {
				$urls_to_exclude[] = $url;
			}

			$options['urls_to_exclude'] = $urls_to_exclude;
		}

		// Migrate options to always underscore instead of dash.

		if ( $options['deployment-provider'] ) {
			$options['deployment_provider'] = $options['deployment-provider'];
			unset( $options['deployment-provider'] );
		}

		if ( $options['tiiny-email'] ) {
			$options['tiiny_email'] = $options['tiiny-email'];
			unset( $options['tiiny-email'] );
		}

		if ( $options['tiiny-subdomain'] ) {
			$options['tiiny_subdomain'] = $options['tiiny-subdomain'];
			unset( $options['tiiny-subdomain'] );
		}

		if ( $options['tiiny-domain-suffix'] ) {
			$options['tiiny_domain_suffix'] = $options['tiiny-domain-suffix'];
			unset( $options['tiiny-domain-suffix'] );
		}

		if ( $options['tiiny-password'] ) {
			$options['tiiny_password'] = $options['tiiny-password'];
			unset( $options['tiiny-password'] );
		}

		if ( $options['tiiny-password'] ) {
			$options['tiiny_password'] = $options['tiiny-password'];
			unset( $options['tiiny-password'] );
		}

		if ( $options['cdn-api-key'] ) {
			$options['cdn_api_key'] = $options['cdn-api-key'];
			unset( $options['cdn-api-key'] );
		}

		if ( $options['cdn-storage-host'] ) {
			$options['cdn_api_host'] = $options['cdn-storage-host'];
			unset( $options['cdn-storage-host'] );
		}

		if ( $options['cdn-storage-host'] ) {
			$options['cdn_storage_host'] = $options['cdn-storage-host'];
			unset( $options['cdn-storage-host'] );
		}

		if ( $options['cdn-access-key'] ) {
			$options['cdn_access_key'] = $options['cdn-access-key'];
			unset( $options['cdn-access-key'] );
		}

		if ( $options['cdn-pull-zone'] ) {
			$options['cdn_pull_zone'] = $options['cdn-pull-zone'];
			unset( $options['cdn-pull-zone'] );
		}

		if ( $options['cdn-storage-zone'] ) {
			$options['cdn_storage_zone'] = $options['cdn-storage-zone'];
			unset( $options['cdn-storage-zone'] );
		}

		if ( $options['cdn-directory'] ) {
			$options['cdn_directory'] = $options['cdn-directory'];
			unset( $options['cdn-directory'] );
		}

		if ( $options['cdn-404'] ) {
			$options['cdn_404'] = $options['cdn-404'];
			unset( $options['cdn-404'] );
		}

		if ( $options['github-account-type'] ) {
			$options['github_account_type'] = $options['github-account-type'];
			unset( $options['github-account-type'] );
		}

		if ( $options['github-user'] ) {
			$options['github_user'] = $options['github-user'];
			unset( $options['github-user'] );
		}

		if ( $options['github-email'] ) {
			$options['github_email'] = $options['github-email'];
			unset( $options['github-email'] );
		}

		if ( $options['github-personal-access-token'] ) {
			$options['github_personal_access_token'] = $options['github-personal-access-token'];
			unset( $options['github-personal-access-token'] );
		}

		if ( $options['github-repository'] ) {
			$options['github_repository'] = $options['github-repository'];
			unset( $options['github-repository'] );
		}

		if ( $options['github-existing-repository'] === 'yes' ) {
			$options['github_existing_repository'] = true;
			unset( $options['github-existing-repository'] );
		} else {
			$options['github_existing_repository'] = false;
			unset( $options['github-existing-repository'] );
		}

		if ( $options['github-repository-visibility'] ) {
			$options['github_repository_visibility'] = $options['github-repository-visibility'];
			unset( $options['github-repository-visibility'] );
		}

		if ( $options['github-branch'] ) {
			$options['github_branch'] = $options['github-branch'];
			unset( $options['github-branch'] );
		}

		if ( $options['github-repository-reset'] ) {
			$options['github_repository_reset'] = $options['github-repository-reset'];
			unset( $options['github-repository-reset'] );
		}

		if ( $options['github-webhook-url'] ) {
			$options['github_webhook_url'] = $options['github-webhook-url'];
			unset( $options['github-webhook-url'] );
		}

		if ( $options['fix-cors'] ) {
			$options['fix_cors'] = $options['fix-cors'];
			unset( $options['fix-cors'] );
		}

		if ( $options['static-url'] ) {
			$options['static_url'] = $options['static-url'];
			unset( $options['static-url'] );
		}

		if ( $options['use-forms'] == 'yes' ) {
			$options['use_forms'] = true;
			unset( $options['use-forms'] );
		} else {
			$options['use_forms'] = false;
			unset( $options['use-forms'] );
		}

		if ( $options['use-comments'] == 'yes' ) {
			$options['use_comments'] = true;
			unset( $options['use-comments'] );
		} else {
			$options['use_comments'] = false;
			unset( $options['use-comments'] );
		}

		if ( $options['comment-redirect'] ) {
			$options['comment_redirect'] = $options['comment-redirect'];
			unset( $options['comment-redirect'] );
		}

		if ( $options['use-search'] == 'yes' ) {
			$options['use_search'] = true;
			unset( $options['use-search'] );
		} else {
			$options['use_search'] = false;
			unset( $options['use-search'] );
		}

		if ( $options['search-type'] ) {
			$options['search_type'] = $options['search-type'];
			unset( $options['search-type'] );
		}

		if ( $options['search-index-title'] ) {
			$options['search_index_title'] = $options['search-index-title'];
			unset( $options['search-index-title'] );
		}

		if ( $options['search-index-content'] ) {
			$options['search_index_content'] = $options['search-index-content'];
			unset( $options['search-index-content'] );
		}

		if ( $options['search-index-excerpt'] ) {
			$options['search_index_excerpt'] = $options['search-index-excerpt'];
			unset( $options['search-index-excerpt'] );
		}

		if ( $options['search-excludable'] ) {
			$search_urls_to_exclude = [];

			foreach ( $options['search-excludable'] as $url => $data ) {
				$search_urls_to_exclude[] = $url;
			}

			$options['search-excludable'] = $search_urls_to_exclude;
		}

		if ( $options['algolia-app-id'] ) {
			$options['algolia_app_id'] = $options['algolia-app-id'];
			unset( $options['algolia-app-id'] );
		}

		if ( $options['algolia-admin-api-key'] ) {
			$options['algolia_admin_api_key'] = $options['algolia-admin-api-key'];
			unset( $options['algolia-admin-api-key'] );
		}

		if ( $options['algolia-search-api-key'] ) {
			$options['algolia_search_api_key'] = $options['algolia-search-api-key'];
			unset( $options['algolia-search-api-key'] );
		}

		if ( $options['algolia-index'] ) {
			$options['algolia_index'] = $options['algolia-index'];
			unset( $options['algolia-index'] );
		}

		if ( $options['algolia-selector'] ) {
			$options['algolia_selector'] = $options['algolia-selector'];
			unset( $options['algolia-selector'] );
		}

		update_option('simply-static2', $options);
	}
}
