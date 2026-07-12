import {
	getSafeLogUrl,
	parseActivityLogMessage,
	parseLogResponse,
	toInertLogText,
} from './log';

describe( 'log utilities', () => {
	describe( 'parseLogResponse', () => {
		it( 'accepts object and JSON-string REST responses', () => {
			const response = { status: 200, data: { total: 1 } };

			expect( parseLogResponse( response ) ).toBe( response );
			expect( parseLogResponse( JSON.stringify( response ) ) ).toEqual(
				response
			);
		} );

		it( 'rejects invalid or scalar REST responses', () => {
			expect( parseLogResponse( '{invalid' ) ).toBeNull();
			expect( parseLogResponse( 'false' ) ).toBeNull();
			expect( parseLogResponse( null ) ).toBeNull();
		} );
	} );

	describe( 'toInertLogText', () => {
		it( 'removes legacy markup while preserving readable text', () => {
			expect(
				toInertLogText(
					'Destination: <a href="https://example.com">Example</a><br>Done'
				)
			).toBe( 'Destination: Example Done' );
		} );

		it( 'returns potentially executable markup only as inert text', () => {
			const text = toInertLogText(
				'<img src=x onerror="alert(1)">Safe<script>alert(2)</script>'
			);

			expect( text ).toBe( 'Safealert(2)' );
			expect( text ).not.toContain( '<img' );
			expect( text ).not.toContain( '<script' );
		} );
	} );

	describe( 'parseActivityLogMessage', () => {
		it( 'extracts only the legacy HTTP(S) completion anchor as data', () => {
			expect(
				parseActivityLogMessage(
					'ZIP archive created: <a href="https://example.com/archive.zip?one=1&#038;two=2">Click here to download</a>'
				)
			).toEqual( {
				before: 'ZIP archive created:',
				after: '',
				link: {
					href: 'https://example.com/archive.zip?one=1&two=2',
					label: 'Click here to download',
				},
			} );

			expect(
				parseActivityLogMessage(
					'Destination URL: <a href="http://static.example.com/" target="_blank" rel="noopener noreferrer">http://static.example.com/</a>'
				).link
			).toEqual( {
				href: 'http://static.example.com/',
				label: 'http://static.example.com/',
			} );
		} );

		it.each( [
			'<a href="javascript:alert(1)">Download</a>',
			'<a href="//evil.example/archive.zip">Download</a>',
			'<a href="https://0@evil.example/archive.zip">Download</a>',
			'<a href="https://example.com/archive.zip" onclick="alert(1)">Download</a>',
			'<a href="https://example.com/archive.zip" href="https://evil.example/">Download</a>',
			'<script>alert(1)</script><a href="https://example.com/archive.zip">Download</a>',
		] )( 'keeps unsafe anchor markup inert: %s', ( message ) => {
			const parsed = parseActivityLogMessage( message );

			expect( parsed.link ).toBeNull();
			expect( parsed.before ).not.toContain( '<' );
			expect( parsed.before ).not.toContain( '>' );
		} );
	} );

	describe( 'getSafeLogUrl', () => {
		it.each( [
			'javascript:alert(1)',
			'data:text/html,<script>alert(1)</script>',
			'vbscript:msgbox(1)',
			'//evil.example/path',
			'https://0@example.com/private',
			'example.com/path',
			'',
		] )( 'rejects unsafe or ambiguous href %s', ( href ) => {
			expect( getSafeLogUrl( href ) ).toBeNull();
		} );

		it( 'accepts HTTP(S) URLs and returns a path-only display label', () => {
			expect(
				getSafeLogUrl( 'https://example.com/path?q=1#result' )
			).toEqual( {
				href: 'https://example.com/path?q=1#result',
				label: '/path?q=1#result',
			} );
			expect( getSafeLogUrl( 'http://example.com/' ) ).toEqual( {
				href: 'http://example.com/',
				label: '/',
			} );
		} );

		it( 'accepts root-relative local paths without accepting protocol-relative URLs', () => {
			expect( getSafeLogUrl( '/local/path' ) ).toEqual( {
				href: '/local/path',
				label: '/local/path',
			} );
			expect( getSafeLogUrl( '//evil.example/path' ) ).toBeNull();
			expect(
				getSafeLogUrl( String.raw`/\evil.example/path` )
			).toBeNull();
		} );
	} );
} );
