<?php

declare(strict_types=1);

namespace {
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	if ( ! function_exists( 'wp_remote_head' ) ) {
		/**
		 * Test double for the WordPress HTTP HEAD helper.
		 *
		 * @param string               $url  Request URL.
		 * @param array<string,mixed>  $args Request arguments.
		 *
		 * @return mixed
		 */
		function wp_remote_head( $url, $args = array() ) {
			WpEnv::$remote_requests[] = array(
				'method' => 'HEAD',
				'url'    => $url,
				'args'   => $args,
			);

			return WpEnv::$remote_response;
		}
	}
}

namespace Simply_Static\Tests\Unit {

	use Simply_Static\Crawler\Sitemap_Crawler;
	use Simply_Static\Tests\Support\UnitTestCase;
	use Simply_Static\Tests\Support\WpTestEnvironment as WpEnv;

	final class SitemapCrawlerTest extends UnitTestCase {

		protected function setUp(): void {
			parent::setUp();
			$this->requireSource( 'src/class-ss-plugin.php' );
			$this->requireSource( 'src/class-ss-options.php' );
			$this->requireSource( 'src/class-ss-util.php' );
			$this->requireSource( 'src/crawler/class-ss-crawler.php' );
			$this->requireSource( 'src/crawler/class-ss-sitemap-crawler.php' );

			$this->useOnlyCommonSitemap( 'https://example.test/sitemap.xml' );
		}

		protected function tearDown(): void {
			libxml_clear_errors();
			libxml_use_internal_errors( false );
			parent::tearDown();
		}

		public function test_it_rejects_non_local_sitemap_documents_before_any_request(): void {
			$this->useOnlyCommonSitemap( 'https://attacker.test/sitemap.xml' );
			$this->setXmlResponse( $this->urlSet( array( 'https://example.test/page/' ) ) );

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertSame( array(), WpEnv::$remote_requests );
		}

		public function test_it_rejects_remote_and_alternate_origin_child_documents_before_requesting_them(): void {
			$this->setXmlResponse(
				$this->sitemapIndex(
					array(
						'https://example.test/child.xml',
						'https://attacker.test/private.xml',
						'https://example.test:8443/internal.xml',
						'http://example.test/insecure.xml',
					)
				)
			);

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );

			$get_urls = $this->requestUrls( 'GET' );
			self::assertSame(
				array(
					'https://example.test/sitemap.xml',
					'https://example.test/child.xml',
				),
				$get_urls
			);
		}

		public function test_it_returns_only_local_same_origin_page_urls_and_strips_fragments(): void {
			$this->setXmlResponse(
				$this->urlSet(
					array(
						'https://example.test/allowed/?q=one#fragment',
						'https://attacker.test/not-allowed/',
						'https://example.test:8443/not-allowed/',
						'http://example.test/not-allowed/',
					)
				)
			);

			self::assertSame(
				array( 'https://example.test/allowed/?q=one' ),
				( new Sitemap_Crawler() )->detect()
			);
		}

		public function test_it_uses_a_visited_set_to_stop_cyclic_indexes(): void {
			$this->setXmlResponse(
				$this->sitemapIndex( array( 'https://example.test/sitemap.xml#again' ) )
			);

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertSame( array( 'https://example.test/sitemap.xml' ), $this->requestUrls( 'GET' ) );
		}

		public function test_it_honors_the_depth_cap(): void {
			add_filter(
				'simply_static_sitemap_max_depth',
				static function () {
					return 0;
				}
			);
			$this->setXmlResponse(
				$this->sitemapIndex( array( 'https://example.test/child.xml' ) )
			);

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertSame( array( 'https://example.test/sitemap.xml' ), $this->requestUrls( 'GET' ) );
		}

		public function test_it_honors_the_document_cap(): void {
			add_filter(
				'simply_static_sitemap_max_documents',
				static function () {
					return 2;
				}
			);
			$this->setXmlResponse(
				$this->sitemapIndex(
					array(
						'https://example.test/one.xml',
						'https://example.test/two.xml',
						'https://example.test/three.xml',
					)
				)
			);

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertSame(
				array( 'https://example.test/sitemap.xml', 'https://example.test/one.xml' ),
				$this->requestUrls( 'GET' )
			);
		}

