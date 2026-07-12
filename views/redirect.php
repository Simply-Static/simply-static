<?php
$redirect_url   = esc_url_raw( (string) $this->redirect_url );
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
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo esc_html__( 'Redirecting...', 'simply-static' ); ?></title>
			<?php if ( $redirect_url ) : ?>
				<meta http-equiv="refresh" content="0;url=<?php echo esc_attr( esc_url( $redirect_url ) ); ?>">
			<?php endif; ?>
	</head>
	<body>
			<?php if ( $redirect_url ) : ?>
				<script type="text/javascript">
					window.location.assign(<?php echo wp_json_encode( $redirect_url ); ?>);
				</script>
			<?php endif; ?>

			<?php if ( $redirect_url ) : ?>
				<p><?php echo wp_kses_post( sprintf( __( 'You are being redirected to %s', 'simply-static' ), '<a href="' . esc_url( $redirect_url ) . '">' . esc_html( $redirect_url ) . '</a>' ) ); ?></p>
			<?php else : ?>
				<p><?php echo esc_html__( 'The redirect target is invalid.', 'simply-static' ); ?></p>
			<?php endif; ?>
	</body>
</html>
