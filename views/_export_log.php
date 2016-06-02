<?php if ( is_array( $this->static_pages ) && count( $this->static_pages ) ) : ?>

	<?php $num_errors = count( array_filter( $this->static_pages, function($p) { return $p->error_message != false; } ) ); ?>

	<div class='tablenav top'>
		<?php include '_pagination.php'; ?>
	</div>

	<table class='widefat striped'>
		<thead>
			<tr>
				<th><?php _e( 'Code', 'simply-static' ); ?></th>
				<th><?php _e( 'URL', 'simply-static' ); ?></th>
				<th><?php _e( 'Found on', 'simply-static' ); ?></th>
				<?php if ( $num_errors > 0 ) : ?>
				<th><?php echo sprintf( __( "Errors (%d)", 'simply-static' ), $num_errors ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>

		<?php
			function find_first_match( $static_pages, $id ) {
				foreach ( $static_pages as $static_page ) {
					if ( $static_page->id === $id ) {
						return $static_page;
					}
				}
			}
		?>

		<?php foreach ( $this->static_pages as $static_page ) : ?>
			<tr>
				<?php $processable = in_array( $static_page->http_status_code, Simply_Static_Archive_Creator::$processable_status_codes ); ?>
				<td class='status-code <?php if ( ! $processable ) { echo 'unprocessable'; } ?>'>
					<?php echo $static_page->http_status_code; ?>
				</td>
				<td class='url'><a href='<?php echo $static_page->url; ?>'><?php echo $static_page->url; ?></a></td>
				<?php $parent_static_page = find_first_match( $this->static_pages, $static_page->found_on_id ); ?>
				<td class='found-on'>
					<?php if ( $parent_static_page ): ?>
						<a href='<?php echo $parent_static_page->url; ?>'><?php echo $parent_static_page->url; ?></a>
					<?php else: ?>
						&mdash;
					<?php endif; ?>
				</td>
				<?php if ( $num_errors > 0 ) : ?>
				<td class='error-message'>
					<?php $msg = $static_page->error_message; if ( isset( $msg ) ) : ?>
						<?php echo $msg; ?>
					<?php endif; ?>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class='tablenav bottom'>
		<?php include '_pagination.php'; ?>
	</div>

<?php endif ?>