		public function test_it_honors_the_discovered_url_cap(): void {
			add_filter(
				'simply_static_sitemap_max_urls',
				static function () {
					return 2;
				}
			);
			$this->setXmlResponse(
				$this->urlSet(
					array(
						'https://example.test/one/',
						'https://example.test/two/',
						'https://example.test/three/',
					)
				)
			);

			self::assertSame(
				array( 'https://example.test/one/', 'https://example.test/two/' ),
				( new Sitemap_Crawler() )->detect()
			);
		}

		public function test_it_stops_discovery_when_the_total_time_budget_is_exhausted(): void {
			add_filter( 'simply_static_sitemap_max_scan_seconds', static function () {
				return 1;
			} );
			$crawler = new class extends Sitemap_Crawler {
				/** @var float[] */
				private $times = array( 0.0, 2.0 );

				protected function now() {
					return ! empty( $this->times ) ? array_shift( $this->times ) : 2.0;
				}
			};

			self::assertSame( array(), $crawler->detect() );
			self::assertSame( array(), WpEnv::$remote_requests );
		}

		public function test_it_applies_timeout_and_response_size_limits_and_rejects_oversized_bodies(): void {
			add_filter(
				'simply_static_sitemap_request_timeout',
				static function () {
					return 2.5;
				}
			);
			add_filter(
				'simply_static_sitemap_max_response_size',
				static function () {
					return 64;
				}
			);
			$this->setXmlResponse( $this->urlSet( array( 'https://example.test/a-very-long-page-name/' ) ) );

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );

			$head = $this->requestsByMethod( 'HEAD' )[0];
			$get  = $this->requestsByMethod( 'GET' )[0];
			self::assertSame( 2.5, $head['args']['timeout'] );
			self::assertSame( 0, $head['args']['redirection'] );
			self::assertTrue( $head['args']['sslverify'] );
			self::assertSame( 2.5, $get['args']['timeout'] );
			self::assertSame( 0, $get['args']['redirection'] );
			self::assertTrue( $get['args']['sslverify'] );
			self::assertSame( 65, $get['args']['limit_response_size'] );
		}

		public function test_it_restores_libxml_error_state_after_malformed_xml(): void {
			$this->setXmlResponse( '<urlset><url><loc>broken' );

			libxml_use_internal_errors( false );
			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertFalse( libxml_use_internal_errors() );

			WpEnv::$remote_requests = array();
			libxml_use_internal_errors( true );
			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertTrue( libxml_use_internal_errors() );
		}

		public function test_it_rejects_dtd_and_external_entity_documents(): void {
			$this->setXmlResponse(
				'<?xml version="1.0"?>'
				. '<!DOCTYPE urlset [<!ENTITY remote SYSTEM "http://127.0.0.1:9/private">]>'
				. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
				. '<url><loc>&remote;</loc></url></urlset>'
			);

			self::assertSame( array(), ( new Sitemap_Crawler() )->detect() );
			self::assertSame( array( 'https://example.test/sitemap.xml' ), $this->requestUrls( 'GET' ) );
		}

		private function useOnlyCommonSitemap( string $url ): void {
			add_filter(
				'simply_static_sitemap_common_urls',
				static function () use ( $url ) {
					return array( $url );
				}
			);
		}

		private function setXmlResponse( string $xml, int $status = 200 ): void {
			WpEnv::$remote_response = array(
				'response' => array( 'code' => $status ),
				'headers'  => array(),
				'body'     => $xml,
			);
		}

		/**
		 * @param string[] $urls
		 */
		private function sitemapIndex( array $urls ): string {
			$items = '';
			foreach ( $urls as $url ) {
				$items .= '<sitemap><loc>' . htmlspecialchars( $url, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</loc></sitemap>';
			}

			return '<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
				. $items . '</sitemapindex>';
		}

		/**
		 * @param string[] $urls
		 */
		private function urlSet( array $urls ): string {
			$items = '';
			foreach ( $urls as $url ) {
				$items .= '<url><loc>' . htmlspecialchars( $url, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</loc></url>';
			}

			return '<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
				. $items . '</urlset>';
		}

		/** @return array<int,array<string,mixed>> */
		private function requestsByMethod( string $method ): array {
			return array_values(
				array_filter(
					WpEnv::$remote_requests,
					static function ( array $request ) use ( $method ): bool {
						return $method === $request['method'];
					}
				)
			);
		}

		/** @return string[] */
		private function requestUrls( string $method ): array {
			return array_map(
				static function ( array $request ): string {
					return $request['url'];
				},
				$this->requestsByMethod( $method )
			);
		}
	}
}
