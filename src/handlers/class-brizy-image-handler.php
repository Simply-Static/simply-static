<?php

namespace Simply_Static;

class Brizy_Image_Handler extends Page_Handler
{

    public function prepare_url( $url )
    {
        return htmlspecialchars_decode($url);
    }

    public function get_path_info( $path_info ) {

        $media_path = $this->get_path_info_for_url( $this->page->url );

        $path_info['basename']  = $media_path['basename'];
        $path_info['filename']  = $media_path['filename'];
        $path_info['extension'] = $media_path['extension'];

        return $path_info;
    }

    public function get_path_info_for_url( $url ) {

        $parsed_url = parse_url( $this->prepare_url( $url ) );
        $queries    = explode( '&', $parsed_url['query'] );

        foreach ( $queries as $query ) {
            $query_info = explode( '=', $query );
            $query_name = $query_info[0];

            if ( $query_name === 'brizy_media' ) {
                return Util::url_path_info( $query_info[1] );
            }
        }

        return [];
    }

    public function get_relative_dir( $relative_dir ) {
        return 'wp-content/uploads/brizy/';
    }

    public function get_converted_url( $url ) {
        $path_info = $this->get_path_info_for_url( $url );

        return '/' . $this->get_relative_dir('') . $path_info['basename'];
    }
}