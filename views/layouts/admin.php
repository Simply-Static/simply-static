<?php
namespace Simply_Static;
?>

<?php foreach ( $this->flashes as $flash ) : ?>
	<div class="fade <?php echo $flash['type']; ?>">
		<p><strong>
			<?php echo $flash['message']; ?>
		</strong></p>
	</div>
<?php endforeach; ?>

<div class="wrap">
	<div id="sistContainer">

		<div id="sistContent">
			<?php include $this->template; ?>
		</div>
		<!-- .sist-content -->
	</div>
	<!-- .sist-container -->
</div>
<!-- .wrap -->
