<?php
/**
 * @package Simply_Static
 */
?>

<h1><?php _e( 'Simply Static &rsaquo; Diagnostics', Simply_Static::SLUG ); ?></h1>

<div class='wrap' id='diagnosticsPage'>

	<?php foreach ( $this->results as $title => $tests ) : ?>
		<table class='widefat striped'>
			<thead>
				<tr>
					<th colspan='2'><?php echo $title; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tests as $result ) : ?>
					<tr>
						<td class='label'><?php echo $result['label'] ?></td>
						<?php if ( $result['test'] ) : ?>
							<td class='test success'><?php echo $result['message'] ?></td>
						<?php else : ?>
							<td class='test error'><?php echo $result['message'] ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

	<!-- <form id='optionsForm' method='post' action=''>

		<?php // wp_nonce_field( Simply_Static::SLUG ) ?>
		<input type='hidden' name='_diagnostics' value='1' />

		<table class='form-table'>
			<tbody>
				<tr>
					<th></th>
					<td>
						<label>
							<input aria-describedby='enableDebuggingHelpBlock' name='debugging_mode' id='debuggingMode' value='1' type='checkbox' <?php // if ( $this->debugging_mode == '1' ) { echo 'checked'; } ?> />
							<?php // _e( "Enable debugging mode", Simply_Static::SLUG ); ?>
						</label>
						<p id='enableDebuggingHelpBlock' class='help-block'>
							<?php // _e( "This will enable verbose logging in Simply Static.", Simply_Static::SLUG ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

	</form> -->

</div>
<!-- .wrap -->
