<?php if ( is_array( $this->static_pages ) && count( $this->static_pages ) ) : ?>
	<?php $http_codes = array( '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0 ); ?>
	<?php $num_errors = count( array_filter( $this->static_pages, function($p) { return $p->error_message != false; } ) ); ?>

	<table class='widefat striped'>
		<thead>
			<tr>
				<th><?php _e( 'Code', Simply_Static::SLUG ); ?></th>
				<th><?php _e( 'URL', Simply_Static::SLUG ); ?></th>
				<th><?php _e( 'Found on', Simply_Static::SLUG ); ?></th>
				<?php if ( $num_errors > 0 ) : ?>
				<th><?php echo sprintf( __( "Errors (%d)", Simply_Static::SLUG ), $num_errors ); ?></th>
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
			<?php
				$code = $static_page->http_status_code;
				if ( $code !== null ) {
					$digit = substr( $static_page->http_status_code, 0, 1 ); // get first digit of error code
					$http_codes[ $digit ] += 1;
				}
			?>

			<tr>
				<td class='status-code'><?php echo $static_page->http_status_code; ?></td>
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
						<?php echo __( $msg, Simply_Static::SLUG ); ?>
					<?php endif; ?>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>


	<p><?php _e( '1xx Informational:', Simply_Static::SLUG ); ?> <b><?php echo $http_codes['1']; ?></b> |
		<?php _e( '2xx Success:', Simply_Static::SLUG ); ?> <b><?php echo $http_codes['2']; ?></b> |
		<?php _e( '3xx Redirection:', Simply_Static::SLUG ); ?> <b><?php echo $http_codes['3']; ?></b> |
		<?php _e( '4xx Client Error:', Simply_Static::SLUG ); ?> <b><?php echo $http_codes['4']; ?></b> |
		<?php _e( '5xx Server Error:', Simply_Static::SLUG ); ?> <b><?php echo $http_codes['5']; ?></b> |
		<?php echo sprintf( __( "More information on HTTP status codes is available on <a href='%s'>Wikipedia</a>.", Simply_Static::SLUG ), 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes' ); ?></p>
	<hr />
<?php endif ?>
