global.wp = {
	i18n: {
		__: ( value ) => value,
	},
};

global.options = {
	is_network: false,
	last_export_end: '',
};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '../../hooks/useInterval', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	const React = require( '@wordpress/element' );
	const wrap =
		( tag ) =>
		( { children, ...props } ) =>
			React.createElement( tag, props, children );

	return {
		Dashicon: ( { icon } ) =>
			React.createElement( 'span', { 'data-icon': icon } ),
		Flex: wrap( 'div' ),
		FlexItem: wrap( 'div' ),
		Notice: ( { children } ) =>
			React.createElement( 'div', { role: 'alert' }, children ),
		Spinner: () =>
			React.createElement( 'span', { 'data-testid': 'spinner' } ),
	};
} );

jest.mock( 'react-terminal-ui', () => {
	const React = require( '@wordpress/element' );

	return {
		__esModule: true,
		default: ( { children } ) =>
			React.createElement(
				'div',
				{ 'data-testid': 'terminal' },
				children
			),
		ColorMode: { Dark: 'dark' },
		TerminalOutput: ( { children } ) =>
			React.createElement( 'div', null, children ),
	};
} );

jest.mock( 'react-data-table-component', () => {
	const React = require( '@wordpress/element' );

	return {
		__esModule: true,
		default: ( props ) => {
			const urlColumn = props.columns.find(
				( column ) => column.name === 'URL'
			);
			const notesColumn = props.columns.find(
				( column ) => column.name === 'Notes'
			);
			const rows = props.data.map( ( row, index ) =>
				React.createElement(
					'div',
					{ key: index, 'data-testid': `row-${ index }` },
					React.createElement(
						'div',
						{ 'data-testid': `url-${ index }` },
						urlColumn.selector( row )
					),
					React.createElement(
						'div',
						{ 'data-testid': `notes-${ index }` },
						notesColumn.selector( row )
					)
				)
			);

			return React.createElement(
				'div',
				{
					'data-testid': 'data-table',
					'data-per-page': props.paginationPerPage,
					'data-page': props.paginationDefaultPage,
				},
				React.createElement(
					'button',
					{
						type: 'button',
						onClick: () => props.onChangeRowsPerPage( 50, 2 ),
					},
					'Show 50'
				),
				rows
			);
		},
	};
} );

