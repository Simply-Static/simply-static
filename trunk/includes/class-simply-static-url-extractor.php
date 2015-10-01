<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simply Static URL extractor class
 */
class Simply_Static_Url_Extractor {

    /** @const */
	protected static $match_elements = array(
        // HTML
        'a'              => array( 'href', 'urn' ),
        'base'           => array( 'href' ),
        'form'           => array( 'action', 'data' ),
        'img'            => array( 'src', 'usemap', 'longdesc' ),
        'link'           => array( 'href' ),

        'applet'         => array( 'code', 'codebase', 'archive', 'object' ),
        'area'           => array( 'href' ),
        'body'           => array( 'background', 'credits', 'instructions', 'logo' ),
        'input'          => array( 'src', 'usemap', 'dynsrc', 'lowsrc', 'action', 'formaction' ),

        'blockquote'     => array( 'cite' ),
        'del'            => array( 'cite' ),
        'frame'          => array( 'longdesc', 'src' ),
        'head'           => array( 'profile' ),
        'iframe'         => array( 'longdesc', 'src' ),
        'ins'            => array( 'cite' ),
        'object'         => array( 'archive', 'classid', 'codebase', 'data', 'usemap' ),
        'q'              => array( 'cite' ),
        'script'         => array( 'src' ),

        'audio'          => array( 'src' ),
        'command'        => array( 'icon' ),
        'embed'          => array( 'src', 'code', 'pluginspage' ),
        'event-source'   => array( 'src' ),
        'html'           => array( 'manifest', 'background', 'xmlns' ),
        'source'         => array( 'src' ),
        'video'          => array( 'src', 'poster' ),

        'bgsound'        => array( 'src' ),
        'div'            => array( 'href', 'src' ),
        'ilayer'         => array( 'src' ),
        'img'            => array( 'dynsrc', 'lowsrc' ),
        'table'          => array( 'background' ),
        'td'             => array( 'background' ),
        'th'             => array( 'background' ),
        'layer'          => array( 'src' ),
        'xml'            => array( 'src' ),

        'button'         => array( 'action', 'formaction' ),
        'datalist'       => array( 'data' ),
        'select'         => array( 'data' ),

        'access'         => array( 'path' ),
        'card'           => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
        'go'             => array( 'href' ),
        'option'         => array( 'onpick' ),
        'template'       => array( 'onenterforward', 'onenterbackward', 'ontimer' ),
        'wml'            => array( 'xmlns' )
    );

    // /** @const */
    // protected static $match_metas = array(
    //     'content-base',
    //     'content-location',
    //     'referer',
    //     'location',
    //     'refresh',
    // );

	/**
	 * The URL request response
	 * @var Simply_Static_Url_Response
	 */
	protected $response;

    /**
	 * The url of the site
	 * @var array
	 */
	protected $extracted_urls = array();

    /**
	 * Constructor
	 * @param string $page_contents The contents of the html/css/etc. page/file
	 * @param string $url The url of the site
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}

	/**
	 * Extracts the list of unique URLs
	 * @return array $urls
	 */
	public function extract_urls() {

        if ( $this->response->is_html() ) {
            $this->extracted_urls = $this->extract_urls_from_html();

            // foreach ( $this->extracted_urls as $url ) {
            //    error_log( $url );
            // }
        }


        //$this->remove_external_urls();




        // array_push( $extracted_urls, $this->extract_html_urls();

        $urls = array();
		if (preg_match_all( '/' . str_replace( '/', '\/', $this->response->url ) . '[^"\'#\? ]+/i', $this->response->body, $matches ) ) {
			$urls = array_unique( $matches[0] );
		}

		return $urls;
	}

    private function remove_external_urls() {
        $this->extracted_urls = array_filter( $this->extracted_urls, function( $url ) {
            if ( stripos($url, 'fonts') !== false  ) {
                error_log( $url );
                error_log( '//: ' . $this->starts_with( $url, '//' ) );
                error_log( 'http: ' . $this->starts_with( $url, 'http' ) );
                error_log( 'base: ' . ! $this->starts_with( $url, $this->base_url ) );
            }

            if ( $this->starts_with( $url, '//' )
            || $this->starts_with( $url, 'http' ) && ! $this->starts_with( $url, $this->base_url ) ) {
                return false;
            } else {
                return true;
            }
        });
    }

    private function starts_with( $haystack, $needle ) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function extract_urls_from_html() {
        // Extract all elements

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML( $this->response->body );
        libxml_use_internal_errors(false);
        // get all elements on the page
        $elements = $doc->getElementsByTagName( '*' );
        foreach ( $elements as $element ) {
            $tag = $element->tagName;

            if ( array_key_exists( $tag, self::$match_elements ) ) {
                $match_attributes = self::$match_elements[$tag];
                foreach ( $match_attributes as $attribute_name ) {
                    $url = $element->getAttribute( $attribute_name );
                    if ( $url !== '' ) {
                        // 0. (before this loop) parse the url for the page we're extracting from
                        // 1. the host must either match or not be set
                        //    if the host is a match:
                        //      the url is good
                        //    if the host is not set: (then we're dealing with a relative url)
                        //      2. the scheme must not be set (to filter out java:, data:, etc.)
                        //      3. properly handle ../ and ./ in the path
                        //        ./ can just be removed, ../ goes up a level from the current path
                        //      4. prepend host/+path depending on if the path starts with '/'
                        $url = trim( $url );
                        //error_log( $url . ' -- ' . filter_var( $url, FILTER_VALIDATE_URL ) );

                        error_log( $url . ' ---------------------------------------------------------------------------------------- . ');

                        ob_start();                    // start buffer capture
                        var_dump( parse_url($url) );   // dump the values
                        $contents = ob_get_contents(); // put the buffer into a variable
                        ob_end_clean();                // end capture
                        error_log( $contents );        // log contents of the result of var_dump( $object )

                        $this->extracted_urls[] = $url;
                    }
                }
            }

        }
    }

}
