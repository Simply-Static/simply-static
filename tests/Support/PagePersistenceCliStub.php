<?php

declare(strict_types=1);

// Loaded only by an isolated CLI test; do not affect the rest of the suite.
final class WP_CLI {
	public static $warnings = array();

	public static function warning( $message ): void {
		self::$warnings[] = $message;
	}
}
