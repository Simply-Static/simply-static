<div class='tablenav-pages'>
    <span class='displaying-num'><?php echo sprintf( __( "%d URLs", Simply_Static::SLUG ), $this->total_static_pages );?></span>
    <?php
        $args = array(
            'format' => '?page=%#%',
            'total' => $this->total_pages,
            'current' => $this->current_page,
            'prev_text' => '&lsaquo;',
            'next_text' => '&rsaquo;'
        );
        echo paginate_links( $args );
    ?>
</div>
