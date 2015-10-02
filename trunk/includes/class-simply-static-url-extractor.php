<?php
/**
 * @package Simply_Static
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The following pages were incredibly helpful:
 * - http://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_web_page
 * - http://nadeausoftware.com/articles/2008/01/php_tip_how_extract_urls_css_file
 * - http://php.net/manual/en/book.dom.php
 */

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
            $this->extract_urls_from_html();
        }

		return array_unique( $this->extracted_urls );
	}

    private function starts_with( $haystack, $needle ) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function extract_urls_from_html() {
        $parsed_page_url = parse_url( $this->response->url );

        $doc = new DOMDocument();
        libxml_use_internal_errors( true );
        $doc->loadHTML( $this->response->body );
        libxml_use_internal_errors( false );
        // get all elements on the page
        $elements = $doc->getElementsByTagName( '*' );
        foreach ( $elements as $element ) {
            $tag = $element->tagName;

            if ( array_key_exists( $tag, self::$match_elements ) ) {
                $match_attributes = self::$match_elements[$tag];
                foreach ( $match_attributes as $attribute_name ) {
                    $extracted_url = $element->getAttribute( $attribute_name );
                    if ( $extracted_url !== '' ) {
                        $extracted_url = trim( $extracted_url );
                        $parsed_extracted_url = parse_url( $extracted_url );

                        // parse_url can sometimes return false -- checking that they're both not false
                        if ( $parsed_page_url && $parsed_extracted_url ) {
                            // if the extracted url has a host
                            if ( array_key_exists( 'host', $parsed_extracted_url ) ) {
                                // and a scheme
                                if ( array_key_exists( 'scheme', $parsed_extracted_url ) ) {
                                    // and that scheme+host matches the scheme+host of the page we extracted it from
                                    if ( $parsed_page_url['scheme'] === $parsed_extracted_url['scheme']
                                    && $parsed_page_url['host'] === $parsed_extracted_url['host'] ) {
                                        $extracted_url = phpUri::parse( $this->response->url )->join( $parsed_extracted_url['path'] );
                                        $this->extracted_urls[] = $extracted_url;
                                    }
                                }
                            } else { // no host on extracted page (might be relative url)
                                // filter out anything with a scheme, e.g. java:, data:, etc.)
                                // also checking for a path (some links might only have a fragent, e.g. #section1)
                                // and that the path is not just a slash
                                if ( ! array_key_exists( 'scheme', $parsed_extracted_url ) && array_key_exists( 'path', $parsed_extracted_url ) ) {
                                    // turn our relative url into an absolute url
                                    $extracted_url = phpUri::parse( $this->response->url )->join( $parsed_extracted_url['path'] );
                                    $this->extracted_urls[] = $extracted_url;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
