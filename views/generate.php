<?php
namespace Simply_Static;
?>

<h1><?php _e( 'Simply Static &rsaquo; Generate', 'simply-static' ); ?></h1>

<div class='wrap' id='generatePage'>

	<?php
    wp_nonce_field( 'simply-static_generate', 'simply_static_nonce' );

    if ( is_multisite() && is_network_admin() ) {
	    $sites = get_sites([
            'public' => true // Only public so we can retrieve URLs.
        ]);

        ?>
        <p>
            <label for="simply_static_blog_id">
                <?php esc_html_e( 'Generate Static Site for:', 'simply-static' ); ?>
            </label>
            <select id="simply_static_blog_id" name="simply_static_blog_id">
                <?php
                /** @var \WP_Site $site */
                foreach ( $sites as $site ) {
                    ?>
                    <option value="<?php echo esc_attr( $site->blog_id ); ?>"><?php echo esc_attr( $site->blogname ); ?> (<?php echo esc_attr( $site->siteurl ); ?>)</option>
                    <?php
                }
                ?>
            </select>
            <?php
                if ( 'no' !== $this->allow_subsites ) {
                    ?>
                    <div>
                        <label>
                            <input type="radio" name="simply_static_use_settings" id="simply_static_use_settings_network" value="network" />
                            Use Network Settings
                        </label>
                    </div>
                    <div>
                        <label>
                            <input checked="checked" type="radio" name="simply_static_use_settings" id="simply_static_use_settings_site" value="site" />
                            Use Site's Settings
                        </label>
                    </div>


                    <?php
                }
            ?>
        </p>
        <?php
    } else {
        ?>
        <input type="hidden" name="simply_static_blog_id" value="<?php echo esc_attr( get_current_blog_id() ); ?>" />
        <?php
    }
    ?>

	<div class='actions'>
		<input id='generate' class='button button-primary button-hero <?php if ( ! $this->archive_generation_done ) { echo 'hide'; } ?>' type='submit' name='generate' value='<?php _e( "Generate Static Files", 'simply-static' ); ?>' />

		<input id='cancel' class='button button-cancel button-hero <?php if ( $this->archive_generation_done ) { echo 'hide'; } ?>' type='submit' name='cancel' value='<?php _e( "Cancel", 'simply-static' ); ?>' />

		<span class='spinner <?php if ( ! $this->archive_generation_done ) { echo 'is-active'; } ?>'></span>
	</div>

	<h3><?php _e( "Activity Log", 'simply-static' ); ?></h3>
	<div id='activityLog'>
		<?php echo $this->activity_log; ?>
	</div>

	<h3><?php _e( "Export Log", 'simply-static' ); ?></h3>
	<div id='exportLog'>
		<?php echo $this->export_log; ?>
	</div>

</div>
