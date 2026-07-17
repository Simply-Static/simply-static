<?php

namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static utility class
 */
class Util {

	/**
	 * Option keys that contain credentials or site-specific deployment secrets.
	 *
	 * @return string[]
	 */
	public static function get_sensitive_option_keys() {
		$keys = array(
			'encryption_key',
			'http_basic_auth_username',
			'http_basic_auth_password',
			'http_basic_auth_digest',
			'tiiny_email',
			'tiiny_subdomain',
			'tiiny_password',
			'cdn_api_key',
			'cdn_access_key',
			'cdn_pull_zone',
			'cdn_storage_zone',
			'github_user',
			'github_email',
			'github_personal_access_token',
			'github_repository',
			'github_webhook_url',
			'aws_access_key',
			'aws_access_secret',
			'aws_bucket',
			'aws_distribution_id',
			'aws_webhook_url',
			's3_access_key',
			's3_access_secret',
			's3_bucket',
			's3_base_url',
			'algolia_app_id',
			'algolia_admin_api_key',
			'algolia_search_api_key',
			'algolia_index',
			'sftp_host',
			'sftp_user',
			'sftp_pass',
			'sftp_private_key',
			'sftp_folder',
			'shortpixel_api_key',
			'cloudflare_turnstile_secret_key',
			'recaptcha_secret_key',
			'ss_single_export_webhook_url',
			'ss_webhook_url',
		);

		$filtered = apply_filters( 'ss_sensitive_option_keys', $keys );
		if ( is_array( $filtered ) ) {
			$keys = array_merge( $keys, $filtered );
		}

		$keys = array_map(
			static function ( $key ) {
				return sanitize_key( str_replace( '-', '_', (string) $key ) );
			},
			$keys
		);

		return array_values( array_unique( array_filter( $keys ) ) );
	}

	/**
	 * Determine whether an option key is likely to contain a secret.
	 *
	 * The pattern is a defense-in-depth fallback for Pro and future integrations;
	 * the explicit list remains the source of truth for non-obvious identifiers.
	 *
	 * @param string $key Option key.
	 *
	 * @return bool
	 */
	public static function is_sensitive_option_key( $key ) {
		$key = sanitize_key( str_replace( '-', '_', (string) $key ) );

		if ( in_array( $key, self::get_sensitive_option_keys(), true ) ) {
			return true;
		}

		return (bool) preg_match(
			'/(?:^|_)(?:password|passwd|passphrase|secret|private_key|access_key|api_key|token|auth_token|access_token|personal_access_token|encryption_key|license_key|credentials?|webhook_url)(?:$|_)/i',
			$key
		);
	}

	/**
	 * Remove credential-bearing values from an options array.
	 *
	 * @param array $options Options to redact.
	 *
	 * @return array
	 */
	public static function remove_sensitive_options( $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		foreach ( array_keys( $options ) as $key ) {
			if ( self::is_sensitive_option_key( $key ) ) {
				unset( $options[ $key ] );
			} elseif ( is_array( $options[ $key ] ) ) {
				$options[ $key ] = self::remove_sensitive_options( $options[ $key ] );
			}
		}

		return $options;
	}

	/**
	 * Restore credential-bearing values from the destination installation.
	 *
	 * Portable settings exports intentionally omit secrets. Importing one of
	 * those exports must therefore merge the destination's existing secrets
	 * back into the payload instead of replacing them with missing values. The
	 * merge is recursive so credentials stored by Pro or future integrations in
	 * nested arrays receive the same protection.
	 *
	 * @param array $incoming Imported, already-untrusted options.
	 * @param array $current  Existing destination options.
	 *
	 * @return array
	 */
	public static function preserve_sensitive_options( $incoming, $current ) {
		$incoming = is_array( $incoming ) ? $incoming : array();
		$current  = is_array( $current ) ? $current : array();

		foreach ( $current as $key => $value ) {
			if ( self::is_sensitive_option_key( $key ) ) {
				$incoming[ $key ] = $value;
				continue;
			}

			if ( ! is_array( $value ) ) {
				continue;
			}

			$incoming_value = isset( $incoming[ $key ] ) && is_array( $incoming[ $key ] )
				? $incoming[ $key ]
				: array();
			$merged         = self::preserve_sensitive_options( $incoming_value, $value );

			// Do not recreate unrelated non-sensitive containers that the import
			// deliberately omitted. A changed result means a nested secret exists.
			if ( array_key_exists( $key, $incoming ) || $merged !== $incoming_value ) {
				$incoming[ $key ] = $merged;
			}
		}

		return $incoming;
	}

	/**
	 * Return a list of plugin directory slugs that must always be present in the
	 * Enhanced Crawl "Plugins to Include" list. These cannot be removed by users.
	 *
	 * Filter: `ss_required_plugins` allows adding slugs (removing built-ins is not possible).
	 *
	 * @return string[] Array of sanitized, unique plugin directory slugs.
	 */
	public static function get_required_plugins(): array {
		$defaults = array(
			'simply-static-pro',
		);

		/**
		 * Filter the list of required plugin slugs that must always be included in Enhanced Crawl.
		 *
		 * @param string[] $defaults Directory slugs of required plugins.
		 */
		$list = apply_filters( 'ss_required_plugins', $defaults );

		if ( ! is_array( $list ) ) {
			$list = $defaults;
		}

		// Ensure built-in required plugins cannot be removed via the filter.
		$list = array_merge( $defaults, $list );

		$list = array_map( 'sanitize_title', array_filter( array_map( 'strval', $list ) ) );

		return array_values( array_unique( $list ) );
	}

