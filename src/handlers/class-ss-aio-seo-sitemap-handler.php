<?php

namespace Simply_Static;

class AIO_SEO_Sitemap_Handler extends Page_Handler {

	/**
	 * Serve the static stylesheet while Simply Static fetches its queued page.
	 *
	 * AIOSEO 5.x hard-codes default-sitemap.xsl in generated sitemap XML and no
	 * longer exposes the old stylesheet filter. Simply Static therefore provides
	 * one stable stylesheet endpoint of its own and rewrites every exported
	 * sitemap to reference it.
	 *
	 * @return void
	 */
	public function run_hooks() {
		parent::run_hooks();

		$page_url = isset( $this->page->url ) ? (string) $this->page->url : '';
		$page_path = wp_parse_url( $page_url, PHP_URL_PATH );

		if ( 'main-sitemap.xsl' !== basename( (string) $page_path ) ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'output_stylesheet' ), 0 );
	}

	/**
	 * Output the generated stylesheet endpoint.
	 *
	 * @return void
	 */
	public function output_stylesheet() {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/xsl; charset=UTF-8' );
		}

		echo self::stylesheet_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Update sitemap files after each fetch.
	 *
	 * The stylesheet page is registered after all known sitemap pages, so its
	 * final handler pass also catches indexes or paginated sitemap files that
	 * were fetched after an earlier sitemap handler ran.
	 *
	 * @param string $destination_dir Destination directory.
	 * @return void
	 */
	public function after_file_fetch( $destination_dir ) {
		$this->fix_sitemap_xsl_references( $destination_dir );
		$this->replace_urls_in_sitemaps( $destination_dir );
	}

	/**
	 * Return a browser-compatible stylesheet for sitemap indexes and URL sets.
	 *
	 * @return string
	 */
	public static function stylesheet_content() {
		return <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
	exclude-result-prefixes="sitemap image">
	<xsl:output method="html" encoding="UTF-8" indent="yes"/>

	<xsl:template match="/">
		<html>
			<head>
				<meta name="viewport" content="width=device-width, initial-scale=1"/>
				<title>XML Sitemap</title>
				<style>
					body{margin:0;background:#f7f8fa;color:#24292f;font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
					main{max-width:1100px;margin:48px auto;padding:0 24px}
					h1{margin:0 0 8px;font-size:28px;font-weight:600}
					p{margin:0 0 24px;color:#57606a}
					.table-wrap{overflow-x:auto;border:1px solid #d0d7de;border-radius:8px;background:#fff}
					table{width:100%;border-collapse:collapse}
					th,td{padding:12px 16px;border-bottom:1px solid #d8dee4;text-align:left;vertical-align:top}
					th{background:#f6f8fa;font-weight:600;white-space:nowrap}
					tr:last-child td{border-bottom:0}
					a{color:#0969da;text-decoration:none;overflow-wrap:anywhere}
					a:hover{text-decoration:underline}
					.number{text-align:right;white-space:nowrap}
				</style>
			</head>
			<body>
				<main>
					<h1>XML Sitemap</h1>
					<xsl:choose>
						<xsl:when test="sitemap:sitemapindex">
							<p>This sitemap index contains <xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/> sitemaps.</p>
							<div class="table-wrap">
								<table>
									<thead><tr><th>URL</th><th>Last modified</th></tr></thead>
									<tbody>
										<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
											<tr>
												<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
												<td><xsl:value-of select="sitemap:lastmod"/></td>
											</tr>
										</xsl:for-each>
									</tbody>
								</table>
							</div>
						</xsl:when>
						<xsl:otherwise>
							<p>This sitemap contains <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs.</p>
							<div class="table-wrap">
								<table>
									<thead><tr><th>URL</th><th class="number">Images</th><th>Last modified</th></tr></thead>
									<tbody>
										<xsl:for-each select="sitemap:urlset/sitemap:url">
											<tr>
												<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
												<td class="number"><xsl:value-of select="count(image:image)"/></td>
												<td><xsl:value-of select="sitemap:lastmod"/></td>
											</tr>
										</xsl:for-each>
									</tbody>
								</table>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</main>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
XSL;
	}

	/**
	 * Point all exported sitemap documents at the queued static stylesheet.
	 *
	 * @param string $destination_dir Destination directory.
	 * @return void
	 */
	protected function fix_sitemap_xsl_references( $destination_dir ) {
		$stylesheet_url = trailingslashit( Options::instance()->get_destination_url() ) . 'main-sitemap.xsl';
		$replacement    = '<?xml-stylesheet type="text/xsl" href="' . $stylesheet_url . '"?>';

		foreach ( $this->get_sitemap_files( $destination_dir ) as $sitemap_file ) {
			$content = @file_get_contents( $sitemap_file );
			if ( ! is_string( $content ) || '' === $content ) {
				continue;
			}

			$updated = preg_replace(
				'/<\?xml-stylesheet\b[^?]*\bhref\s*=\s*(["\']).*?\1[^?]*\?>/i',
				$replacement,
				$content
			);

			if ( is_string( $updated ) && $updated !== $content ) {
				if ( false === @file_put_contents( $sitemap_file, $updated ) ) {
					Util::debug_log( 'Cannot update AIOSEO XSL reference in ' . $sitemap_file );
					continue;
				}

				Util::debug_log( 'Updated AIOSEO XSL reference in ' . $sitemap_file );
			}
		}
	}

	/**
	 * Replace origin URLs in every exported sitemap document.
	 *
	 * @param string $destination_dir Destination directory.
	 * @return void
	 */
	protected function replace_urls_in_sitemaps( $destination_dir ) {
		$destination_url = rtrim( (string) Options::instance()->get_destination_url(), '/' );
		if ( '' === $destination_url ) {
			return;
		}

		$pattern = '/(?:(https?:)?\/\/)' . Util::origin_host_pattern() . '/i';

		foreach ( $this->get_sitemap_files( $destination_dir ) as $sitemap_file ) {
			$content = @file_get_contents( $sitemap_file );
			if ( ! is_string( $content ) || '' === $content ) {
				continue;
			}

			$updated = preg_replace( $pattern, $destination_url, $content );
			if ( is_string( $updated ) && $updated !== $content ) {
				if ( false === @file_put_contents( $sitemap_file, $updated ) ) {
					Util::debug_log( 'Cannot replace URLs in AIOSEO sitemap ' . $sitemap_file );
					continue;
				}

				Util::debug_log( 'Updated URLs in AIOSEO sitemap ' . $sitemap_file );
			}
		}
	}

	/**
	 * Return all root-level sitemap XML files, including paginated names such as
	 * post-sitemap2.xml.
	 *
	 * @param string $destination_dir Destination directory.
	 * @return string[]
	 */
	private function get_sitemap_files( $destination_dir ) {
		$sitemap_files = array(
			Util::combine_path( $destination_dir, '/sitemap.xml' ),
			Util::combine_path( $destination_dir, '/sitemap_index.xml' ),
		);
		$xml_files = glob( Util::combine_path( $destination_dir, '/*-sitemap*.xml' ) );

		if ( is_array( $xml_files ) ) {
			$sitemap_files = array_merge( $sitemap_files, $xml_files );
		}

		return array_values( array_unique( array_filter( $sitemap_files, 'is_file' ) ) );
	}
}
