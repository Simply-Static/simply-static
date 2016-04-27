<?php
/**
 * @package Simply_Static
 */
?>

<h1><?php _e( 'Simply Static &rsaquo; Generate', Simply_Static::SLUG ); ?></h1>

<div class='wrap' id='generatePage'>

	<?php wp_nonce_field( Simply_Static::SLUG ) ?>
	<input type='hidden' name='_generate' value='1' />

	<div class='actions'>
		<input id='generate' class='button button-primary button-hero <?php if ( ! $this->archive_generation_ready_to_start ) { echo 'hide'; } ?>' type='submit' name='generate' value='<?php _e( "Generate Static Files", Simply_Static::SLUG ); ?>' />

		<input id='pause' class='button button-hero hide' type='submit' name='pause' value='<?php _e( "Pause", Simply_Static::SLUG ); ?>' />

		<input id='resume' class='button button-primary button-hero <?php if ( $this->archive_generation_ready_to_start ) { echo 'hide'; } ?>' type='submit' name='resume' value='<?php _e( "Resume", Simply_Static::SLUG ); ?>' />

		<input id='cancel' class='button button-cancel button-hero <?php if ( $this->archive_generation_ready_to_start ) { echo 'hide'; } ?>' type='submit' name='cancel' value='<?php _e( "Cancel", Simply_Static::SLUG ); ?>' />

		<span class='spinner'></span>
	</div>

	<h3><?php _e( "Activity Log", Simply_Static::SLUG ); ?></h3>
	<div id='activityLog'>
		<?php echo $this->activity_log; ?>
	</div>

	<h3><?php _e( "Export Log", Simply_Static::SLUG ); ?></h3>
	<div id='exportLog'>
		<?php echo $this->export_log; ?>
	</div>

</div>
