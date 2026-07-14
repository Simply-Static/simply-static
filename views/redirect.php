<?php
$redirect_url      = (string) $this->redirect_url;
$redirect_parts    = wp_parse_url( $redirect_url );

// Relative and offline exports intentionally create path-only redirect targets.
// Keep those local by rejecting protocol-relative and backslash-based URLs.
$is_relative_url   = is_array( $redirect_parts )
	&& ! empty( $redirect_parts['path'] )
	&& ! array_key_exists( 'scheme', $redirect_parts )
	&& ! array_key_exists( 'host', $redirect_parts )
	&& ! array_key_exists( 'user', $redirect_parts )
	&& ! array_key_exists( 'pass', $redirect_parts )
	&& 0 !== strpos( $redirect_url, '//' )
	&& false === strpos( $redirect_url, '\\' )
	&& ! preg_match( '/[\x00-\x20\x7F]/', $redirect_url );

if ( ! $is_relative_url ) {
	$redirect_url   = esc_url_raw( $redirect_url );
	$redirect_parts = wp_parse_url( $redirect_url );

	if (
		! is_array( $redirect_parts )
		|| empty( $redirect_parts['host'] )
		|| empty( $redirect_parts['scheme'] )
		|| ! in_array( strtolower( $redirect_parts['scheme'] ), array( 'http', 'https' ), true )
		|| array_key_exists( 'user', $redirect_parts )
		|| array_key_exists( 'pass', $redirect_parts )
	) {
		$redirect_url = '';
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo esc_html__( 'Redirecting...', 'simply-static' ); ?></title>
			<?php if ( $redirect_url ) : ?>
				<meta http-equiv="refresh" content="0;url=<?php echo esc_attr( $redirect_url ); ?>">
			<?php endif; ?>
	</head>
	<body>
			<?php if ( $redirect_url ) : ?>
				<script type="text/javascript">
					window.location.assign(<?php echo wp_json_encode( $redirect_url ); ?>);
				</script>
			<?php endif; ?>

			<?php if ( $redirect_url ) : ?>
				<p><?php echo wp_kses_post( sprintf( __( 'You are being redirected to %s', 'simply-static' ), '<a href="' . esc_attr( $redirect_url ) . '">' . esc_html( $redirect_url ) . '</a>' ) ); ?></p>
			<?php else : ?>
				<p><?php echo esc_html__( 'The redirect target is invalid.', 'simply-static' ); ?></p>
			<?php endif; ?>
	</body>
</html>