	/**
	 * Ensure that all required plugins are present in a given list of plugin slugs.
	 * Only adds required plugins that are currently active.
	 *
	 * @param string[] $slugs Current list of plugin slugs.
	 *
	 * @return string[] Updated list with required plugins guaranteed.
	 */
	public static function ensure_required_plugins( array $slugs ): array {
		$required = self::get_required_plugins();
		$active   = self::get_all_active_plugins();

		$active_slugs = array();
		foreach ( $active as $plugin_path ) {
			$s = self::plugin_slug_from_path( $plugin_path );
			if ( '' !== $s ) {
				$active_slugs[] = strtolower( $s );
			}
		}

		$slugs_lc = array_map( 'strtolower', $slugs );

		foreach ( $required as $req ) {
			$req_lc = strtolower( $req );
			if ( in_array( $req_lc, $active_slugs, true ) && ! in_array( $req_lc, $slugs_lc, true ) ) {
				$slugs[]   = $req;
				$slugs_lc[] = $req_lc;
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Return a filterable list of admin-only plugin directory slugs to always exclude
	 * from Enhanced Crawl "Plugins to Include" selections and auto-include logic.
	 *
	 * This centralizes the defaults and mirrors previous lists used in Admin REST and Plugin.
	 *
	 * Filter: `ss_admin_only_plugins` allows adding/removing slugs.
	 *
	 * @return string[] Array of sanitized, unique plugin directory slugs.
	 */
	public static function get_admin_only_plugins(): array {
		$defaults = array(
			'advanced-custom-fields',
			'secure-custom-fields',
			'query-monitor',
			'debug-bar',
			'health-check',
			'user-switching',
			'wp-crontrol',
			'theme-check',
			'regenerate-thumbnails',
			'wp-migrate-db',
			'wp-migrate-db-pro',
			'wp-staging',
			'wp-staging-pro',
			'rollback',
			'wp-rollback',
			'classic-editor',
			'artiss-transient-cleaner',
			'updraftplus',
			'user-switchting',
			'view-admin-as',
			'wp-beta-tester',
			'wp-downgrade',
			'wp-rest-cache',
			'wp-reset',
			'wpvidid-backuprestore',
			'duplicate-post',
		);

		/**
		 * Filter the list of admin-only plugin slugs that should be excluded from Enhanced Crawl.
		 *
		 * @param string[] $defaults Directory slugs of admin-only plugins.
		 */
		$list = apply_filters( 'ss_admin_only_plugins', $defaults );

		if ( ! is_array( $list ) ) {
			$list = $defaults;
		}
		// Sanitize and de-duplicate.
		$list = array_map( 'sanitize_title', array_filter( array_map( 'strval', $list ) ) );

		return array_values( array_unique( $list ) );
	}

	/**
	 * Derive a plugin directory slug from a plugin path like akismet/akismet.php.
	 * Returns an empty string if it cannot be derived.
	 *
	 * @param string $plugin_path
	 *
	 * @return string
	 */
	public static function plugin_slug_from_path( string $plugin_path ): string {
		$dir = trim( dirname( (string) $plugin_path ), '/' );
		if ( $dir === '' || $dir === '.' ) {
			return '';
		}

		return sanitize_title( $dir );
	}

	/**
	 * Append a plugin slug to Enhanced Crawl's "Plugins to Include" list and persist.
	 * De-duplicates and sanitizes slugs.
	 *
	 * @param string $slug Plugin directory slug
	 *
	 * @return void
	 */
	public static function add_plugin_to_enhanced_crawl( string $slug ): void {
		$slug    = sanitize_title( $slug );
		$options = get_option( 'simply-static' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$current = array();
		if ( isset( $options['plugins_to_include'] ) && is_array( $options['plugins_to_include'] ) ) {
			$current = array_map( 'strval', $options['plugins_to_include'] );
		}
		if ( $slug !== '' ) {
			$current[] = $slug;
		}
		$options['plugins_to_include'] = array_values( array_unique( array_map( 'sanitize_title', $current ) ) );
		update_option( 'simply-static', $options );
	}

	/**
	 * Remove a plugin slug from Enhanced Crawl's "Plugins to Include" list and persist.
	 *
	 * @param string $slug Plugin directory slug
	 *
	 * @return void
	 */
	public static function remove_plugin_from_enhanced_crawl( string $slug ): void {
		// Never allow removal of required plugins.
		$required    = self::get_required_plugins();
		$required_lc = array_map( 'strtolower', $required );
		if ( in_array( strtolower( sanitize_title( $slug ) ), $required_lc, true ) ) {
			return;
		}

		$options = get_option( 'simply-static' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$current = array();
		if ( isset( $options['plugins_to_include'] ) && is_array( $options['plugins_to_include'] ) ) {
			$current = array_map( 'strval', $options['plugins_to_include'] );
		}
		$slug     = sanitize_title( (string) $slug );
		$filtered = array();
		foreach ( $current as $item ) {
			if ( sanitize_title( $item ) !== $slug ) {
				$filtered[] = $item;
			}
		}
		$options['plugins_to_include'] = array_values( array_unique( array_map( 'sanitize_title', $filtered ) ) );
		update_option( 'simply-static', $options );
	}

	/**
	 * Return the active theme slugs: child (stylesheet) and parent (template) if different.
	 *
	 * @return string[]
	 */
	public static function active_theme_slugs(): array {
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		if ( ! $theme || ! $theme->exists() ) {
			return array();
		}
		$slugs      = array();
		$stylesheet = $theme->get_stylesheet();
		if ( ! empty( $stylesheet ) ) {
			$slugs[] = sanitize_title( $stylesheet );
		}
		$template = $theme->get_template();
		if ( ! empty( $template ) ) {
			$slugs[] = sanitize_title( $template );
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Append one or more theme slugs to Enhanced Crawl's "Themes to Include" list and persist.
	 *
	 * @param string[] $slugs
	 *
	 * @return void
	 */
	public static function add_themes_to_enhanced_crawl( array $slugs ): void {
		$options = get_option( 'simply-static' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$current = array();
		if ( isset( $options['themes_to_include'] ) && is_array( $options['themes_to_include'] ) ) {
			$current = array_map( 'strval', $options['themes_to_include'] );
		}
		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( $slug !== '' ) {
				$current[] = $slug;
			}
		}
		$options['themes_to_include'] = array_values( array_unique( array_map( 'sanitize_title', $current ) ) );
		update_option( 'simply-static', $options );
	}

	/**
	 * Automatically remove a deactivated plugin from Enhanced Crawl's "Plugins to Include" list.
	 *
	 * Hook callback for `deactivated_plugin`.
	 *
	 * Filters used:
	 * - `ss_auto_remove_on_deactivation` (bool) Gate entire behavior (default true).
	 *
	 * @param string $plugin Relative plugin path like akismet/akismet.php
	 * @param bool $network_deactivating True if network-deactivated on multisite
	 *
	 * @return void
	 */
	public static function maybe_auto_remove_deactivated_plugin( $plugin, $network_deactivating ): void {
		$enabled = apply_filters( 'ss_auto_remove_on_deactivation', true );
		if ( ! $enabled ) {
			return;
		}

		$slug = self::plugin_slug_from_path( (string) $plugin );
		if ( '' === $slug ) {
			return;
		}

		if ( is_multisite() && $network_deactivating ) {
			$sites = function_exists( 'get_sites' ) ? get_sites( array( 'fields' => 'ids' ) ) : array();
			if ( is_array( $sites ) ) {
				foreach ( $sites as $blog_id ) {
					switch_to_blog( (int) $blog_id );
					self::remove_plugin_from_enhanced_crawl( $slug );
					restore_current_blog();
				}
			}

			return;
		}

		self::remove_plugin_from_enhanced_crawl( $slug );
	}

	/**
	 * Include the currently active theme (and parent, if child theme is used) into
	 * Enhanced Crawl's "Themes to Include" list whenever the theme is switched.
	 *
	 * Hook callback for `after_switch_theme`.
	 *
	 * Filters used:
	 * - `ss_auto_include_themes_on_switch` (bool) Control if this runs (default true).
	 * - `ss_auto_include_skip_themes` (string[]) Theme slugs to skip.
	 *
	 * @param mixed ...$args Ignored. Present for compatibility with WP action parameters.
	 *
	 * @return void
	 */
	public static function maybe_auto_include_active_theme( ...$args ): void {
		$enabled = apply_filters( 'ss_auto_include_themes_on_switch', true );
		if ( ! $enabled ) {
			return;
		}
		$slugs = self::active_theme_slugs();

		$skip = apply_filters( 'ss_auto_include_skip_themes', array() );
		if ( ! is_array( $skip ) ) {
			$skip = array();
		}
		$skip  = array_map( 'sanitize_title', array_filter( array_map( 'strval', $skip ) ) );
		$slugs = array_values( array_diff( $slugs, $skip ) );
		if ( empty( $slugs ) ) {
			return;
		}

		self::add_themes_to_enhanced_crawl( $slugs );
	}

	/**
	 * Automatically add an activated plugin to Enhanced Crawl's "Plugins to Include" list.
	 *
	 * Hook callback for `activated_plugin`. Kept here to avoid bloating Plugin class.
	 *
	 * Filters used:
	 * - `ss_auto_include_on_activation` (bool) Gate entire behavior (default true).
	 * - `ss_auto_include_skip_plugins` (string[]) Additional plugin slugs to skip.
	 * - `ss_admin_only_plugins` (string[]) Centralized admin-only list.
	 * - `ss_auto_include_self_slugs` (string[]) Defaults to ['simply-static','simply-static-pro'].
	 * - `ss_auto_include_debug` (bool) Enable error_log debug lines.
	 *
	 * @param string $plugin Relative plugin path like akismet/akismet.php
	 * @param bool $network_wide True if network-activated on multisite
	 *
	 * @return void
	 */
	public static function maybe_auto_include_activated_plugin( $plugin, $network_wide ): void {
		// Allow disabling globally.
		$enabled = apply_filters( 'ss_auto_include_on_activation', true );
		if ( ! $enabled ) {
			return;
		}

		// Derive the plugin directory slug.
		$slug = self::plugin_slug_from_path( (string) $plugin );
		if ( '' === $slug ) {
			return;
		}
		$slug_lc = strtolower( $slug );

		// Exclusion lists: admin-only, custom, and self slugs.
		$admin_only_list = self::get_admin_only_plugins();
		$admin_only_lc   = array_map( 'strtolower', array_map( 'strval', (array) $admin_only_list ) );

		$custom_skips = apply_filters( 'ss_auto_include_skip_plugins', array() );
		if ( ! is_array( $custom_skips ) ) {
			$custom_skips = array();
		}
		$custom_skips_lc = array_map( 'strtolower', array_map( 'sanitize_title', array_filter( array_map( 'strval', $custom_skips ) ) ) );

		$self_defaults = array( 'simply-static', 'simply-static-pro' );
		$self_slugs    = apply_filters( 'ss_auto_include_self_slugs', $self_defaults );
		if ( ! is_array( $self_slugs ) ) {
			$self_slugs = $self_defaults;
		}
		$self_slugs_lc = array_map( 'strtolower', array_map( 'sanitize_title', array_filter( array_map( 'strval', $self_slugs ) ) ) );

		$skip_lc = array_values( array_unique( array_merge( $admin_only_lc, $custom_skips_lc, $self_slugs_lc ) ) );
		if ( in_array( $slug_lc, $skip_lc, true ) ) {
			if ( apply_filters( 'ss_auto_include_debug', false ) ) {
				error_log( sprintf( '[Simply Static] Auto-include skipped for plugin "%s" (in skip list).', $slug ) );
			}

			return;
		}

		if ( is_multisite() && $network_wide ) {
			$sites = function_exists( 'get_sites' ) ? get_sites( array( 'fields' => 'ids' ) ) : array();
			if ( is_array( $sites ) ) {
				foreach ( $sites as $blog_id ) {
					switch_to_blog( (int) $blog_id );
					self::add_plugin_to_enhanced_crawl( $slug );
					restore_current_blog();
				}
			}
			if ( apply_filters( 'ss_auto_include_debug', false ) ) {
				error_log( sprintf( '[Simply Static] Auto-included plugin "%s" for %d site(s) via network activation.', $slug, is_array( $sites ) ? count( $sites ) : 0 ) );
			}

			return;
		}

		// Single site or per-site activation.
		self::add_plugin_to_enhanced_crawl( $slug );
		if ( apply_filters( 'ss_auto_include_debug', false ) ) {
			error_log( sprintf( '[Simply Static] Auto-included plugin "%s" on this site.', $slug ) );
		}
	}

	/**
	 * Parse a list of user-provided lines into literals and regex patterns.
	 * Lines wrapped like /pattern/flags are treated as regex; others as literals.
	 *
	 * @param array $lines
	 *
	 * @return array{literals: string[], regex: string[]}
	 */
	public static function parse_patterns( array $lines ): array {
		$literals = [];
		$regex    = [];
		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( $line === '' ) {
				continue;
			}
			// Regex if starts and ends with /, allow optional flags like i,m,s,u
			if ( strlen( $line ) >= 2 && $line[0] === '/' && strrpos( $line, '/' ) !== 0 ) {
				$last    = strrpos( $line, '/' );
				$pattern = substr( $line, 0, $last + 1 );
				$flags   = substr( $line, $last + 1 );
				// Validate pattern
				$valid = @preg_match( $pattern . $flags, '' );
				if ( $valid !== false ) {
					$regex[] = $pattern . $flags;
					continue;
				}
			}
			$literals[] = $line;
		}

		return [
			'literals' => array_values( array_unique( $literals ) ),
			'regex'    => array_values( array_unique( $regex ) )
		];
	}

	/**
	 * Build a candidate URL list used when resolving regex in Additional URLs.
	 * This is a best-effort, bounded set of the home/front page, public posts,
	 * term links, and author links.
	 *
	 * @return string[]
	 */
	public static function candidate_urls_for_regex(): array {
		$limit = max( 0, (int) apply_filters( 'ss_regex_candidate_url_limit', 5000 ) );

		if ( 0 === $limit ) {
			return array();
		}

		$batch_size = (int) apply_filters( 'ss_regex_candidate_query_batch_size', 250, $limit );
		$batch_size = min( 1000, $limit, max( 1, $batch_size ) );
		$urls       = array();
		$seen       = array();
		$add_url    = static function ( $url ) use ( &$urls, &$seen, $limit ) {
			if ( count( $urls ) >= $limit || ! is_string( $url ) || '' === $url || isset( $seen[ $url ] ) ) {
				return;
			}

			$seen[ $url ] = true;
			$urls[]       = $url;
		};

		$add_url( home_url( '/' ) );

		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_id = (int) get_option( 'page_on_front' );
			if ( $front_id ) {
				$add_url( get_permalink( $front_id ) );
			}
		}

		if ( count( $urls ) >= $limit ) {
			return $urls;
		}

		// Posts and pages of public types
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		unset( $post_types['attachment'] );
		$post_types = apply_filters( 'simply_static_post_types_to_crawl', $post_types );

		if ( is_array( $post_types ) && ! empty( $post_types ) ) {
			// Each provider may examine at most the remaining result capacity. This
			// retains the posts -> terms -> authors fallback without allowing invalid
			// or duplicate rows to trigger a full-table scan.
			$post_offset      = 0;
			$post_scan_budget = $limit - count( $urls );

			while ( count( $urls ) < $limit && $post_scan_budget > 0 ) {
				$query_limit = min( $batch_size, $limit - count( $urls ), $post_scan_budget );
				$ids         = get_posts(
					array(
						'post_type'              => $post_types,
						'post_status'            => 'publish',
						'posts_per_page'         => $query_limit,
						'offset'                 => $post_offset,
						'orderby'                => 'ID',
						'order'                  => 'ASC',
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'ignore_sticky_posts'    => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);

				if ( is_wp_error( $ids ) || ! is_array( $ids ) || empty( $ids ) ) {
					break;
				}

				$ids           = array_slice( array_values( $ids ), 0, $query_limit );
				$fetched_count = count( $ids );
				$post_offset      += $fetched_count;
				$post_scan_budget -= $fetched_count;

				if ( function_exists( '_prime_post_caches' ) ) {
					_prime_post_caches( $ids, false, false );
				}

				foreach ( $ids as $post_id ) {
					$add_url( get_permalink( $post_id ) );
				}

				if ( $fetched_count < $query_limit ) {
					break;
				}
			}
		}

		if ( count( $urls ) < $limit ) {
			// Terms
			$taxonomies      = get_taxonomies( [ 'public' => true ], 'names' );
			$term_scan_budget = $limit - count( $urls );

			foreach ( (array) $taxonomies as $taxonomy ) {
				$term_offset = 0;

				while ( count( $urls ) < $limit && $term_scan_budget > 0 ) {
					$query_limit = min( $batch_size, $limit - count( $urls ), $term_scan_budget );
					$terms       = get_terms(
						array(
							'taxonomy'               => $taxonomy,
							'hide_empty'             => true,
							'number'                 => $query_limit,
							'offset'                 => $term_offset,
							'orderby'                => 'term_id',
							'order'                  => 'ASC',
							'update_term_meta_cache' => false,
						)
					);

					if ( is_wp_error( $terms ) || ! is_array( $terms ) || empty( $terms ) ) {
						break;
					}

					$terms         = array_slice( array_values( $terms ), 0, $query_limit );
					$fetched_count = count( $terms );
					$term_offset      += $fetched_count;
					$term_scan_budget -= $fetched_count;

					foreach ( $terms as $term ) {
						$term_url = get_term_link( $term );

						if ( ! is_wp_error( $term_url ) ) {
							$add_url( $term_url );
						}
					}

					if ( $fetched_count < $query_limit ) {
						break;
					}
				}

				if ( count( $urls ) >= $limit || $term_scan_budget <= 0 ) {
					break;
				}
			}
		}

		if ( count( $urls ) < $limit ) {
			// Authors
			$user_offset      = 0;
			$user_scan_budget = $limit - count( $urls );

			while ( count( $urls ) < $limit && $user_scan_budget > 0 ) {
				$query_limit = min( $batch_size, $limit - count( $urls ), $user_scan_budget );
				$users       = get_users(
					array(
						'who'         => 'authors',
						'number'      => $query_limit,
						'offset'      => $user_offset,
						'orderby'     => 'ID',
						'order'       => 'ASC',
						'fields'      => array( 'ID' ),
						'count_total' => false,
					)
				);

				if ( is_wp_error( $users ) || ! is_array( $users ) || empty( $users ) ) {
					break;
				}

				$users         = array_slice( array_values( $users ), 0, $query_limit );
				$fetched_count = count( $users );
				$user_offset      += $fetched_count;
				$user_scan_budget -= $fetched_count;

				foreach ( $users as $user ) {
					$user_id = 0;

					if ( is_object( $user ) && isset( $user->ID ) ) {
						$user_id = (int) $user->ID;
					} elseif ( is_array( $user ) && isset( $user['ID'] ) ) {
						$user_id = (int) $user['ID'];
					} elseif ( is_numeric( $user ) ) {
						$user_id = (int) $user;
					}

					if ( $user_id > 0 ) {
						$add_url( get_author_posts_url( $user_id ) );
					}
				}

				if ( $fetched_count < $query_limit ) {
					break;
				}
			}
		}

		return $urls;
	}

	/**
	 * Determine if a URL should be excluded based on settings and patterns.
	 * Centralized helper used by crawlers and fetch tasks.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_url_excluded( string $url ): bool {
		// Never exclude core WordPress or Simply Static assets
		if ( self::is_core_include_asset( $url ) ) {
			return false;
		}

		// Backup archives and their metadata are private working files. Keep this
		// check in the central exclusion path so previously queued records are also
		// skipped on later exports, not only during uploads-directory discovery.
		if ( self::is_private_backup_path( $url ) ) {
			self::debug_log( sprintf( 'Excluding URL "%s" — matched built-in rule: Simply Static private backup directory', $url ) );

			return true;
		}

		$excluded = array( '.php' );
		$opts     = Options::instance();

		$use_search              = (bool) $opts->get( 'use_search' );
		$use_search_results_page = $opts->get( 'use_search_results_page' );
		if ( null === $use_search_results_page ) {
			$use_search_results_page = true;
		}

		if ( ! $use_search || ! $use_search_results_page ) {
			$url_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
			if ( ! empty( $url_parts['query'] ) && ! self::is_local_asset_url( $url ) ) {
				self::debug_log( sprintf( 'Excluding URL "%s" - matched built-in rule: query-string URL (search results page is disabled)', $url ) );

				return true;
			}
		}

		// Exclude debug files (.log, .txt) but not robots.txt, llms.txt, _redirects, or _headers
		if ( preg_match( '/\.(log|txt)$/i', $url ) && strpos( $url, 'debug' ) !== false && strpos( $url, 'robots.txt' ) === false && strpos( $url, 'llms.txt' ) === false && strpos( $url, '_redirects' ) === false && strpos( $url, '_headers' ) === false ) {
			self::debug_log( sprintf( 'Excluding URL "%s" — matched built-in rule: debug log file pattern', $url ) );

			return true;
		}

		// Exclude feeds if add_feeds is not true.
		if ( ! $opts->get( 'add_feeds' ) ) {
			// Only exclude WordPress feed-style URLs
			if ( preg_match( '/(\/feed\/?$|\?feed=|\/feed\/|\/rss\/?$|\/atom\/?$)/i', $url ) ) {
				self::debug_log( sprintf( 'Excluding URL "%s" — matched built-in rule: feed URL (add_feeds is disabled)', $url ) );

				return true;
			}
		}

		// Exclude Rest API if add_rest_api is not true.
		if ( ! $opts->get( 'add_rest_api' ) ) {
			$excluded[] = 'wp-json';
		}

		$urls_to_exclude = $opts->get( 'urls_to_exclude' );
		$regex_patterns  = [];
		if ( ! empty( $urls_to_exclude ) ) {
			if ( is_array( $urls_to_exclude ) ) {
				$excluded_by_option = wp_list_pluck( $urls_to_exclude, 'url' );
			} else {
				$excluded_by_option = explode( "\n", $urls_to_exclude );
			}

			if ( is_array( $excluded_by_option ) ) {
				// Normalize: trim whitespace/CRLF, drop empties, unique
				$excluded_by_option = array_filter( array_map( 'trim', $excluded_by_option ), function ( $v ) {
					return $v !== '';
				} );
				$excluded_by_option = array_unique( $excluded_by_option );
				$parsed             = self::parse_patterns( $excluded_by_option );
				$excluded           = array_merge( $excluded, $parsed['literals'] );
				$regex_patterns     = $parsed['regex'];
			}
		}

		if ( apply_filters( 'simply_static_exclude_temp_dir', true ) ) {
			$excluded[] = self::get_temp_dir_url();
		}

		$excluded = apply_filters( 'ss_excluded_by_default', $excluded );

		if ( $excluded ) {
			$excluded = array_filter( $excluded );
		}

		// First test regex patterns if provided
		foreach ( (array) $regex_patterns as $pattern ) {
			if ( @preg_match( $pattern, $url ) ) {
				if ( preg_match( $pattern, $url ) ) {
					self::debug_log( sprintf( 'Excluding URL "%s" — matched regex exclusion rule: %s', $url, $pattern ) );

					return true;
				}
			}
		}

 	// Then test literal patterns (case-insensitive)
		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $excludable ) {
				// Path-like literals (starting with /) are matched against the
				// URL path at path-segment boundaries so that e.g. "/9" only
				// excludes the path /9 (and sub-paths /9/…) instead of every
				// URL that happens to contain the characters "/9" anywhere.
				if ( isset( $excludable[0] ) && $excludable[0] === '/' && strpos( $excludable, '.' ) === false ) {
					$url_path = parse_url( $url, PHP_URL_PATH ) ?: '/';
					$pattern  = rtrim( $excludable, '/' );
					if (
						strcasecmp( $url_path, $pattern ) === 0
						|| strcasecmp( $url_path, $pattern . '/' ) === 0
						|| stripos( $url_path, $pattern . '/' ) === 0
					) {
						self::debug_log( sprintf( 'Excluding URL "%s" — matched path exclusion rule: "%s"', $url, $excludable ) );

						return true;
					}
				} else {
					// Non-path literals: keep existing substring behaviour.
					if ( stripos( $url, $excludable ) !== false ) {
						self::debug_log( sprintf( 'Excluding URL "%s" — matched literal exclusion rule: "%s"', $url, $excludable ) );

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Determine whether a URL or relative path points into a private Simply
	 * Static backup directory.
	 *
	 * Backup/Migrate stores working files below
	 * uploads/simply-static/backup-{32-character random key}/. These files may
	 * contain archives and installation metadata and must never become static
	 * site artifacts.
	 *
	 * @param string $path URL or filesystem-style relative path.
	 *
	 * @return bool
	 */
	public static function is_private_backup_path( string $path ): bool {
		$url_path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $path, PHP_URL_PATH ) : parse_url( $path, PHP_URL_PATH );

		if ( is_string( $url_path ) && '' !== $url_path ) {
			$path = $url_path;
		}

		$path = self::normalize_slashes( rawurldecode( $path ) );

		return 1 === preg_match( '#(?:^|/)simply-static/backup-[a-f0-9]{32}(?:/|$)#i', $path );
	}

	/**
	 * Get all active plugins for the current site, including network-activated plugins on multisite.
	 *
	 * Returns a list of plugin basenames (e.g. akismet/akismet.php).
	 *
	 * @return array
	 */
	public static function get_all_active_plugins(): array {
		$active = (array) get_option( 'active_plugins', [] );
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// Network-activated plugins are stored as an associative array with plugin file as the key.
			$network         = (array) get_site_option( 'active_sitewide_plugins', [] );
			$network_plugins = array_keys( $network );
			$active          = array_merge( $active, $network_plugins );
		}
		$active = array_values( array_unique( $active ) );
		sort( $active );

		return $active;
	}

	/**
	 * Compute the target Static Site URL based on Simply Static settings.
	 * Returns an empty string if it cannot be determined.
	 *
	 * Logic:
	 * - destination_url_type = 'relative' and relative_path not empty:
	 *   Use the current site's scheme (https if wp_is_using_https() or is_ssl()),
	 *   then build home_url( '/', $scheme ) + relative_path.
	 * - destination_url_type = 'absolute' with non-empty destination_scheme and destination_host:
	 *   Normalize and return scheme://host.
	 *
	 * @return string The static site URL or empty string when unavailable.
	 */
	public static function get_static_site_url() {
		$options = get_option( 'simply-static' );
		if ( empty( $options ) || ! is_array( $options ) ) {
			return '';
		}

		$type       = isset( $options['destination_url_type'] ) ? strtolower( trim( $options['destination_url_type'] ) ) : '';
		$target_url = '';

		if ( 'relative' === $type ) {
			$relative_path = isset( $options['relative_path'] ) ? trim( $options['relative_path'] ) : '';
			if ( $relative_path !== '' ) {
				$scheme     = ( function_exists( 'wp_is_using_https' ) && wp_is_using_https() ) ? 'https' : ( is_ssl() ? 'https' : 'http' );
				$base_url   = home_url( '/', $scheme );
				$target_url = trailingslashit( $base_url ) . ltrim( $relative_path, '/' );
			}
		} elseif ( 'absolute' === $type ) {
			$scheme = isset( $options['destination_scheme'] ) ? trim( $options['destination_scheme'] ) : '';
			$host   = isset( $options['destination_host'] ) ? trim( $options['destination_host'] ) : '';
			if ( $scheme !== '' && $host !== '' ) {
				$scheme     = preg_replace( '/:\\/*$/', '', $scheme );
				$host       = preg_replace( '/^\\/*/', '', $host );
				$target_url = $scheme . '://' . $host;
			}
		}

		return $target_url;
	}

	/**
	 * Get the destination base used when rewriting plain-text file URLs.
	 *
	 * This mirrors how absolute/relative/offline URLs are written elsewhere in
	 * the exporter while allowing an empty string for root-relative exports.
	 *
	 * @return string|null
	 */
	public static function get_text_file_destination_base() {
		$options = Options::instance();

		switch ( $options->get( 'destination_url_type' ) ) {
			case 'absolute':
				$destination_url = untrailingslashit( (string) $options->get_destination_url() );
				return $destination_url === '' ? null : $destination_url;
			case 'relative':
				$relative_path = (string) $options->get( 'relative_path' );
				return $relative_path === '' ? '' : untrailingslashit( $relative_path );
			default:
				return untrailingslashit( (string) $options->get_destination_url() );
		}
	}

	/**
	 * Convert a sitemap reference found in robots.txt to a fully qualified URL.
	 *
	 * Search engines expect the robots.txt Sitemap directive to point to a
	 * complete URL. For relative exports we therefore promote local sitemap
	 * paths to the deployed static site URL instead of returning a relative path.
	 *
	 * @param string $url Sitemap URL or path.
	 *
	 * @return string
	 */
	public static function convert_text_file_sitemap_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return $url;
		}

		$options = Options::instance();
		if ( 'absolute' !== $options->get( 'destination_url_type' ) ) {
			return $url;
		}

		$static_site_url = untrailingslashit( self::get_static_site_url() );
		if ( $static_site_url === '' ) {
			return $url;
		}

		$absolute_url = self::relative_to_absolute_url( $url, trailingslashit( self::origin_url() ) . 'robots.txt' );
		if ( ! is_string( $absolute_url ) || $absolute_url === '' || ! self::is_local_url( $absolute_url ) ) {
			return $url;
		}

		$sanitized_path = self::sanitize_local_path( self::get_path_from_local_url( $absolute_url ) );

		return $static_site_url . $sanitized_path;
	}

	/**
	 * Replace local origin URLs in exported plain-text files such as robots.txt.
	 *
	 * @param string $content File content to update.
	 *
	 * @return string
	 */
	public static function replace_origin_urls_in_text( $content ) {
		if ( ! is_string( $content ) || $content === '' ) {
			return $content;
		}

		$sitemap_placeholders = array();
		$content              = preg_replace_callback(
			'/(^\s*Sitemap:\s*)(\S+)/im',
			function ( $matches ) use ( &$sitemap_placeholders ) {
				$placeholder = '__SIMPLY_STATIC_SITEMAP_' . count( $sitemap_placeholders ) . '__';
				$sitemap_placeholders[ $placeholder ] = $matches[1] . self::convert_text_file_sitemap_url( $matches[2] );
				return $placeholder;
			},
			$content
		);

		$destination_base = self::get_text_file_destination_base();
		if ( null === $destination_base ) {
			return empty( $sitemap_placeholders ) ? $content : strtr( $content, $sitemap_placeholders );
		}

		$pattern  = '/(?:https?:)?\\/\\/' . self::origin_host_pattern( false, true ) . '/i';
		$replaced = preg_replace( $pattern, $destination_base, $content );
		if ( is_string( $replaced ) ) {
			$content = $replaced;
		}

		return empty( $sitemap_placeholders ) ? $content : strtr( $content, $sitemap_placeholders );
	}

	/**
	 * Get the protocol used for the origin URL
	 * @return string http or https
	 */
	public static function origin_scheme() {
		$pattern = '/:\/\/.*/';

		return preg_replace( $pattern, '', self::origin_url() );
	}

	/**
	 * Get the host for the origin URL
	 * @return string host (URL minus the protocol)
	 */
	public static function origin_host() {
		return untrailingslashit( self::strip_protocol_from_url( self::origin_url() ) );
	}

	/**
	 * Build a regex-safe origin host/path pattern with a URL segment boundary.
	 *
	 * origin_host() may include a subdirectory (for example example.com/wp).
	 * Without a boundary, raw replacements can treat /wp-includes as though it
	 * started with the /wp base and leave broken suffixes like -includes.
	 *
	 * @param bool $encoded Whether the target text is URL-encoded.
	 * @param bool $allow_port Whether to allow any port when origin_url has none.
	 *
	 * @return string Regex fragment.
	 */
	public static function origin_host_pattern( $encoded = false, $allow_port = false ) {
		$origin_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( self::origin_url() ) : parse_url( self::origin_url() );

		if ( ! is_array( $origin_parts ) || empty( $origin_parts['host'] ) ) {
			$origin_host = self::origin_host();
			$pattern     = preg_quote( $encoded ? urlencode( $origin_host ) : $origin_host, '/' );

			return $pattern . ( $encoded ? '(?=$|%2F|%3F|%23)' : '(?=$|[\/?#])' );
		}

		$host = (string) $origin_parts['host'];
		$path = isset( $origin_parts['path'] ) ? untrailingslashit( (string) $origin_parts['path'] ) : '';

		if ( $encoded ) {
			$port = isset( $origin_parts['port'] ) ? ':' . $origin_parts['port'] : '';

			return preg_quote( urlencode( $host . $port . $path ), '/' ) . '(?=$|%2F|%3F|%23)';
		}

		$port_pattern = '';
		if ( isset( $origin_parts['port'] ) ) {
			$port_pattern = preg_quote( ':' . $origin_parts['port'], '/' );
		} elseif ( $allow_port ) {
			$port_pattern = '(?::\d+)?';
		}

		return preg_quote( $host, '/' ) . $port_pattern . preg_quote( $path, '/' ) . '(?=$|[\/?#])';
	}

	/**
	 * Build a regex-safe JSON-escaped origin URL pattern with a path boundary.
	 *
	 * WordPress often writes URLs in JSON as https:\/\/example.com\/wp\/path.
	 * A boundary prevents /wp from partially matching /wp-includes.
	 *
	 * @return string Regex fragment.
	 */
	public static function json_escaped_origin_url_pattern() {
		return preg_quote( addcslashes( untrailingslashit( self::origin_url() ), '/' ), '/' ) . '(?=$|\\\\\/|[?#])';
	}

	/**
	 * Get all URL bases that should be treated as local during export.
	 *
	 * Proxy setups can use a public Origin URL that differs from the actual
	 * WordPress home/site URL. Redirects and generated asset URLs may still point
	 * at the real WordPress URL, so all known local bases need to be accepted.
	 *
	 * @return array
	 */
	public static function local_url_bases() {
		$bases = array( self::origin_url() );

		if ( function_exists( 'home_url' ) ) {
			$bases[] = untrailingslashit( home_url() );
		}

		if ( function_exists( 'site_url' ) ) {
			$bases[] = untrailingslashit( site_url() );
		}

		$bases = array_filter( array_map( 'untrailingslashit', $bases ) );
		$bases = array_values( array_unique( $bases ) );

		usort( $bases, function ( $a, $b ) {
			$a_path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $a, PHP_URL_PATH ) : parse_url( $a, PHP_URL_PATH );
			$b_path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $b, PHP_URL_PATH ) : parse_url( $b, PHP_URL_PATH );

			return strlen( (string) $b_path ) <=> strlen( (string) $a_path );
		} );

		return apply_filters( 'ss_local_url_bases', $bases );
	}

