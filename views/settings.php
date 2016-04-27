<?php
/**
 * @package Simply_Static
 */
?>

<h1><?php _e( 'Simply Static &rsaquo; Settings', Simply_Static::SLUG ); ?></h1>

<div class='wrap' id='settingsPage'>

		<form id='optionsForm' method='post' action=''>

			<?php wp_nonce_field( Simply_Static::SLUG ) ?>
			<input type='hidden' name='_settings' value='1' />

			<h2 id='sistTabs' class='nav-tab-wrapper'>
				<a class='nav-tab' id='general-tab' href='#tab-general'><?php _e( 'General', Simply_Static::SLUG ); ?></a>
				<a class='nav-tab' id='advanced-tab' href='#tab-advanced'><?php _e( 'Advanced', Simply_Static::SLUG ); ?></a>
			</h2>

			<div id='general' class='tab-pane'>

				<table class='form-table'>
					<tbody>
						<tr>
							<th>
								<label for='originHost'><?php _e( "Origin URL", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<select id='originScheme' name='origin_scheme' disabled>
									<option value='<?php echo $this->origin_scheme; ?>'><?php echo $this->origin_scheme; ?>://</option>
								</select>
								<input aria-describedby='originHostHelpBlock' type='text' id='originHost' name='origin_host' value='<?php echo esc_attr( $this->origin_host ) ?>' size='50' disabled />
								<p id='originHostHelpBlock' class='help-block'><?php echo sprintf( __( "This is the URL of your WordPress installation. You can edit the URL on <a href='%s'>the General Settings page</a>.", Simply_Static::SLUG ), admin_url( '/options-general.php' ) ); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for='destinationHost'><?php _e( "Destination URL", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<select id='destinationScheme' name='destination_scheme'>
									<option value='http' <?php sist_selected_if( $this->destination_scheme == 'http' ) ?>>http://</option>
									<option value='https' <?php sist_selected_if( $this->destination_scheme == 'https' ) ?>>https://</option>
								</select>
								<input aria-describedby='destinationHostHelpBlock' type='text' id='destinationHost' name='destination_host' value='<?php echo esc_attr( $this->destination_host ) ?>' size='50' />
								<p id='destinationHostHelpBlock' class='help-block'><?php _e( "This is the URL where your static site will live. When generating your static site, all links to the Origin URL will be replaced with the Destination URL.", Simply_Static::SLUG ); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for='deliveryMethod'><?php _e( "Delivery Method", Simply_Static::SLUG ); ?></label></th>
							<td>
								<select name='delivery_method' id='deliveryMethod'>
									<option value='zip' <?php sist_selected_if( $this->delivery_method == 'zip' ) ?>><?php _e( "ZIP Archive", Simply_Static::SLUG ); ?></option>
									<option value='local' <?php sist_selected_if( $this->delivery_method == 'local' ) ?>><?php _e( "Local Directory", Simply_Static::SLUG ); ?></option>
								</select>
							</td>
						</tr>
						<tr class='delivery-method zip'>
							<th></th>
							<td>
								<p><?php _e( "Saving your static files to a ZIP archive is Simply Static's default delivery method. After generating your static files you will be prompted to download the ZIP archive.", Simply_Static::SLUG ); ?></p>
							</td>
						</tr>
						<tr class='delivery-method local'>
							<th></th>
							<td>
								<p><?php _e( "Saving your static files to a local directory is useful if you want to serve your static files from the same server as your WordPress installation. WordPress can live on a subdomain (e.g. wordpress.example.com) while your static files are served from your primary domain (e.g. www.example.com).", Simply_Static::SLUG ); ?></p>
							</td>
						</tr>
						<tr class='delivery-method local'>
							<th>
								<label for='local_dir'><?php _e( "Local Directory", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<?php $example_local_dir = trailingslashit( untrailingslashit( get_home_path() ) . '_static' ); ?>
								<input aria-describedby='localDirHelpBlock' type='text' id='localDir' name='local_dir' value='<?php echo esc_attr( $this->local_dir ) ?>' class='widefat' />
								<p id='localDirHelpBlock' class='help-block'><?php _e( "This is the directory where your static files will be saved. The directory must exist and be writeable by the webserver.", Simply_Static::SLUG ); ?><br />
								<?php echo sprintf( __( "Example: <code>%s</code>", Simply_Static::SLUG ), $example_local_dir ); ?></p>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<p class='submit'>
									<input class='button button-primary' type='submit' name='save' value='<?php _e( "Save Changes", Simply_Static::SLUG );?>' />
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div id='advanced' class='tab-pane'>

				<table class='form-table'>
					<tbody>
						<tr>
							<th>
								<label for='tempFilesDir'><?php _e( "Temporary Files Directory", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<?php $example_temp_files_dir = trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' );?>
								<input aria-describedby='tempFilesDirHelpBlock' type='text' id='tempFilesDir' name='temp_files_dir' value='<?php echo esc_attr( $this->temp_files_dir ) ?>' class='widefat' />
								<p id='tempFilesDirHelpBlock' class='help-block'><?php _e( "Your static files (and ZIP archives, if generated) are temporarily saved to this directory. This directory must exist and be writeable.", Simply_Static::SLUG ); ?><br />
								<?php echo sprintf( __( "Default: <code>%s</code>", Simply_Static::SLUG ), $example_temp_files_dir ); ?></p>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<label>
									<input aria-describedby='deleteTempFilesHelpBlock' name='delete_temp_files' id='deleteTempFiles' value='1' type='checkbox' <?php if ( $this->delete_temp_files == '1' ) { echo 'checked'; } ?> />
									<?php _e( "Delete temporary files", Simply_Static::SLUG ); ?>
								</label>
								<p id='deleteTempFilesHelpBlock' class='help-block'>
									<?php _e( "Static files are temporarily saved to the directory above before being copied to their destination. These files can be deleted after the copy process, or you can keep them as a backup.", Simply_Static::SLUG ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<label for='additionalUrls'><?php _e( "Additional URLs", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<textarea aria-describedby='additionalUrlsHelpBlock' class='widefat' name='additional_urls' id='additionalUrls' rows='5' cols='10'><?php echo esc_html( $this->additional_urls ) ?></textarea>
								<p id='additionalUrlsHelpBlock' class='help-block'>
									<?php echo sprintf( __( "Simply Static will create a static copy of any page it can find a link to, starting at %s. If you want to create static copies of pages or files that <em>aren't</em> linked to, add the URLs here (one per line).", Simply_Static::SLUG ), sist_origin_url() ); ?><br />
									<?php echo sprintf( __( "Examples: <code>%s</code> or <code>%s</code>", Simply_Static::SLUG ),
									sist_origin_url() . __( "/hidden-page", Simply_Static::SLUG ),
									sist_origin_url() . __( "/images/secret.jpg", Simply_Static::SLUG ) ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<label for='additionalFiles'><?php _e( "Additional Files and Directories", Simply_Static::SLUG );?></label>
							</th>
							<td>
								<textarea aria-describedby='additionalFilesHelpBlock' class='widefat' name='additional_files' id='additionalFiles' rows='5' cols='10'><?php echo esc_html( $this->additional_files ) ?></textarea>
								<p id='additionalFilesHelpBlock' class='help-block'>
									<?php _e( "Sometimes you may want to include additional files (such as files referenced via AJAX) or directories. Add the paths to those files or directories here (one per line).", Simply_Static::SLUG ); ?><br />
									<?php echo sprintf( __( "Examples: <code>%s</code> or <code>%s</code>", Simply_Static::SLUG ),
									get_home_path() .  __( "additional-directory", Simply_Static::SLUG ),
									trailingslashit( WP_CONTENT_DIR ) .  __( "fancy.pdf", Simply_Static::SLUG ) ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<p class='submit'>
									<input class='button button-primary' type='submit' name='save' value='<?php _e( "Save Changes", Simply_Static::SLUG );?>' />
								</p>
							</td>
						</tr>
					</tbody>
				</table>

			</div>

		</form>

</div>
