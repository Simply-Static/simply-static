<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Support;

final class WpTestEnvironment {

	/** @var array<string,mixed> */
	public static $options = array();

	/** @var array<string,mixed> */
	public static $site_options = array();

	/** @var array<string,mixed> */
	public static $transients = array();

	/** @var array<string,mixed> */
	public static $site_transients = array();

	/** @var array<string,array<int,array<int,array{callback:callable,accepted_args:int}>>> */
	public static $filters = array();

	/** @var array<int,array<string,mixed>> */
	public static $remote_requests = array();

	/** @var mixed */
	public static $remote_response = array(
		'response' => array( 'code' => 200 ),
		'headers'  => array(),
		'body'     => '',
	);

	/** @var array<int,array<string,mixed>> */
	public static $routes = array();

	/** @var string[] */
	public static $action_log = array();

	/** @var array<string,bool> */
	public static $capabilities = array();

	/** @var array<int,array<string,bool>> */
	public static $site_capabilities = array();

	/** @var array<int,mixed> */
	public static $sites = array();

	/** @var mixed */
	public static $post_types = null;

	/** @var mixed */
	public static $taxonomies = null;

	/** @var array<string,string[]> */
	public static $valid_nonces = array();

	/** @var array<int,array{nonce:string,action:string}> */
	public static $nonce_verifications = array();

	/** @var bool */
	public static $is_admin = true;

	/** @var int */
	public static $current_blog_id = 1;

	/** @var int[] */
	public static $blog_stack = array();

	/** @var bool */
	public static $multisite = false;

	/** @var string */
	public static $home_url = 'https://example.test';

	/** @var string */
	public static $site_url = 'https://example.test';

	/** @var array<string,mixed> */
	public static $upload_dir = array();

	/** @var array<int,array{type:string,message:string}> */
	public static $errors = array();

	public static function reset(): void {
		self::$options          = array();
		self::$site_options     = array();
		self::$transients       = array();
		self::$site_transients  = array();
		self::$filters          = array();
		self::$remote_requests  = array();
		self::$remote_response  = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array(),
			'body'     => '',
		);
		self::$routes           = array();
		self::$action_log       = array();
		self::$capabilities     = array();
		self::$site_capabilities = array();
		self::$sites            = array();
		self::$post_types       = null;
		self::$taxonomies       = null;
		self::$valid_nonces     = array();
		self::$nonce_verifications = array();
		self::$is_admin         = true;
		self::$current_blog_id  = 1;
		self::$blog_stack       = array();
		self::$multisite        = false;
		self::$home_url         = 'https://example.test';
		self::$site_url         = 'https://example.test';
		self::$upload_dir       = array(
			'basedir' => sys_get_temp_dir() . '/simply-static-tests/uploads',
			'baseurl' => 'https://example.test/wp-content/uploads',
		);
		self::$errors           = array();
		$_GET                   = array();
		$_POST                  = array();
		$_REQUEST               = array();
		$_COOKIE                = array();
		$_SERVER                = array();
		unset( $GLOBALS['simply_static_test_scheduled_hooks'] );
	}

	/**
	 * @param callable $callback
	 */
	public static function addFilter( string $hook, $callback, int $priority, int $accepted_args ): void {
		self::$filters[ $hook ][ $priority ][] = array(
			'callback'      => $callback,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * @param mixed $value
	 * @param mixed ...$args
	 * @return mixed
	 */
	public static function applyFilters( string $hook, $value, ...$args ) {
		if ( empty( self::$filters[ $hook ] ) ) {
			return $value;
		}

		ksort( self::$filters[ $hook ] );
		foreach ( self::$filters[ $hook ] as $callbacks ) {
			foreach ( $callbacks as $registered ) {
				$arguments = array_slice(
					array_merge( array( $value ), $args ),
					0,
					$registered['accepted_args']
				);
				$value = call_user_func_array( $registered['callback'], $arguments );
			}
		}

		return $value;
	}

	/**
	 * @param mixed ...$args
	 */
	public static function doAction( string $hook, ...$args ): void {
		self::$action_log[] = $hook;

		if ( empty( self::$filters[ $hook ] ) ) {
			return;
		}

		ksort( self::$filters[ $hook ] );
		foreach ( self::$filters[ $hook ] as $callbacks ) {
			foreach ( $callbacks as $registered ) {
				call_user_func_array(
					$registered['callback'],
					array_slice( $args, 0, $registered['accepted_args'] )
				);
			}
		}
	}
}