	/**
	 * Find the local URL base that matches the given URL.
	 *
	 * @param string $url URL to match.
	 *
	 * @return string|null
	 */
	public static function get_local_url_base( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return null;
		}

		$url_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		if ( ! is_array( $url_parts ) || empty( $url_parts['host'] ) ) {
			return null;
		}

		$url_host = strtolower( preg_replace( '/:\d+$/', '', (string) $url_parts['host'] ) );
		$url_path = isset( $url_parts['path'] ) ? untrailingslashit( $url_parts['path'] ) : '';
		$same_host = false;

		foreach ( self::local_url_bases() as $base ) {
			$base_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );
			if ( ! is_array( $base_parts ) || empty( $base_parts['host'] ) ) {
				continue;
			}

			$base_host = strtolower( preg_replace( '/:\d+$/', '', (string) $base_parts['host'] ) );
			if ( $url_host !== $base_host ) {
				continue;
			}

			$same_host = true;

			$base_path = isset( $base_parts['path'] ) ? untrailingslashit( $base_parts['path'] ) : '';
			if ( '' === $base_path || '/' === $base_path || $url_path === $base_path || strpos( $url_path . '/', trailingslashit( $base_path ) ) === 0 ) {
				return $base;
			}
		}

		if ( $same_host && self::is_root_wordpress_asset_path( $url_path ) ) {
			$scheme = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] : self::origin_scheme();
			$port   = isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '';

			return $scheme . '://' . $url_parts['host'] . $port;
		}

		if ( self::is_current_wordpress_host( $url_host ) && self::is_root_wordpress_asset_path( $url_path ) ) {
			$scheme = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] : self::origin_scheme();
			$port   = isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '';

			return $scheme . '://' . $url_parts['host'] . $port;
		}

		return null;
	}

	/**
	 * Check whether a host is a WordPress origin for this export.
	 *
	 * @param string $host URL host.
	 *
	 * @return bool
	 */
	public static function is_current_wordpress_host( $host ) {
		$host = self::normalize_url_host( $host );
		if ( '' === $host ) {
			return false;
		}

		$allowed_hosts             = self::current_wordpress_hosts();
		$is_current_wordpress_host = in_array( $host, $allowed_hosts, true );

		return (bool) apply_filters(
			'ss_is_current_wordpress_host',
			$is_current_wordpress_host,
			$host,
			$allowed_hosts
		);
	}

	/**
	 * Get WordPress hosts that should be treated as local.
	 *
	 * @return array
	 */
	private static function current_wordpress_hosts() {
		$hosts = array();

		foreach ( self::local_url_bases() as $base ) {
			$base_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );
			if ( is_array( $base_parts ) && ! empty( $base_parts['host'] ) ) {
				$hosts[] = $base_parts['host'];
			}
		}

		if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$hosts[] = function_exists( 'wp_unslash' ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : $_SERVER['HTTP_HOST'];
		}

		if ( ! empty( $_SERVER['SERVER_NAME'] ) ) {
			$hosts[] = function_exists( 'wp_unslash' ) ? wp_unslash( $_SERVER['SERVER_NAME'] ) : $_SERVER['SERVER_NAME'];
		}

		$hosts = (array) apply_filters( 'ss_current_wordpress_hosts', $hosts );
		$hosts = array_map( array( __CLASS__, 'normalize_url_host' ), $hosts );
		$hosts = array_filter( $hosts );
		$hosts = array_values( array_unique( $hosts ) );

		return $hosts;
	}

	/**
	 * Normalize a URL host value for comparison.
	 *
	 * @param string $host URL host.
	 *
	 * @return string
	 */
	private static function normalize_url_host( $host ) {
		$host = strtolower( trim( (string) $host, ". \t\n\r\0\x0B" ) );

		return preg_replace( '/:\d+$/', '', $host );
	}

	/**
	 * Check whether a URL path points at WordPress asset directories.
	 *
	 * @param string $path URL path.
	 *
	 * @return bool
	 */
	public static function is_wordpress_asset_path( $path ) {
		return self::is_root_wordpress_asset_path( $path );
	}

	/**
	 * Check whether a same-host root path points at WordPress asset directories.
	 *
	 * @param string $path URL path.
	 *
	 * @return bool
	 */
	private static function is_root_wordpress_asset_path( $path ) {
		if ( ! is_string( $path ) || '' === $path || '/' === $path ) {
			return false;
		}

		$segments = explode( '/', trim( $path, '/' ) );
		if ( empty( $segments[0] ) ) {
			return false;
		}

		$options    = Options::instance();
		$asset_dirs = array(
			'wp-admin',
			'wp-content',
			'wp-includes',
			self::get_hide_wp_option( $options, 'wp_content_directory', 'wp_content_folder', 'wp-content' ),
			self::get_hide_wp_option( $options, 'wp_includes_directory', 'wp_includes_folder', 'wp-includes' ),
		);

		$asset_dirs = array_values( array_unique( array_filter( array_map( 'strval', $asset_dirs ) ) ) );

		return in_array( $segments[0], $asset_dirs, true );
	}

	/**
	 * Replace a local URL base with another base URL/path.
	 *
	 * @param string $url URL to rewrite.
	 * @param string $replacement Replacement base.
	 *
	 * @return string
	 */
	public static function replace_local_url_base( $url, $replacement ) {
		$base = self::get_local_url_base( $url );
		if ( null === $base ) {
			return $url;
		}

		$url_parts  = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		$base_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );

		if ( ! is_array( $url_parts ) || ! is_array( $base_parts ) ) {
			return $url;
		}

		$url_path  = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';
		$base_path = isset( $base_parts['path'] ) ? untrailingslashit( $base_parts['path'] ) : '';
		$path      = $url_path;

		if ( '' !== $base_path && '/' !== $base_path ) {
			$path = substr( $url_path, strlen( $base_path ) );
			$path = '' === $path ? '/' : $path;
		}

		$query    = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';
		$fragment = isset( $url_parts['fragment'] ) ? '#' . $url_parts['fragment'] : '';
		$base_url = untrailingslashit( (string) $replacement );

		if ( '' === $base_url ) {
			return self::add_leading_slash( ltrim( $path, '/' ) ) . $query . $fragment;
		}

		if ( './' === $replacement ) {
			return './' . ltrim( $path, '/' ) . $query . $fragment;
		}

		return $base_url . self::add_leading_slash( ltrim( $path, '/' ) ) . $query . $fragment;
	}

	/**
	 * Wrapper around home_url(). Useful for swapping out the URL during debugging.
	 * @return string home URL
	 */
	public static function origin_url() {
		$options = Options::instance();

		if ( $options->get( 'origin_url' ) ) {
			return apply_filters( 'ss_origin_url', esc_url( untrailingslashit( $options->get( 'origin_url' ) ) ) );
		}

		return apply_filters( 'ss_origin_url', untrailingslashit( home_url() ) );
	}

	/**
	 * Strip UTF-8 BOM from a string
	 *
	 * @param string $string String to strip BOM from
	 *
	 * @return string
	 */
	public static function strip_bom( $string ) {
		if ( ! is_string( $string ) ) {
			return $string;
		}

		return preg_replace( '/^\xEF\xBB\xBF/', '', $string );
	}

	/**
	 * Truncate if a string exceeds a certain length (30 chars by default)
	 * @return string
	 */
	public static function truncate( $string, $length = 30, $omission = '...' ) {
		return ( strlen( $string ) > $length + 3 ) ? ( substr( $string, 0, $length ) . $omission ) : $string;
	}

	/**
	 * Dump an object to error_log
	 *
	 * @param mixed $object Object to dump to the error log
	 *
	 * @return void
	 */
	public static function error_log( $object = null ) {
		$contents = self::get_contents_from_object( $object );
		error_log( $contents );
	}

	/**
	 * Clear the debug log
	 * @return void
	 */
	public static function clear_debug_log() {
		$debug_file = self::get_debug_log_filename();
		if ( file_exists( $debug_file ) ) {
			// Clear file
			file_put_contents( $debug_file, '' );
		}
	}

	/**
	 * Save an object/string to the debug log
	 *
	 * @param mixed $object Object to save to the debug log
	 *
	 * @return void
	 */
	public static function debug_log( $object = null ) {
		$options = Options::instance();
		if ( ! $options->get( 'debugging_mode' ) ) {
			return;
		}

		$debug_file = self::get_debug_log_filename();

		if ( ! file_exists( $debug_file ) ) {
			wp_mkdir_p( dirname( $debug_file ) );
		}

		// add timestamp and newline
		$message = '[' . date( 'Y-m-d H:i:s' ) . '] ';

		$trace = debug_backtrace();
		if ( isset( $trace[0]['file'] ) ) {
			$file = basename( $trace[0]['file'] );
			if ( isset( $trace[0]['line'] ) ) {
				$file .= ':' . $trace[0]['line'];
			}
			$message .= '[' . $file . '] ';
		}

		$contents = self::get_contents_from_object( $object );

		// get message onto a single line
		$contents = preg_replace( "/\r|\n/", "", $contents );

		$message .= $contents . "\n";

		// log the message to the debug file instead of the usual error_log location
		error_log( $message, 3, $debug_file );
	}

	/**
	 * Return the filename for the debug log
	 * @return string Filename for the debug log
	 */
	public static function get_debug_log_filename() {
		// Get directories.
		$uploads_dir       = wp_upload_dir();
		$simply_static_dir = $uploads_dir['basedir'] . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR;

		// Set name for debug file.
		$options = get_option( 'simply-static' );

		if ( isset( $options['encryption_key'] ) ) {
			return apply_filters( 'ss_debug_log_file', $simply_static_dir . $options['encryption_key'] . '-debug.txt', $options['encryption_key'] );
		} else {
			return apply_filters( 'ss_debug_log_file', $simply_static_dir . 'debug.txt', '' );
		}
	}

	/**
	 * Get contents of an object as a string
	 *
	 * @param mixed $object Object to get string for
	 *
	 * @return string         String containing the contents of the object
	 */
	protected static function get_contents_from_object( $object ) {
		// Handle common scalar types early and safely
		if ( is_string( $object ) ) {
			// Prevent huge memory usage by truncating very large strings
			return self::truncate( $object, 5000 );
		}
		if ( is_null( $object ) ) {
			return 'NULL';
		}
		if ( is_bool( $object ) ) {
			return $object ? 'TRUE' : 'FALSE';
		}
		if ( is_int( $object ) || is_float( $object ) ) {
			return (string) $object;
		}
		if ( is_resource( $object ) ) {
			return 'resource(' . get_resource_type( $object ) . ')';
		}

		// For arrays/objects, avoid var_dump which can explode memory usage.
		// Prefer JSON with partial output on error; fall back to print_r.
		$max_length = apply_filters( 'simply_static_debug_max_length', 100000 ); // 100 KB by default
		$json_opts  = defined( 'JSON_PARTIAL_OUTPUT_ON_ERROR' ) ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
		$encoded    = function_exists( 'wp_json_encode' ) ? wp_json_encode( $object, $json_opts, 5 ) : json_encode( $object, $json_opts, 5 );

		if ( $encoded === false || $encoded === null ) {
			$encoded = print_r( $object, true );
		}

		if ( strlen( $encoded ) > $max_length ) {
			$encoded = substr( $encoded, 0, $max_length ) . '... [truncated]';
		}

		return $encoded;
	}

	public static function is_valid_scheme( $scheme ) {
		$valid_schemes = apply_filters( 'simply_static_valid_schemes', [
			'http',
			'https',
		] );

		return in_array( $scheme, $valid_schemes );
	}

	/**
	 * Given a URL extracted from a page, return an absolute URL
	 *
	 * Takes a URL (e.g. /test) extracted from a page (e.g. http://foo.com/bar/) and
	 * returns an absolute URL (e.g. http://foo.com/bar/test). Absolute URLs are
	 * returned as-is. Exception: links beginning with a # (hash) are left as-is.
	 *
	 * A null value is returned in the event that the extracted_url is blank or it's
	 * unable to be parsed.
	 *
	 * @param string $extracted_url Relative or absolute URL extracted from page
	 * @param string $page_url URL of page
	 *
	 * @return string|null                   Absolute URL, or null
	 */
	public static function relative_to_absolute_url( $extracted_url, $page_url ) {

		// we can't do anything with null or blank urls
		if ( $extracted_url === null ) {
			return null;
		}

		$extracted_url = trim( $extracted_url );

		// we can't do anything with blank urls
		if ( $extracted_url === '' ) {
			return null;
		}

		// if we get a hash, e.g. href='#section-three', just return it as-is
		if ( strpos( $extracted_url, '#' ) === 0 ) {
			return $extracted_url;
		}

		// check for a protocol-less URL
		// (Note: there's a bug in PHP <= 5.4.7 where parsed URLs starting with //
		// are treated as a path. So we're doing this check upfront.)
		// http://php.net/manual/en/function.parse-url.php#example-4617
		if ( strpos( $extracted_url, '//' ) === 0 ) {

			// if this is a local URL, add the protocol to the URL
			if ( preg_match( '/^\/\/' . self::origin_host_pattern() . '/i', $extracted_url ) ) {
				$extracted_url = self::origin_scheme() . ':' . $extracted_url;
			}

			return $extracted_url;

		}

		$parsed_extracted_url = parse_url( $extracted_url );

		// parse_url can sometimes return false; bail if it does
		if ( $parsed_extracted_url === false ) {
			return null;
		}

		// if no path, check for an ending slash; if there isn't one, add one
		if ( ! isset( $parsed_extracted_url['path'] ) ) {
			if ( isset( $parsed_extracted_url['scheme'] ) && ! self::is_valid_scheme( $parsed_extracted_url['scheme'] ) ) {
				return $extracted_url;
			}
			$clean_url     = self::remove_params_and_fragment( $extracted_url );
			$fragment      = substr( $extracted_url, strlen( $clean_url ) );
			$extracted_url = trailingslashit( $clean_url ) . $fragment;
		}

		if ( isset( $parsed_extracted_url['host'] ) ) {

			return $extracted_url;

		} elseif ( isset( $parsed_extracted_url['scheme'] ) ) {

			// examples of schemes without hosts: java:, data:
			return $extracted_url;

		} else { // no host on extracted page (might be relative url)

			$path = isset( $parsed_extracted_url['path'] ) ? $parsed_extracted_url['path'] : '';

			$query    = isset( $parsed_extracted_url['query'] ) ? '?' . $parsed_extracted_url['query'] : '';
			$fragment = isset( $parsed_extracted_url['fragment'] ) ? '#' . $parsed_extracted_url['fragment'] : '';

			// turn our relative url into an absolute url
			$extracted_url = PhpUri::parse( $page_url )->join( $path . $query . $fragment );

			return $extracted_url;

		}
	}

	/**
	 * Recursively create a path from one page to another
	 *
	 * Takes a path (e.g. /blog/foobar/) extracted from a page (e.g. /blog/page/3/)
	 * and returns a path to get to the extracted page from the current page
	 * (e.g. ./../../foobar/index.html). Since this is for offline use, the path
	 * return will include a /index.html if the extracted path doesn't contain
	 * an extension.
	 *
	 * The function recursively calls itself, cutting off sections of the page path
	 * until the base matches the extracted path or it runs out of parts to remove,
	 * then it builds out the path to the extracted page.
	 *
	 * @param string $extracted_path Relative or absolute URL extracted from page.
	 * @param string $page_path URL of page.
	 * @param int $iterations Number of times the page path has been chopped.
	 *
	 * @return string|null                 Absolute URL, or null
	 */
	public static function create_offline_path( $extracted_path, $page_path, $iterations = 0 ) {
		if ( $iterations === 0 ) {
			// Remove index.html if it's there
			$page_path = self::strip_index_filenames_from_url( $page_path );

			// If it's not the root, and it doesn't have a trailing slash,
			// it's likely a file (like r080108-1.html).
			// We want to chop off the file part for depth calculation.
			if ( $page_path !== '/' && substr( $page_path, - 1 ) !== '/' ) {
				$page_path = dirname( $page_path );
			}

			$page_path = trailingslashit( $page_path );
		}

		if ( $page_path === '/' || strpos( $extracted_path, $page_path ) === 0 ) {
			$extracted_path = substr( $extracted_path, strlen( $page_path ) === 1 ? 0 : strlen( $page_path ) );
			$new_path       = '.' . str_repeat( '/..', $iterations ) . self::add_leading_slash( $extracted_path );

			return $new_path;
		} else {
			// match everything before the last slash
			$pattern = '/(.*)\/[^\/]*\/$/';
			// remove the last slash and anything after it
			$new_page_path = preg_replace( $pattern, '$1/', $page_path );

			return self::create_offline_path( $extracted_path, $new_page_path, ++ $iterations );
		}
	}

	/**
	 * Check if URL starts with same URL as WordPress installation
	 *
	 * Both http and https are assumed to be the same domain.
	 *
	 * @param string $url URL to check
	 *
	 * @return boolean      true if URL is local, false otherwise
	 */
	public static function is_local_url( $url ) {
		return apply_filters( 'ss_is_local_url', null !== self::get_local_url_base( self::remove_params_and_fragment( $url ) ) );
	}

	/**
	 * Determine whether two HTTP(S) URLs share the same security origin.
	 *
	 * Unlike is_local_url(), this comparison intentionally includes the scheme and
	 * effective port. It is used for credentials, which must never be forwarded to
	 * another service merely because it happens to use the same hostname.
	 *
	 * @param string $url  URL to inspect.
	 * @param string $base Expected origin URL.
	 *
	 * @return bool
	 */
	public static function is_same_origin_url( $url, $base ) {
		$url_parts  = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		$base_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );

		if ( ! is_array( $url_parts ) || ! is_array( $base_parts ) ) {
			return false;
		}

		if ( array_key_exists( 'user', $url_parts ) || array_key_exists( 'pass', $url_parts ) ) {
			return false;
		}

		$url_scheme  = strtolower( isset( $url_parts['scheme'] ) ? (string) $url_parts['scheme'] : '' );
		$base_scheme = strtolower( isset( $base_parts['scheme'] ) ? (string) $base_parts['scheme'] : '' );
		$url_host    = isset( $url_parts['host'] ) ? self::normalize_url_host( $url_parts['host'] ) : '';
		$base_host   = isset( $base_parts['host'] ) ? self::normalize_url_host( $base_parts['host'] ) : '';

		if (
			! in_array( $url_scheme, array( 'http', 'https' ), true )
			|| ! in_array( $base_scheme, array( 'http', 'https' ), true )
			|| '' === $url_host
			|| '' === $base_host
		) {
			return false;
		}

		$url_port  = isset( $url_parts['port'] ) ? (int) $url_parts['port'] : ( 'https' === $url_scheme ? 443 : 80 );
		$base_port = isset( $base_parts['port'] ) ? (int) $base_parts['port'] : ( 'https' === $base_scheme ? 443 : 80 );

		return $url_scheme === $base_scheme && $url_host === $base_host && $url_port === $base_port;
	}

	/**
	 * Check whether a URL has the exact origin of a configured WordPress URL.
	 *
	 * @param string $url URL to inspect.
	 *
	 * @return bool
	 */
	public static function is_local_origin_url( $url ) {
		$is_local = false;
		foreach ( self::local_url_bases() as $base ) {
			if ( self::is_same_origin_url( $url, $base ) ) {
				$is_local = true;
				break;
			}
		}

		return (bool) apply_filters( 'ss_is_local_origin_url', $is_local, $url, self::local_url_bases() );
	}

	/**
	 * Return an HTTP Basic Authorization header only for an exact WordPress origin.
	 *
	 * @param string $url Request URL.
	 *
	 * @return string|null
	 */
	public static function get_basic_auth_header_for_url( $url ) {
		$options  = Options::instance();
		$username = $options->get( 'http_basic_auth_username' );
		$password = $options->get( 'http_basic_auth_password' );

		if ( ! is_string( $username ) || ! is_string( $password ) || '' === $username || '' === $password ) {
			return null;
		}

		// Credential trust is deliberately narrower than crawl-local trust. An
		// integration may extend local URL filters for discovery without thereby
		// receiving the destination's Basic Auth secret.
		$auth_bases = array( self::origin_url() );
		if ( function_exists( 'home_url' ) ) {
			$auth_bases[] = untrailingslashit( home_url() );
		}
		if ( function_exists( 'site_url' ) ) {
			$auth_bases[] = untrailingslashit( site_url() );
		}
		$auth_bases = array_values( array_unique( array_filter( $auth_bases ) ) );
		$allowed    = false;
		foreach ( $auth_bases as $base ) {
			if ( self::is_same_origin_url( $url, $base ) ) {
				$allowed = true;
				break;
			}
		}

		$allowed = (bool) apply_filters( 'ss_send_basic_auth_to_url', $allowed, $url, $auth_bases );
		if ( ! $allowed ) {
			return null;
		}

		return 'Basic ' . base64_encode( $username . ':' . $password );
	}

	/**
	 * Check if WP-Cron is running.
	 *
	 * @return bool
	 */
	public static function is_cron(): bool {
		// Return false only when WP-Cron is explicitly disabled and no SS_CRON override is set.
		$wp_cron_disabled = ( defined( 'DISABLE_WP_CRON' ) && ( true === constant( 'DISABLE_WP_CRON' ) ) );
		if ( $wp_cron_disabled && ! defined( 'SS_CRON' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the path from a local URL, removing the protocol and host
	 *
	 * @param string $url URL to strip protocol/host from
	 *
	 * @return string       URL sans protocol/host
	 */
	public static function get_path_from_local_url( $url ) {
		if ( ! is_string( $url ) ) {
			return $url;
		}

		$base = self::get_local_url_base( self::remove_params_and_fragment( $url ) );
		if ( null !== $base ) {
			$path = self::get_path_from_url_base( $url, $base );
			if ( null !== $path ) {
				return $path;
			}
		}

		// Fallback: origin_host() may include a subdirectory; strip only once at the start.
		$no_scheme = self::strip_protocol_from_url( $url );
		$pattern   = '/^' . self::origin_host_pattern() . '/';
		$no_host   = preg_replace( $pattern, '', $no_scheme, 1 );

		return '/' . ltrim( $no_host, '/' );
	}

	/**
	 * Get the static/public path for a local URL after Hide WP path replacements.
	 *
	 * @param string $url Local URL or local path.
	 *
	 * @return string
	 */
	public static function get_public_path_from_local_url( $url ) {
		$source_path = self::get_path_from_local_url( $url );
		$public_path = self::replace_wordpress_path_with_public_path( $source_path );

		if (
			! is_string( $source_path )
			|| ! self::is_root_wordpress_asset_path( self::remove_params_and_fragment( $source_path ) )
		) {
			return $public_path;
		}

		$asset_prefix = self::get_public_asset_path_prefix( $url, $source_path );
		if ( '' === $asset_prefix ) {
			return $public_path;
		}

		return $asset_prefix . '/' . ltrim( $public_path, '/' );
	}

	/**
	 * Get the WordPress install path that must prefix a generated asset path.
	 *
	 * Local URL matching intentionally prefers the longest base so redirects
	 * between an Origin URL, home_url(), and site_url() can resolve to the same
	 * generated page. For assets, however, a deeper WordPress Address is a real
	 * public path when the Site Address is its parent. Preserve that suffix in
	 * the generated path instead of treating it as another export root.
	 *
	 * @param string $url Absolute local asset URL.
	 * @param string $source_path Asset path relative to the matched local base.
	 *
	 * @return string Path prefix, or an empty string when none should be added.
	 */
	private static function get_public_asset_path_prefix( $url, $source_path ) {
		if ( ! is_string( $url ) || ! is_string( $source_path ) ) {
			return '';
		}

		$public_bases = array( self::origin_url() );
		if ( function_exists( 'home_url' ) ) {
			$public_bases[] = untrailingslashit( home_url() );
		}

		$public_bases = array_values( array_unique( array_filter( array_map( 'untrailingslashit', $public_bases ) ) ) );
		$source_path = self::remove_params_and_fragment( $source_path );

		foreach ( $public_bases as $public_base ) {
			$public_root_path = self::get_path_from_url_base( $url, $public_base );
			if ( null === $public_root_path ) {
				continue;
			}

			$public_root_path = self::remove_params_and_fragment( $public_root_path );
			if ( $public_root_path === $source_path || strlen( $public_root_path ) <= strlen( $source_path ) ) {
				return '';
			}

			if ( substr( $public_root_path, -strlen( $source_path ) ) !== $source_path ) {
				return '';
			}

			return rtrim( substr( $public_root_path, 0, -strlen( $source_path ) ), '/' );
		}

		return '';
	}

	/**
	 * Get a URL path relative to one explicit local base.
	 *
	 * @param string $url URL to normalize.
	 * @param string $base Local base URL.
	 *
	 * @return string|null Relative path, or null when the base does not match.
	 */
	private static function get_path_from_url_base( $url, $base ) {
		$url_parts  = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		$base_parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );

		if ( ! is_array( $url_parts ) || ! is_array( $base_parts ) || empty( $url_parts['host'] ) || empty( $base_parts['host'] ) ) {
			return null;
		}

		if ( self::normalize_url_host( $url_parts['host'] ) !== self::normalize_url_host( $base_parts['host'] ) ) {
			return null;
		}

		$url_path  = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';
		$base_path = isset( $base_parts['path'] ) ? untrailingslashit( $base_parts['path'] ) : '';

		if (
			'' !== $base_path
			&& '/' !== $base_path
			&& $url_path !== $base_path
			&& 0 !== strpos( untrailingslashit( $url_path ) . '/', trailingslashit( $base_path ) )
		) {
			return null;
		}

		if ( '' !== $base_path && '/' !== $base_path ) {
			$url_path = substr( $url_path, strlen( $base_path ) );
			$url_path = '' === $url_path ? '/' : $url_path;
		}

		$query    = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';
		$fragment = isset( $url_parts['fragment'] ) ? '#' . $url_parts['fragment'] : '';

		return '/' . ltrim( $url_path, '/' ) . $query . $fragment;
	}

	/**
	 * Get the WordPress source path for a local URL that may already use Hide WP replacements.
	 *
	 * @param string $url Local URL or local path.
	 *
	 * @return string
	 */
	public static function get_source_path_from_local_url( $url ) {
		$path = self::get_path_from_local_url( $url );

		return self::replace_public_path_with_wordpress_path( $path );
	}

	/**
	 * Resolve a local asset URL to a readable file within an explicit WordPress root.
	 *
	 * URL paths are decoded once and rejected when they contain traversal,
	 * control characters, backslashes, encoded path separators, or ambiguous
	 * double-encoding. The canonical file path must remain inside the root that
	 * corresponds to its URL prefix, preventing symlinks from escaping that root.
	 *
	 * @param string $url Local asset URL.
	 *
	 * @return string|\WP_Error|null Canonical file path, an error for an unsafe
	 *                               path, or null when no local file exists.
	 */
	public static function resolve_local_asset_path( $url ) {
		$unsafe_path_error = static function () {
			return new \WP_Error(
				'simply_static_unsafe_local_asset_path',
				__( 'Refusing to read a local asset outside an allowed WordPress directory.', 'simply-static' )
			);
		};

		if (
			! is_string( $url )
			|| '' === $url
			|| false !== strpos( $url, "\0" )
			|| false !== strpos( $url, '\\' )
			|| preg_match( '/[\x01-\x1F\x7F]/', $url )
			|| ! self::is_local_url( $url )
		) {
			return $unsafe_path_error();
		}

		$source_path = self::get_source_path_from_local_url( self::remove_params_and_fragment( $url ) );
		if ( ! is_string( $source_path ) || '' === $source_path ) {
			return $unsafe_path_error();
		}

		// Reject malformed escapes and encoded separators before decoding. A
		// remaining dangerous escape after decoding indicates double-encoding.
		if (
			preg_match( '/%(?![0-9a-f]{2})/i', $source_path )
			|| preg_match( '/%(?:00|2f|5c)/i', $source_path )
		) {
			return $unsafe_path_error();
		}

		$decoded_path = rawurldecode( $source_path );
		if (
			false !== strpos( $decoded_path, "\0" )
			|| false !== strpos( $decoded_path, '\\' )
			|| preg_match( '/[\x01-\x1F\x7F]/', $decoded_path )
			|| preg_match( '/%(?:00|2e|2f|5c)/i', $decoded_path )
		) {
			return $unsafe_path_error();
		}

		$segments = explode( '/', $decoded_path );
		foreach ( $segments as $segment ) {
			if ( '.' === $segment || '..' === $segment ) {
				return $unsafe_path_error();
			}
		}

		$segments        = array_values( array_filter( $segments, 'strlen' ) );
		$normalized_path = '/' . implode( '/', $segments );
		$mappings        = array();

		$add_mapping = static function ( $root, $base_url = null ) use ( &$mappings ) {
			if ( ! is_string( $root ) || '' === $root || ! is_dir( $root ) ) {
				return;
			}

			$root_path = realpath( $root );
			if ( false === $root_path ) {
				return;
			}
			$root_path = rtrim( str_replace( '\\', '/', $root_path ), '/' );
			if ( '' === $root_path || preg_match( '/^[a-z]:$/i', $root_path ) ) {
				return;
			}

			$url_prefix = '/';
			if ( is_string( $base_url ) && '' !== $base_url ) {
				if ( self::is_local_url( $base_url ) ) {
					$url_prefix = self::get_source_path_from_local_url( self::remove_params_and_fragment( $base_url ) );
				} else {
					$parsed_path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base_url, PHP_URL_PATH ) : parse_url( $base_url, PHP_URL_PATH );
					$url_prefix  = is_string( $parsed_path ) ? $parsed_path : '';
				}

				if ( ! is_string( $url_prefix ) || false !== strpos( $url_prefix, '\\' ) ) {
					return;
				}

				$url_prefix = '/' . trim( rawurldecode( $url_prefix ), '/' );
				if ( '/' !== $url_prefix ) {
					$url_prefix = rtrim( $url_prefix, '/' );
				}
			}

			$mappings[] = array(
				'root'       => $root_path,
				'url_prefix' => $url_prefix,
			);
		};

		// More-specific URL mappings must be attempted before ABSPATH's catch-all.
		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = wp_upload_dir();
			if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) && ! empty( $uploads['baseurl'] ) ) {
				$add_mapping( $uploads['basedir'], $uploads['baseurl'] );
			}
		}
		if ( defined( 'WP_PLUGIN_DIR' ) && defined( 'WP_PLUGIN_URL' ) ) {
			$add_mapping( WP_PLUGIN_DIR, WP_PLUGIN_URL );
		}
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$add_mapping( WP_CONTENT_DIR, WP_CONTENT_URL );
		}
		if ( defined( 'ABSPATH' ) ) {
			$add_mapping( ABSPATH );
		}

		$escaped_roots      = false;
		$checked_candidates = array();
		foreach ( $mappings as $mapping ) {
			$prefix = $mapping['url_prefix'];
			if (
				'/' !== $prefix
				&& $normalized_path !== $prefix
				&& 0 !== strpos( $normalized_path, $prefix . '/' )
			) {
				continue;
			}

			$relative_path = '/' === $prefix ? ltrim( $normalized_path, '/' ) : ltrim( substr( $normalized_path, strlen( $prefix ) ), '/' );
			$candidate     = $mapping['root'] . ( '' === $relative_path ? '' : '/' . $relative_path );
			if ( isset( $checked_candidates[ $candidate ] ) ) {
				continue;
			}
			$checked_candidates[ $candidate ] = true;

			$real_candidate = realpath( $candidate );
			if ( false === $real_candidate ) {
				continue;
			}

			$real_candidate = str_replace( '\\', '/', $real_candidate );
			if ( 0 !== strpos( $real_candidate, $mapping['root'] . '/' ) ) {
				$escaped_roots = true;
				continue;
			}

			if ( is_file( $real_candidate ) && is_readable( $real_candidate ) ) {
				return $real_candidate;
			}
		}

		return $escaped_roots ? $unsafe_path_error() : null;
	}

	/**
	 * Convert a local URL that may use Hide WP replacements back to the WordPress source URL.
	 *
	 * @param string $url Local URL.
	 *
	 * @return string
	 */
	public static function get_source_url_from_local_url( $url ) {
		if ( ! is_string( $url ) || ! self::is_local_url( $url ) ) {
			return $url;
		}

		$base = self::get_local_url_base( self::remove_params_and_fragment( $url ) );
		if ( null === $base ) {
			return $url;
		}

		$path = self::get_source_path_from_local_url( $url );

		return untrailingslashit( $base ) . self::add_leading_slash( ltrim( $path, '/' ) );
	}

	/**
	 * Replace default WordPress asset directories with configured Hide WP public directories.
	 *
	 * @param string $path Local path.
	 *
	 * @return string
	 */
	public static function replace_wordpress_path_with_public_path( $path ) {
		return self::replace_wordpress_asset_path( $path, false );
	}

	/**
	 * Replace configured Hide WP public directories with the real WordPress source directories.
	 *
	 * @param string $path Local path.
	 *
	 * @return string
	 */
	public static function replace_public_path_with_wordpress_path( $path ) {
		return self::replace_wordpress_asset_path( $path, true );
	}

	/**
	 * Apply Hide WP path replacements in either direction.
	 *
	 * @param string $path Local path.
	 * @param bool   $reverse Whether to map public paths back to WordPress source paths.
	 *
	 * @return string
	 */
	private static function replace_wordpress_asset_path( $path, $reverse = false ) {
		if ( ! is_string( $path ) || '' === $path ) {
			return $path;
		}

		$clean_path     = self::remove_params_and_fragment( $path );
		$query_fragment = substr( $path, strlen( $clean_path ) );
		$leading_slash  = strpos( $clean_path, '/' ) === 0;
		$trailing_slash = strlen( $clean_path ) > 1 && substr( $clean_path, - 1 ) === '/';
		$segments       = explode( '/', trim( $clean_path, '/' ) );

		if ( empty( $segments ) || '' === $segments[0] ) {
			return $path;
		}

		$options = Options::instance();
		$map     = array(
			'wp-content'  => self::get_hide_wp_option( $options, 'wp_content_directory', 'wp_content_folder', 'wp-content' ),
			'wp-includes' => self::get_hide_wp_option( $options, 'wp_includes_directory', 'wp_includes_folder', 'wp-includes' ),
			'uploads'     => self::get_hide_wp_option( $options, 'wp_uploads_directory', 'wp_uploads_folder', 'uploads' ),
			'plugins'     => self::get_hide_wp_option( $options, 'wp_plugins_directory', 'wp_plugins_folder', 'plugins' ),
			'themes'      => self::get_hide_wp_option( $options, 'wp_themes_directory', 'wp_themes_folder', 'themes' ),
		);

		if ( $reverse ) {
			self::replace_path_segment( $segments, 0, $map['wp-content'], 'wp-content' );
			self::replace_path_segment( $segments, 0, $map['wp-includes'], 'wp-includes' );

			if ( isset( $segments[0] ) && 'wp-content' === $segments[0] && isset( $segments[1] ) ) {
				self::replace_path_segment( $segments, 1, $map['uploads'], 'uploads' );
				self::replace_path_segment( $segments, 1, $map['plugins'], 'plugins' );
				self::replace_path_segment( $segments, 1, $map['themes'], 'themes' );
			}
		} else {
			self::replace_path_segment( $segments, 0, 'wp-content', $map['wp-content'] );
			self::replace_path_segment( $segments, 0, 'wp-includes', $map['wp-includes'] );

			if ( isset( $segments[0] ) && $map['wp-content'] === $segments[0] && isset( $segments[1] ) ) {
				self::replace_path_segment( $segments, 1, 'uploads', $map['uploads'] );
				self::replace_path_segment( $segments, 1, 'plugins', $map['plugins'] );
				self::replace_path_segment( $segments, 1, 'themes', $map['themes'] );
			}
		}

		$theme_style_name = self::get_hide_wp_option( $options, 'theme_style_name', '', 'style' );
		$theme_style_name = preg_replace( '/\.css$/i', '', $theme_style_name );
		if ( '' === $theme_style_name ) {
			$theme_style_name = 'style';
		}

		$is_theme_asset = isset( $segments[0], $segments[1] )
			&& ( $reverse ? 'wp-content' === $segments[0] && 'themes' === $segments[1] : $map['wp-content'] === $segments[0] && $map['themes'] === $segments[1] );

		if ( $is_theme_asset && ! empty( $segments ) ) {
			$last_index = count( $segments ) - 1;
			$from       = $reverse ? $theme_style_name . '.css' : 'style.css';
			$to         = $reverse ? 'style.css' : $theme_style_name . '.css';

			if ( $from !== $to && isset( $segments[ $last_index ] ) && $from === $segments[ $last_index ] ) {
				$segments[ $last_index ] = $to;
			}
		}

		$mapped_path = implode( '/', $segments );
		if ( $leading_slash ) {
			$mapped_path = '/' . $mapped_path;
		}
		if ( $trailing_slash && substr( $mapped_path, - 1 ) !== '/' ) {
			$mapped_path .= '/';
		}

		return $mapped_path . $query_fragment;
	}

	/**
	 * Get a Hide WP option with support for the historical *_folder keys.
	 *
	 * @param Options $options Options instance.
	 * @param string  $primary Primary option key.
	 * @param string  $legacy Legacy option key.
	 * @param string  $default Default segment.
	 *
	 * @return string
	 */
	private static function get_hide_wp_option( $options, $primary, $legacy, $default ) {
		$value = $options->get( $primary );
		if ( ( null === $value || '' === $value ) && '' !== $legacy ) {
			$value = $options->get( $legacy );
		}

		if ( null === $value || '' === $value ) {
			$value = $default;
		}

		$value = trim( (string) $value, '/' );

		return '' === $value ? $default : $value;
	}

	/**
	 * Replace one path segment when it matches the expected value.
	 *
	 * @param array  $segments Path segments.
	 * @param int    $index Segment index.
	 * @param string $from Current segment.
	 * @param string $to Replacement segment.
	 *
	 * @return void
	 */
	private static function replace_path_segment( &$segments, $index, $from, $to ) {
		if ( '' === $from || '' === $to || ! isset( $segments[ $index ] ) ) {
			return;
		}

		if ( $segments[ $index ] === $from ) {
			$segments[ $index ] = $to;
		}
	}

	/**
	 * Returns a URL w/o the query string or fragment (i.e. nothing after the path)
	 *
	 * @param string $url URL to remove query string/fragment from
	 *
	 * @return string      URL without query string/fragment
	 */
	public static function remove_params_and_fragment( $url ) {
		return preg_replace( '/(\?|#).*/', '', $url );
	}

	/**
	 * Converts a textarea into an array w/ each line being an entry in the array
	 *
	 * @param string $textarea Textarea to convert
	 *
	 * @return array            Converted array
	 */
	public static function string_to_array( $textarea ) {
		if ( ! is_string( $textarea ) ) {
			return array();
		}

		// using preg_split to intelligently break at newlines
		// see: http://stackoverflow.com/questions/1483497/how-to-put-string-in-array-split-by-new-line
		$lines = preg_split( "/\r\n|\n|\r/", $textarea );
		array_walk( $lines, 'trim' );
		$lines = array_filter( $lines );

		return $lines;
	}

	/**
	 * Remove the //, http://, https:// protocols from a URL
	 *
	 * @param string $url URL to remove protocol from
	 *
	 * @return string      URL sans http/https protocol
	 */
	public static function strip_protocol_from_url( $url ) {
		$pattern = '/^(https?:)?\/\//';

		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Remove index.html/index.php from a URL
	 *
	 * @param string $url URL to remove index file from
	 *
	 * @return string      URL sans index file
	 */
	public static function strip_index_filenames_from_url( $url ) {
		$pattern = '/index.(html?|php)$/';

		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Get the current datetime formatted as a string for entry into MySQL
	 * @return string MySQL formatted datetime
	 */
	public static function formatted_datetime() {
		return current_time( 'Y-m-d H:i:s' );
	}

	/**
	 * Sanitize filename to remove problematic Unicode characters
	 *
	 * @param string $filename Filename to sanitize.
	 *
	 * @return string Sanitized filename.
	 */
	public static function sanitize_filename( $filename ) {
		if ( $filename === '__qs' ) {
			return $filename;
		}

		/**
		 * Filter whether to preserve original filenames without sanitization.
		 *
		 * When true, spaces and special characters in filenames are preserved
		 * instead of being converted to hyphens. This is useful for sites with
		 * large existing static structures that rely on original filenames.
		 *
		 * @param bool $preserve Whether to preserve original filenames. Default false.
		 *
		 * @since 3.6.0.1
		 *
		 */
		if ( apply_filters( 'ss_preserve_original_filenames', false ) ) {
			return $filename;
		}

		// Bypass for safe ASCII (alphanumerics, hyphens, underscores, dots)
		if ( preg_match( '/^[a-zA-Z0-9\-_.]+$/', $filename ) && substr( $filename, - 1 ) !== '.' ) {
			return $filename;
		}

		$filename = html_entity_decode( $filename, ENT_QUOTES, 'UTF-8' );
		if ( function_exists( 'remove_accents' ) ) {
			$filename = remove_accents( $filename );
		}

		// Remove bullet points, ellipses, copyright, and private use characters
		$filename = preg_replace( '/[\x{2022}\x{2026}\x{00A9}\x{E000}-\x{F8FF}]/u', '-', (string) $filename );

		if ( is_null( $filename ) ) {
			$filename = '';
		}

		return sanitize_file_name( $filename );
	}

	/**
	 * Sanitize each segment of a path.
	 *
	 * @param string $path Path to sanitize.
	 *
	 * @return string Sanitized path.
	 */
	public static function sanitize_path( $path ) {
		$segments           = explode( '/', $path );
		$sanitized_segments = array_map( [ self::class, 'sanitize_filename' ], $segments );

		return implode( '/', $sanitized_segments );
	}

	/**
	 * Sanitize a local path (sans host) while preserving query and fragment.
	 *
	 * Uses the same filename/extension splitting logic as
	 * create_directories_for_static_page() to ensure that the sanitized URL
	 * written into HTML always matches the filename stored on disk.
	 *
	 * Previously the whole last path segment (e.g. "file.main.css") was passed
	 * to sanitize_filename() in one piece, causing WordPress's
	 * sanitize_file_name() to treat intermediate dots as suspect extensions and
	 * append underscores (e.g. "file.main_.css"), while the file-save path
	 * split the extension first and sanitized only the base name.
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string Sanitized path with original query/fragment.
	 */
	public static function sanitize_local_path( $path ) {
		$clean_path     = self::remove_params_and_fragment( $path );
		$query_fragment = substr( $path, strlen( $clean_path ) );
		$decoded_path   = rawurldecode( $clean_path );

		// Split into directory and filename+extension using the same helper
		// that the file-save path uses, so both sides agree on what the
		// "filename" vs "extension" portions are.
		$path_info = self::url_path_info( $decoded_path );

		// Sanitize directory segments (these have no extension to worry about).
		$sanitized_dir = self::sanitize_path( $path_info['dirname'] );

		// Sanitize only the base filename (without extension), mirroring
		// create_directories_for_static_page().
		$sanitized_filename = self::sanitize_filename( $path_info['filename'] );

		// Reassemble: dir + sanitized basename + .extension
		$result = $sanitized_dir . $sanitized_filename;
		if ( ! empty( $path_info['extension'] ) ) {
			$result .= '.' . $path_info['extension'];
		}

		return $result . $query_fragment;
	}

	/**
	 * Similar to PHP's pathinfo(), but designed with URL paths in mind (instead of directories)
	 *
	 * Example:
	 *   $info = self::url_path_info( '/manual/en/function.pathinfo.php?test=true' );
	 *     $info['dirname']   === '/manual/en/'
	 *     $info['basename']  === 'function.pathinfo.php'
	 *     $info['extension'] === 'php'
	 *     $info['filename']  === 'function.pathinfo'
	 *
	 * @param string $path The URL path
	 *
	 * @return array        Array containing info on the parts of the path
	 */
	public static function url_path_info( $path ) {
		$info = array(
			'dirname'   => '',
			'basename'  => '',
			'filename'  => '',
			'extension' => ''
		);

		$path = self::remove_params_and_fragment( $path );

		// everything after the last slash is the filename
		$last_slash_location = strrpos( $path, '/' );
		if ( $last_slash_location === false ) {
			$info['basename'] = $path;
		} else {
			$info['dirname']  = substr( $path, 0, $last_slash_location + 1 );
			$info['basename'] = substr( $path, $last_slash_location + 1 );
		}

		// finding the dot for the extension
		$last_dot_location = strrpos( $info['basename'], '.' );
		if ( $last_dot_location === false ) {
			$info['filename'] = $info['basename'];
		} else {
			$info['filename']  = substr( $info['basename'], 0, $last_dot_location );
			$info['extension'] = substr( $info['basename'], $last_dot_location + 1 );
		}

		// substr sets false if it fails, we're going to reset those values to ''
		foreach ( $info as $name => $value ) {
			if ( $value === false ) {
				$info[ $name ] = '';
			}
		}

		return $info;
	}

	public static function is_local_asset_url( $url ) {
		if ( ! self::is_local_url( $url ) ) {
			return false;
		}

		$allowed_asset_extensions = apply_filters( 'simply_static_allowed_local_asset_extensions', [
			// Images
			'webp',
			'gif',
			'jpg',
			'jpeg',
			'png',
			'svg',
			'ico',
			'cur',
			// Media
			'mp4',
			'webm',
			'ogg',
			'ogv',
			'mp3',
			'wav',
			// Data/Docs
			'json',
			'xml',
			'csv',
			'pdf',
			'txt',
			// Web assets
			'js',
			'css',
			// Fonts
			'woff2',
			'woff',
			'ttf',
			'eot',
			'otf'
		] );

		$path_info = self::url_path_info( $url );

		if ( empty( $path_info['extension'] ) ) {
			return false;
		}

		return in_array( $path_info['extension'], $allowed_asset_extensions, true );
	}

	/**
	 * Check if a URL belongs to core WordPress or Simply Static assets that should never be excluded.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool True if it's a core include asset.
	 */
	public static function is_core_include_asset( string $url ): bool {
		$core_paths = [ includes_url(), plugins_url() . '/simply-static/', plugins_url() . '/simply-static-pro/' ];
		foreach ( $core_paths as $path ) {
			if ( strpos( $url, $path ) !== false ) {
				return self::is_local_asset_url( $url );
			}
		}

		return false;
	}

	/**
	 * Ensure there is a single trailing directory separator on the path
	 *
	 * @param string $path File path to add trailing directory separator to
	 */
	public static function add_trailing_directory_separator( $path ) {
		return self::remove_trailing_directory_separator( $path ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Remove all trailing directory separators
	 *
	 * @param string $path File path to remove trailing directory separators from
	 */
	public static function remove_trailing_directory_separator( $path ) {
		return rtrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Remove all leading directory separators
	 *
	 * @param string $path File path to remove leading directory separators from
	 */
	public static function remove_leading_directory_separator( $path ) {
		if ( $path === null ) {
			return '';
		}

		return ltrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Add a slash to the beginning of a path
	 *
	 * @param string $path URL path to add leading slash to
	 */
	public static function add_leading_slash( $path ) {
		return '/' . self::remove_leading_slash( $path );
	}

	/**
	 * Remove a slash from the beginning of a path
	 *
	 * @param string $path URL path to remove leading slash from
	 */
	public static function remove_leading_slash( $path ) {
		if ( $path === null ) {
			return '';
		}

		return ltrim( $path, '/' );
	}

	/**
	 * Add a message to the array of status messages for the job
	 *
	 * @param array $messages Array of messages to add the message to
	 * @param string $task_name Name of the task
	 * @param string $message Message to display about the status of the job
	 * @param boolean $unique If unique, the task_name/key will get a prefix if the same exists.
	 *
	 * @return array
	 */
	public static function add_archive_status_message( $messages, $task_name, $message, $unique = false ) {
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $task_name, $messages ) || $unique ) {
			if ( $unique ) {
				$task_name = $task_name . '_' . uniqid();
			}
			$messages[ $task_name ] = array(
				'message'  => $message,
				'datetime' => self::formatted_datetime()
			);
		} else { // otherwise just update the message
			$messages[ $task_name ]['message'] = $message;
		}

		return $messages;
	}

	/**
	 * Get full URL from path.
	 *
	 * @param string $path given path.
	 *
	 * @return string
	 */
	public static function abs_path_to_url( $path = '' ) {
		$normalized_path = wp_normalize_path( $path );

		// Check if the path is within WP_CONTENT_DIR
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$normalized_content_dir = wp_normalize_path( untrailingslashit( WP_CONTENT_DIR ) );

			// If the path starts with the content directory, use WP_CONTENT_URL for replacement
			if ( $normalized_path === $normalized_content_dir || 0 === strpos( $normalized_path, $normalized_content_dir . '/' ) ) {
				$url = str_replace(
					$normalized_content_dir,
					untrailingslashit( WP_CONTENT_URL ),
					$normalized_path
				);

				return esc_url_raw( $url );
			}
		}

		// Default behavior for paths inside the WordPress installation.
		$normalized_abspath = wp_normalize_path( untrailingslashit( ABSPATH ) );
		if ( $normalized_path !== $normalized_abspath && 0 !== strpos( $normalized_path, $normalized_abspath . '/' ) ) {
			return '';
		}

		$url = site_url() . substr( $normalized_path, strlen( $normalized_abspath ) );

		return esc_url_raw( $url );
	}

	/**
	 * Combine multiple paths into a single path while handling varying slashes and trailing slashes
	 *
	 * @param string ...$paths Each path to combine. You can pass as many paths as you want.
	 *
	 * @return string The combined path
	 */
	public static function combine_path(): string {
		$paths = func_get_args();

		if ( count( $paths ) < 1 ) {
			return '';
		}

		$paths = array_map( fn( $path ) => self::normalize_slashes( $path ), $paths );

		// We don't strip the slashes from the first path because on Linux, paths start with a slash.
		$trimmed_path = array_map( fn( $path ) => trim( trim( $path ), '/' ), array_slice( $paths, 1 ) );
		array_unshift( $trimmed_path, untrailingslashit( $paths[0] ) );

		return implode( '/', $trimmed_path );
	}

	/**
	 * Normalize slashes in a path to forward slashes
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string The normalized path.
	 */
	public static function normalize_slashes( string $path ): string {
		return strpos( $path, '\\' ) !== false ? str_replace( '\\', '/', $path ) : $path;
	}

	/**
	 * Build a safe relative path from an absolute path and its base directory.
	 * Ensures forward slashes and a leading slash for consistent URL building.
	 *
	 * @param string $base_dir The base directory (prefix) of the absolute path.
	 * @param string $absolute_path The absolute path to the file.
	 *
	 * @return string               The normalized relative path starting with '/'.
	 */
	public static function safe_relative_path( string $base_dir, string $absolute_path ): string {
		$dir_norm = rtrim( $base_dir, DIRECTORY_SEPARATOR );
		$rel      = substr( $absolute_path, strlen( $dir_norm ) );
		if ( $rel === false ) {
			$rel = str_replace( $base_dir, '', $absolute_path );
		}
		$rel = str_replace( '\\', '/', $rel );
		if ( $rel === '' || $rel[0] !== '/' ) {
			$rel = '/' . ltrim( $rel, '/' );
		}

		return $rel;
	}

	/**
	 * Join a base URL and a relative path with exactly one slash.
	 *
	 * @param string $base_url Base URL (may end with or without a slash).
	 * @param string $relative_path Relative path (may start with or without a slash).
	 *
	 * @return string               The joined URL.
	 */
	public static function safe_join_url( string $base_url, string $relative_path ): string {
		return rtrim( $base_url, '/' ) . '/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the global $wp_filesystem with credentials set.
	 * Returns null in case of any errors.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	public static function get_file_system() {
		global $wp_filesystem;

		$success = true;

		// Initialize the file system if it has not been done yet.
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			$constants = array(
				'hostname'    => 'FTP_HOST',
				'username'    => 'FTP_USER',
				'password'    => 'FTP_PASS',
				'public_key'  => 'FTP_PUBKEY',
				'private_key' => 'FTP_PRIKEY',
			);

			$credentials = array();

			// We provide credentials based on wp-config.php constants.
			// Reference https://developer.wordpress.org/apis/wp-config-php/#wordpress-upgrade-constants
			foreach ( $constants as $key => $constant ) {
				if ( defined( $constant ) ) {
					$credentials[ $key ] = constant( $constant );
				}
			}

			$success = WP_Filesystem( $credentials );
		}

		if ( ! $success || $wp_filesystem->errors->has_errors() ) {
			return null;
		}

		return $wp_filesystem;
	}

	/**
	 * Clear all transients used in Simply Static.
	 *
	 * @return void
	 */
	public static function clear_transients() {
		// Diagnostics.
		delete_transient( 'simply_static_checks' );
		delete_transient( 'simply_static_failed_tests' );

		// Tasks.
		$tasks = [
			'fetch_urls',
			'search',
			'minify',
			'optimize_directories',
			'shortpixel',
			'shortpixel_download',
			'aws_empty',
			'create_zip_archive',
			'transfer_files_locally',
			'github_blobs',
			'github_commit',
			'bunny_deploy',
			'tiiny_deploy',
			'aws_deploy',
			'sftp_deploy',
			'sftp_bulk_deploy',
		];

		foreach ( $tasks as $task ) {
			delete_option( 'simply_static_' . $task . '_total_pages' );
		}
	}

	public static function get_temp_dir_url() {
		$dir = self::get_temp_dir();

		return self::abs_path_to_url( $dir );
	}

	/*
	 * Get the absolute path to the temporary file directory.
	 *
	 */
	public static function get_temp_dir() {
		$options = get_option( 'simply-static' );

		// Preferred base temp directory from settings if provided and safe
		if ( ! empty( $options['temp_files_dir'] ) ) {
			$temp_dir = $options['temp_files_dir'];
			// If a stream wrapper path is provided by a plugin (e.g., Infinite Uploads), avoid using it for local temp work.
			if ( function_exists( 'wp_is_stream' ) && wp_is_stream( $temp_dir ) ) {
				$temp_dir = '';
			}
		} else {
			$temp_dir = '';
		}

		// Fallback to uploads dir if not set by option
		if ( $temp_dir === '' ) {
			$upload_dir = wp_upload_dir();
			$basedir    = isset( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : '';
			// Guard against stream wrappers like iu:// from offload plugins
			if ( $basedir && ( ! function_exists( 'wp_is_stream' ) || ! wp_is_stream( $basedir ) ) ) {
				$temp_dir = $basedir . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
			}
		}

		// Final fallback to a guaranteed local path under WP_CONTENT_DIR or system temp
		if ( $temp_dir === '' || ( function_exists( 'wp_is_stream' ) && wp_is_stream( $temp_dir ) ) ) {
			if ( defined( 'WP_CONTENT_DIR' ) && WP_CONTENT_DIR ) {
				$temp_dir = rtrim( WP_CONTENT_DIR, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
			} else {
				$temp_dir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'simply-static' . DIRECTORY_SEPARATOR . 'temp-files';
			}
		}

		// Ensure directory exists
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		return trailingslashit( $temp_dir );
	}

	/**
	 * Check whether a directory is safe for recursive content deletion.
	 *
	 * This rejects filesystem roots, symlinks, WordPress/application roots, upload
	 * and system-temp roots, and common user-data roots. Descendant directories
	 * (such as uploads/simply-static/temp-files) remain valid.
	 *
	 * @param string $dir Directory path.
	 *
	 * @return bool
	 */
	public static function is_safe_directory_to_delete( $dir ) {
		if ( ! is_string( $dir ) || '' === trim( $dir ) || false !== strpos( $dir, "\0" ) ) {
			return false;
		}

		if ( ( function_exists( 'wp_is_stream' ) && wp_is_stream( $dir ) ) || is_link( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		$real_path = realpath( $dir );
		if ( false === $real_path ) {
			return false;
		}

		$real_path = rtrim( str_replace( '\\', '/', $real_path ), '/' );
		if ( '' === $real_path || preg_match( '#^(?:[A-Za-z]:)?$#', $real_path ) ) {
			return false;
		}

		$protected = array(
			defined( 'ABSPATH' ) ? ABSPATH : '',
			defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '',
			defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '',
			sys_get_temp_dir(),
		);

		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = wp_upload_dir();
			if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) ) {
				$protected[] = $uploads['basedir'];
			}
		}

		$home = getenv( 'HOME' );
		if ( is_string( $home ) && '' !== $home ) {
			$protected[] = $home;
			$protected[] = $home . '/Desktop';
			$protected[] = $home . '/Documents';
			$protected[] = $home . '/Downloads';
		}

		$is_safe = true;
		foreach ( array_filter( $protected ) as $protected_path ) {
			$protected_real = realpath( $protected_path );
			if ( false === $protected_real ) {
				$protected_real = $protected_path;
			}
			$protected_real = rtrim( str_replace( '\\', '/', $protected_real ), '/' );

			if ( $real_path === $protected_real || 0 === strpos( $protected_real . '/', $real_path . '/' ) ) {
				$is_safe = false;
				break;
			}
		}

		return (bool) apply_filters( 'ss_is_safe_directory_to_delete', $is_safe, $real_path, $protected );
	}

	/**
	 * Recursively delete contents of a directory but keep the directory itself.
	 *
	 * Rules:
	 * - No error suppression operators (@). We perform checks before FS calls to avoid warnings.
	 * - Very defensive: do nothing for empty/non-dirs and for very shallow paths.
	 *
	 * @param string $dir Absolute path to the directory whose contents should be cleared.
	 *
	 * @return void
	 */
	public static function delete_dir_contents( string $dir ): void {
		$dir = (string) $dir;
		if ( ! self::is_safe_directory_to_delete( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( $items === false ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) && ! is_link( $path ) ) {
				self::delete_dir_contents( $path );
				// Remove the now-empty directory if possible.
				if ( is_dir( $path ) && is_writable( $path ) ) {
					rmdir( $path );
				}
			} else {
				// Files or links
				if ( ( is_file( $path ) || is_link( $path ) ) && ( file_exists( $path ) || is_link( $path ) ) ) {
					if ( is_writable( $path ) ) {
						unlink( $path );
					}
				}
			}
		}
	}

	/**
	 * Normalize a URL to handle URL-encoded characters in the path.
	 *
	 * This function addresses issues with posts that have URL-encoded values
	 * in their post_name (e.g., "bedo%cc%88" instead of "bedö"). WordPress's
	 * get_permalink() may double-encode these values, causing the URLs to fail
	 * during static export.
	 *
	 * The function ONLY processes URLs that have double-encoded characters
	 * (containing %25 which is an encoded %). Other URLs are returned unchanged
	 * to avoid corrupting properly formatted URLs.
	 *
	 * @param string $url The URL to normalize.
	 *
	 * @return string The normalized URL.
	 */
	public static function normalize_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		$parsed = parse_url( $url );
		if ( $parsed === false || ! isset( $parsed['path'] ) ) {
			return $url;
		}

		// Only process URLs that have double-encoded characters (%25 is an encoded %)
		// This prevents corrupting URLs that are already correctly formatted
		if ( strpos( $parsed['path'], '%25' ) === false ) {
			return $url;
		}

		// Decode the path to handle any double-encoding
		// We decode twice to handle cases where % was encoded as %25
		$decoded_path = urldecode( urldecode( $parsed['path'] ) );

		// Re-encode the path properly, but preserve slashes
		$path_segments    = explode( '/', $decoded_path );
		$encoded_segments = array_map( function ( $segment ) {
			// Use rawurlencode but restore characters that are valid in URL paths
			// and commonly used in filenames (commas, tildes, etc.)
			$encoded = rawurlencode( $segment );
			// Restore safe characters that rawurlencode encodes but are valid in paths
			// RFC 3986 sub-delims: !$&'()*+,;= and unreserved: -._~
			$encoded = str_replace(
				array( '%2C', '%7E', '%21', '%27', '%28', '%29', '%2A', '%40' ),
				array( ',', '~', '!', "'", '(', ')', '*', '@' ),
				$encoded
			);

			return $encoded;
		}, $path_segments );
		$normalized_path  = implode( '/', $encoded_segments );

		// Rebuild the URL
		$normalized_url = '';
		if ( isset( $parsed['scheme'] ) ) {
			$normalized_url .= $parsed['scheme'] . '://';
		}
		if ( isset( $parsed['host'] ) ) {
			$normalized_url .= $parsed['host'];
		}
		if ( isset( $parsed['port'] ) ) {
			$normalized_url .= ':' . $parsed['port'];
		}
		$normalized_url .= $normalized_path;
		if ( isset( $parsed['query'] ) ) {
			$normalized_url .= '?' . $parsed['query'];
		}
		if ( isset( $parsed['fragment'] ) ) {
			$normalized_url .= '#' . $parsed['fragment'];
		}

		return $normalized_url;
	}
}
