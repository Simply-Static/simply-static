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

		<table class='form-table'>
			<tbody>
				<tr>
					<th><?php _e( "Debugging Mode", 'simply-static' ); ?></th>
					<td>
						<label>
							<input aria-describedby='enableDebuggingHelpBlock' name='debugging_mode' id='debuggingMode' value='1' type='checkbox' <?php Util::checked_if( $this->debugging_mode === '1' ); ?> />
							<?php _e( "Enable debugging mode", 'simply-static' ); ?>
						</label>
						<p id='enableDebuggingHelpBlock' class='help-block'>
							<?php _e( "When enabled, a debug log will be created when generating static files.", 'simply-static' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<p class='submit'>
							<input class='button button-primary' type='submit' name='save' value='<?php _e( "Save Changes", 'simply-static' );?>' />
						</p>
					</td>
				</tr>
			</tbody>
		</table>

	</form>

	<h3 style='margin-top: 50px;'><?php _e( "Send Report by Email", 'simply-static' ); ?></h3>

	<table class='form-table'>
		<tbody>
			<tr>
				<th></th>
				<td>
					<?php if ( $this->debug_file_exists ) : ?>
						<p><?php echo sprintf( __( "You have created <a href='%s'>a debug log</a>.", 'simply-static' ), $this->debug_file_url ); ?></p>
					<?php else : ?>
						<p><?php _e( "You have not created a debug log yet.", 'simply-static' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>

</div>
<!-- .wrap -->