const {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} = require( '@testing-library/react' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const useInterval = require( '../../hooks/useInterval' ).default;
const { SettingsContext } = require( '../context/SettingsContext' );
const ActivityLog = require( './ActivityLog' ).default;
const ExportLog = require( './ExportLog' ).default;

const activityContext = {
	isRunning: false,
	isResumed: false,
	blogId: 1,
	snapshotRollback: { running: false },
	isRollbackRunning: false,
};

const exportContext = {
	isRunning: false,
	blogId: 1,
	isPro: () => false,
};

function renderWithContext( component, value ) {
	return render(
		<SettingsContext.Provider value={ value }>
			{ component }
		</SettingsContext.Provider>
	);
}

function exportResponse( rows = [], total = rows.length ) {
	return JSON.stringify( {
		status: 200,
		data: {
			static_pages: rows,
			total_static_pages: total,
		},
	} );
}

describe( 'ActivityLog', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		useInterval.mockReset();
	} );

	it( 'renders server-provided markup as inert text', async () => {
		apiFetch.mockResolvedValue(
			JSON.stringify( {
				status: 200,
				data: {
					fetch: {
						datetime: '2026-07-12 10:00:00',
						message:
							'<img src=x onerror="alert(1)">Safe<script>alert(2)</script>',
					},
				},
			} )
		);

		const { container } = renderWithContext(
			<ActivityLog />,
			activityContext
		);

		await screen.findByText( /Safealert\(2\)/ );
		expect( container.querySelector( 'img' ) ).toBeNull();
		expect( container.querySelector( 'script' ) ).toBeNull();
		expect( container.querySelector( '[onerror]' ) ).toBeNull();
	} );

	it( 'reconstructs a validated completion link without injecting server HTML', async () => {
		apiFetch.mockResolvedValue(
			JSON.stringify( {
				status: 200,
				data: {
					create_zip_archive: {
						datetime: '2026-07-12 10:00:00',
						message:
							'ZIP archive created: <a href="https://example.com/archive.zip?token=safe">Click here to download</a>',
					},
				},
			} )
		);

		const { container } = renderWithContext(
			<ActivityLog />,
			activityContext
		);
		const link = await screen.findByRole( 'link', {
			name: 'Click here to download',
		} );

		expect( link.getAttribute( 'href' ) ).toBe(
			'https://example.com/archive.zip?token=safe'
		);
		expect( link.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( link.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );
		expect( screen.getByText( /ZIP archive created:/ ) ).not.toBeNull();
		expect( container.querySelector( '[onclick]' ) ).toBeNull();
	} );

	it( 'keeps an unsafe completion anchor inert', async () => {
		apiFetch.mockResolvedValue(
			JSON.stringify( {
				status: 200,
				data: {
					create_zip_archive: {
						datetime: '2026-07-12 10:00:00',
						message:
							'ZIP archive created: <a href="https://example.com/archive.zip" onclick="alert(1)">Download</a>',
					},
				},
			} )
		);

		const { container } = renderWithContext(
			<ActivityLog />,
			activityContext
		);

		await screen.findByText( /ZIP archive created: Download/ );
		expect( container.querySelector( 'a' ) ).toBeNull();
		expect( container.querySelector( '[onclick]' ) ).toBeNull();
	} );

	it( 'does not abort or replace a slow poll when the interval ticks or the export finishes', async () => {
		let resolveSlowRequest;
		let requestCount = 0;
		const completionResponse = JSON.stringify( {
			status: 200,
			data: {
				create_zip_archive: {
					datetime: '2026-07-12 10:00:05',
					message:
						'ZIP archive created: <a href="https://example.com/final.zip">Final download</a>',
				},
			},
		} );
		apiFetch.mockImplementation( () => {
			++requestCount;
			if ( requestCount === 1 ) {
				return new Promise( ( resolve ) => {
					resolveSlowRequest = resolve;
				} );
			}

			return Promise.resolve( completionResponse );
		} );
		const runningContext = { ...activityContext, isRunning: true };
		const { rerender } = renderWithContext(
			<ActivityLog />,
			runningContext
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalledTimes( 1 ) );
		const request = apiFetch.mock.calls[ 0 ][ 0 ];
		const intervalCallback = useInterval.mock.calls.find(
			( call ) => call[ 1 ] === 2500
		)[ 0 ];

		act( () => {
			intervalCallback();
			intervalCallback();
		} );
		rerender(
			<SettingsContext.Provider
				value={ { ...runningContext, isRunning: false } }
			>
				<ActivityLog />
			</SettingsContext.Provider>
		);

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( request.signal.aborted ).toBe( false );

		await act( async () => {
			resolveSlowRequest( completionResponse );
		} );

		expect(
			await screen.findByRole( 'link', { name: 'Final download' } )
		).not.toBeNull();
		await waitFor( () => expect( apiFetch ).toHaveBeenCalledTimes( 2 ) );
	} );
} );

