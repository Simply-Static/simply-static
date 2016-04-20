<!DOCTYPE html>
<html>
	<head>
		<title><?php _e( 'Redirecting...', Simply_Static::SLUG ); ?></title>
		<noscript>
			<meta http-equiv="refresh" content="0;url=<?php echo $this->redirect_url; ?>">
		</noscript>

		<script type="text/javascript">
			window.location = "<?php echo $this->redirect_url; ?>";
		</script>
	</head>
	<body>
		<p>You are being redirected to <a href="<?php echo $this->redirect_url; ?>"><?php echo $this->redirect_url; ?></a></p>
	</body>
</html>
