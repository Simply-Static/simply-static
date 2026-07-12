<?php

declare(strict_types=1);

$report    = isset( $argv[1] ) ? $argv[1] : dirname( __DIR__ ) . '/build/logs/clover.xml';
$minimum   = false !== getenv( 'MIN_LINE_COVERAGE' ) ? (float) getenv( 'MIN_LINE_COVERAGE' ) : 35.0;
$contents  = is_file( $report ) ? file_get_contents( $report ) : false;

if ( ! is_string( $contents ) || ! preg_match( '/<metrics\s+([^>]+)\/>\s*<\/project>/s', $contents, $match ) ) {
	fwrite( STDERR, "Unable to read project metrics from Clover report: {$report}\n" );
	exit( 2 );
}

preg_match_all( '/([a-z]+)="([0-9]+)"/', $match[1], $attributes, PREG_SET_ORDER );
$metrics = array();
foreach ( $attributes as $attribute ) {
	$metrics[ $attribute[1] ] = (int) $attribute[2];
}

$statements         = isset( $metrics['statements'] ) ? $metrics['statements'] : 0;
$covered_statements = isset( $metrics['coveredstatements'] ) ? $metrics['coveredstatements'] : 0;
if ( $statements < 1 ) {
	fwrite( STDERR, "Clover report contains no executable lines.\n" );
	exit( 2 );
}

$coverage = ( $covered_statements / $statements ) * 100;
printf( "Line coverage: %.2f%% (%d/%d); required floor: %.2f%%\n", $coverage, $covered_statements, $statements, $minimum );

if ( $coverage + 0.00001 < $minimum ) {
	fwrite( STDERR, "Coverage floor was not met.\n" );
	exit( 1 );
}
