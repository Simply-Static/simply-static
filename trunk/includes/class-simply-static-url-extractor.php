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
        array('element'=>'a',       'attribute'=>'href'),       // 2.0
        array('element'=>'a',       'attribute'=>'urn'),        // 2.0
        array('element'=>'base',    'attribute'=>'href'),       // 2.0
        array('element'=>'form',    'attribute'=>'action'),     // 2.0
        array('element'=>'img',     'attribute'=>'src'),        // 2.0
        array('element'=>'link',    'attribute'=>'href'),       // 2.0

        array('element'=>'applet',  'attribute'=>'code'),       // 3.2
        array('element'=>'applet',  'attribute'=>'codebase'),   // 3.2
        array('element'=>'area',    'attribute'=>'href'),       // 3.2
        array('element'=>'body',    'attribute'=>'background'), // 3.2
        array('element'=>'img',     'attribute'=>'usemap'),     // 3.2
        array('element'=>'input',   'attribute'=>'src'),        // 3.2

        array('element'=>'applet',  'attribute'=>'archive'),    // 4.01
        array('element'=>'applet',  'attribute'=>'object'),     // 4.01
        array('element'=>'blockquote','attribute'=>'cite'),     // 4.01
        array('element'=>'del',     'attribute'=>'cite'),       // 4.01
        array('element'=>'frame',   'attribute'=>'longdesc'),   // 4.01
        array('element'=>'frame',   'attribute'=>'src'),        // 4.01
        array('element'=>'head',    'attribute'=>'profile'),    // 4.01
        array('element'=>'iframe',  'attribute'=>'longdesc'),   // 4.01
        array('element'=>'iframe',  'attribute'=>'src'),        // 4.01
        array('element'=>'img',     'attribute'=>'longdesc'),   // 4.01
        array('element'=>'input',   'attribute'=>'usemap'),     // 4.01
        array('element'=>'ins',     'attribute'=>'cite'),       // 4.01
        array('element'=>'object',  'attribute'=>'archive'),    // 4.01
        array('element'=>'object',  'attribute'=>'classid'),    // 4.01
        array('element'=>'object',  'attribute'=>'codebase'),   // 4.01
        array('element'=>'object',  'attribute'=>'data'),       // 4.01
        array('element'=>'object',  'attribute'=>'usemap'),     // 4.01
        array('element'=>'q',       'attribute'=>'cite'),       // 4.01
        array('element'=>'script',  'attribute'=>'src'),        // 4.01

        array('element'=>'audio',   'attribute'=>'src'),        // 5.0
        array('element'=>'command', 'attribute'=>'icon'),       // 5.0
        array('element'=>'embed',   'attribute'=>'src'),        // 5.0
        array('element'=>'event-source','attribute'=>'src'),    // 5.0
        array('element'=>'html',    'attribute'=>'manifest'),   // 5.0
        array('element'=>'source',  'attribute'=>'src'),        // 5.0
        array('element'=>'video',   'attribute'=>'src'),        // 5.0
        array('element'=>'video',   'attribute'=>'poster'),     // 5.0

        array('element'=>'bgsound', 'attribute'=>'src'),        // Extension
        array('element'=>'body',    'attribute'=>'credits'),    // Extension
        array('element'=>'body',    'attribute'=>'instructions'),//Extension
        array('element'=>'body',    'attribute'=>'logo'),       // Extension
        array('element'=>'div',     'attribute'=>'href'),       // Extension
        array('element'=>'div',     'attribute'=>'src'),        // Extension
        array('element'=>'embed',   'attribute'=>'code'),       // Extension
        array('element'=>'embed',   'attribute'=>'pluginspage'),// Extension
        array('element'=>'html',    'attribute'=>'background'), // Extension
        array('element'=>'ilayer',  'attribute'=>'src'),        // Extension
        array('element'=>'img',     'attribute'=>'dynsrc'),     // Extension
        array('element'=>'img',     'attribute'=>'lowsrc'),     // Extension
        array('element'=>'input',   'attribute'=>'dynsrc'),     // Extension
        array('element'=>'input',   'attribute'=>'lowsrc'),     // Extension
        array('element'=>'table',   'attribute'=>'background'), // Extension
        array('element'=>'td',      'attribute'=>'background'), // Extension
        array('element'=>'th',      'attribute'=>'background'), // Extension
        array('element'=>'layer',   'attribute'=>'src'),        // Extension
        array('element'=>'xml',     'attribute'=>'src'),        // Extension

        array('element'=>'button',  'attribute'=>'action'),     // Forms 2.0
        array('element'=>'datalist','attribute'=>'data'),       // Forms 2.0
        array('element'=>'form',    'attribute'=>'data'),       // Forms 2.0
        array('element'=>'input',   'attribute'=>'action'),     // Forms 2.0
        array('element'=>'select',  'attribute'=>'data'),       // Forms 2.0

        // XHTML
        array('element'=>'html',    'attribute'=>'xmlns'),

        // WML
        array('element'=>'access',  'attribute'=>'path'),       // 1.3
        array('element'=>'card',    'attribute'=>'onenterforward'),// 1.3
        array('element'=>'card',    'attribute'=>'onenterbackward'),// 1.3
        array('element'=>'card',    'attribute'=>'ontimer'),    // 1.3
        array('element'=>'go',      'attribute'=>'href'),       // 1.3
        array('element'=>'option',  'attribute'=>'onpick'),     // 1.3
        array('element'=>'template','attribute'=>'onenterforward'),// 1.3
        array('element'=>'template','attribute'=>'onenterbackward'),// 1.3
        array('element'=>'template','attribute'=>'ontimer'),    // 1.3
        array('element'=>'wml',     'attribute'=>'xmlns'),      // 2.0
    );

    /** @const */
    protected static $match_metas = array(
        'content-base',
        'content-location',
        'referer',
        'location',
        'refresh',
    );

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

        //$urls = $this->extract_urls_from_html();

        //$this->remove_external_urls();


        //foreach ( $this->extracted_urls as $url ) {
        //    var_dump( $url );
        //}

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

    private function extract_html_urls() {
        // Extract all elements


        // Match elements and attributes
        foreach ( self::$match_elements as $match_element )
        {
            $name = $match_element['element'];
            $attr = $match_element['attribute'];
            $pattern = '/^' . $name . '\s.*' . $attr . $value_pattern . '/iu';
            if ( $name == 'object' )
                $split_pattern = '/\s*/u';  // Space-separated URL list
            else if ( $name == 'archive' )
                $split_pattern = '/,\s*/u'; // Comma-separated URL list
            else
                unset( $split_pattern );    // Single URL
            foreach ( $elements as $element )
            {
                if ( !preg_match( $pattern, $element, $match ) )
                    continue;
                $m = empty($match[3]) ? $match[4] : $match[3];
                if ( !isset( $split_pattern ) )
                    $this->extracted_urls[] = $m;
                else
                {
                    $msplit = preg_split( $split_pattern, $m );
                    foreach ( $msplit as $ms )
                        $this->extracted_urls[] = $ms;
                }
            }
        }
    }

}
