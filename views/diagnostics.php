<?php
namespace Simply_Static;
?>

<h1><?php _e( 'Simply Static &rsaquo; Diagnostics', Plugin::SLUG ); ?></h1>

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

	<h3 style='margin-top: 50px;'><?php _e( "Debugging Options", 'simply-static' ); ?></h3>

	<form id='optionsForm' method='post' action=''>

		<?php wp_nonce_field( 'simply-static_diagnostics' ) ?>
		<input type='hidden' name='_diagnostics' value='1' />

		<input id='reset_plugin' class='button button-destroy button-hero' type='submit' name='reset_plugin' value='<?php _e( "Reset Plugin", 'simply-static' ); ?>' />
		<p id='resetPluginHelpBlock' class='help-block'>
			<?php _e( "This will reset Simply Static back to the same state as when it was first installed.", 'simply-static' ); ?>
		</p>

		<table class='form-table'>
			<tbody>
				<tr>
					<th></th>
					<td>

					</td>
				</tr>
				<!-- <tr>
					<th><?php _e( "Debugging Options", 'simply-static' ); ?></th>
					<td>
						<label>
							<input aria-describedby='enableDebuggingHelpBlock' name='debugging_mode' id='debuggingMode' value='1' type='checkbox' <?php // if ( $this->debugging_mode == '1' ) { echo 'checked'; } ?> />
							<?php _e( "Enable debugging mode", 'simply-static' ); ?>
						</label>
						<p id='enableDebuggingHelpBlock' class='help-block'>
							<?php _e( "This will enable verbose logging in Simply Static.", 'simply-static' ); ?>
						</p>
					</td>
				</tr> -->
			</tbody>
		</table>

	</form>

</div>
<!-- .wrap -->