describe( 'ExportLog', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		options.is_network = false;
		options.last_export_end = '';
	} );

	it( 'renders notes inertly and links only safe HTTP URLs', async () => {
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path === '/simplystatic/v1/export-type' ) {
				return Promise.resolve(
					JSON.stringify( {
						status: 200,
						data: { export_type: 'Export', export_type_id: null },
					} )
				);
			}

			return Promise.resolve(
				exportResponse( [
					{
						code: 200,
						url: 'javascript:alert(1)',
						notes: '<img src=x onerror="alert(1)">Unsafe',
					},
					{
						code: 200,
						url: 'https://example.com/path?q=1',
						notes: '<strong>Safe note</strong>',
					},
				] )
			);
		} );

		const { container } = renderWithContext( <ExportLog />, exportContext );

		await screen.findByText( 'javascript:alert(1)' );
		expect( screen.getByTestId( 'url-0' ).querySelector( 'a' ) ).toBeNull();
		expect(
			screen.getByTestId( 'notes-0' ).querySelector( 'img' )
		).toBeNull();
		expect( container.querySelector( '[onerror]' ) ).toBeNull();

		const safeLink = screen.getByTestId( 'url-1' ).querySelector( 'a' );
		expect( safeLink ).not.toBeNull();
		expect( safeLink.getAttribute( 'href' ) ).toBe(
			'https://example.com/path?q=1'
		);
		expect( safeLink.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( safeLink.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );
		expect( screen.getByTestId( 'notes-1' ).textContent ).toBe(
			'Safe note'
		);
	} );

	it( 'uses the newly selected page size in state and in the request', async () => {
		const exportPaths = [];
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path === '/simplystatic/v1/export-type' ) {
				return Promise.resolve(
					JSON.stringify( {
						status: 200,
						data: { export_type: 'Export', export_type_id: null },
					} )
				);
			}

			exportPaths.push( path );
			return Promise.resolve( exportResponse() );
		} );

		renderWithContext( <ExportLog />, exportContext );
		await waitFor( () => expect( exportPaths.length ).toBe( 1 ) );

		fireEvent.click( screen.getByRole( 'button', { name: 'Show 50' } ) );

		await waitFor( () =>
			expect(
				exportPaths.some( ( path ) =>
					path.includes( 'page=2&per_page=50' )
				)
			).toBe( true )
		);
		expect(
			screen.getByTestId( 'data-table' ).getAttribute( 'data-per-page' )
		).toBe( '50' );
		expect(
			screen.getByTestId( 'data-table' ).getAttribute( 'data-page' )
		).toBe( '2' );
	} );

	it( 'debounces searches and aborts an obsolete in-flight search', async () => {
		const exportRequests = [];
		let resolveOldSearch;

		apiFetch.mockImplementation( ( request ) => {
			if ( request.path === '/simplystatic/v1/export-type' ) {
				return Promise.resolve(
					JSON.stringify( {
						status: 200,
						data: { export_type: 'Export', export_type_id: null },
					} )
				);
			}

			exportRequests.push( request );
			if ( request.path.includes( 'search=old' ) ) {
				return new Promise( ( resolve ) => {
					resolveOldSearch = resolve;
				} );
			}

			return Promise.resolve( exportResponse() );
		} );

		renderWithContext( <ExportLog />, exportContext );
		await waitFor( () => expect( exportRequests.length ).toBe( 1 ) );

		fireEvent.change( screen.getByLabelText( 'Search export log' ), {
			target: { value: 'o' },
		} );
		fireEvent.change( screen.getByLabelText( 'Search export log' ), {
			target: { value: 'ol' },
		} );
		fireEvent.change( screen.getByLabelText( 'Search export log' ), {
			target: { value: 'old' },
		} );

		await waitFor(
			() =>
				expect(
					exportRequests.some( ( request ) =>
						request.path.includes( 'search=old' )
					)
				).toBe( true ),
			{ timeout: 1000 }
		);
		expect(
			exportRequests.filter( ( request ) =>
				request.path.includes( 'search=' )
			).length
		).toBe( 2 );

		const oldRequest = exportRequests.find( ( request ) =>
			request.path.includes( 'search=old' )
		);
		fireEvent.change( screen.getByLabelText( 'Search export log' ), {
			target: { value: 'new' },
		} );
		expect( oldRequest.signal.aborted ).toBe( true );

		await act( async () => {
			resolveOldSearch(
				exportResponse( [
					{ code: 200, url: 'https://example.com/old', notes: 'Old' },
				] )
			);
		} );

		await waitFor(
			() =>
				expect(
					exportRequests.some( ( request ) =>
						request.path.includes( 'search=new' )
					)
				).toBe( true ),
			{ timeout: 1000 }
		);
		expect( screen.queryByText( '/old' ) ).toBeNull();
	} );
} );
