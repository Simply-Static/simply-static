global.wp = {
	i18n: {
		__: ( value ) => value,
		_n: ( singular, plural, number ) => 1 === number ? singular : plural,
		sprintf: ( value, ...args ) => {
			let nextArg = 0;
			return value.replace( /%(?:(\d+)\$)?[ds]/g, ( match, position ) => {
				const index = position ? Number( position ) - 1 : nextArg++;
				return args[ index ];
			} );
		},
	},
};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	const React = require( '@wordpress/element' );

	return {
		Button: ( { children, onClick, disabled, href } ) => href
			? React.createElement( 'a', { href }, children )
			: React.createElement( 'button', { type: 'button', onClick, disabled }, children ),
		Modal: ( { children, title, onRequestClose } ) => React.createElement(
			'div',
			{ role: 'dialog', 'aria-label': title },
			React.createElement( 'button', { type: 'button', onClick: onRequestClose }, 'Close' ),
			children
		),
		Notice: ( { children } ) => React.createElement( 'div', { role: 'alert' }, children ),
		Spinner: () => React.createElement( 'span', null, 'Spinner' ),
	};
} );

const { fireEvent, render, screen, waitFor } = require( '@testing-library/react' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const PendingChangesModal = require( './PendingChangesModal' ).default;

const firstPage = {
	items: [
		{
			id: 11,
			title: 'About us',
			url: 'https://example.com/about/',
			type: 'content',
			modified_gmt: '2026-11-18 08:30:00',
		},
	],
	total: 21,
	total_pages: 2,
	page: 1,
	review_token: 'review-token',
	environment: 'production',
	language: 'de_DE',
	can_clear: true,
	full_export_required: true,
	full_export_reasons: [ { code: 'theme', message: 'The active theme changed.' } ],
	summary: {},
};

describe( 'PendingChangesModal', () => {
	beforeEach( () => {
		apiFetch.mockReset();
	} );

	it( 'loads the review and retains its cutoff token across pagination', async () => {
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( '?page=2&' ) ) {
				return Promise.resolve( {
					...firstPage,
					items: [ { id: 22, title: 'Old logo', url: '', type: 'deleted', modified_gmt: '2026-11-18 09:00:00' } ],
					page: 2,
				} );
			}
			return Promise.resolve( firstPage );
		} );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );

		expect( ( await screen.findByRole( 'link', { name: 'About us' } ) ).getAttribute( 'href' ) ).toBe( 'https://example.com/about/' );
		expect( screen.getByText( 'The active theme changed.' ) ).not.toBeNull();
		expect( screen.getByText( 'Environment: production' ) ).not.toBeNull();
		expect( screen.getByText( 'Language: de_DE' ) ).not.toBeNull();

		fireEvent.click( screen.getByRole( 'button', { name: 'Next' } ) );
		await screen.findByText( 'Old logo' );
		expect( apiFetch ).toHaveBeenLastCalledWith( {
			path: '/simplystatic/v1/pending-changes?page=2&per_page=20&review_token=review-token',
			method: 'GET',
		} );
	} );

	it( 'labels structural plugin and theme records as site-wide changes', async () => {
		apiFetch.mockResolvedValue( {
			...firstPage,
			items: [
				{
					id: 'sitewide-1',
					title: 'Plugin change: plugin/basename.php',
					url: '',
					type: 'sitewide',
					modified_gmt: '2026-11-18 08:30:00',
				},
			],
			total: 1,
			total_pages: 1,
		} );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );

		const label = await screen.findByText( 'Site-wide' );
		expect( label.closest( 'tr' ).textContent ).toContain( 'Plugin change: plugin/basename.php' );
		expect( label.className ).toContain( 'pending-change-type-sitewide' );
		expect( label.closest( 'tr' ).textContent ).not.toContain( 'Deleted' );
	} );

	it( 'uses inline confirmation, clears only the review cutoff, and preserves full-export requirements', async () => {
		const onCleared = jest.fn();
		apiFetch.mockImplementation( ( { path, method } ) => {
			if ( 'POST' === method && '/simplystatic/v1/pending-changes/clear' === path ) {
				return Promise.resolve( { cleared: true, total: 0, summary: { changes_count: 0 } } );
			}
			return Promise.resolve( { ...firstPage, total: 1, total_pages: 1 } );
		} );

		render( <PendingChangesModal onCleared={ onCleared } onRequestClose={ jest.fn() } /> );
		fireEvent.click( await screen.findByRole( 'button', { name: 'Clear tracked changes' } ) );

		expect( screen.getByText( /does not undo WordPress, plugin, or theme updates/ ) ).not.toBeNull();
		expect( screen.getByText( /Changes made after this review began will stay queued/ ) ).not.toBeNull();
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		fireEvent.click( screen.getByRole( 'button', { name: 'Confirm clear' } ) );

		await screen.findByText( 'The tracked Changes Only list was cleared.' );
		expect( screen.getByText( 'A Full Site export is still required.' ) ).not.toBeNull();
		expect( screen.getByText( 'The active theme changed.' ) ).not.toBeNull();
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/simplystatic/v1/pending-changes/clear',
			method: 'POST',
			data: { review_token: 'review-token' },
		} );
		expect( onCleared ).toHaveBeenCalledWith( { cleared: true, total: 0, summary: { changes_count: 0 } } );
	} );

	it( 'disables review actions while export state is locked', async () => {
		apiFetch.mockResolvedValue( firstPage );

		render( <PendingChangesModal actionsDisabled onRequestClose={ jest.fn() } /> );

		expect( await screen.findByText( /cannot be reviewed or cleared while an export is running/ ) ).not.toBeNull();
		expect( screen.getByRole( 'button', { name: 'Clear tracked changes' } ).disabled ).toBe( true );
		expect( screen.getByRole( 'button', { name: 'Next' } ).disabled ).toBe( true );
	} );

	it( 'keeps the clear outcome distinct when newer changes cannot be refreshed', async () => {
		let listRequests = 0;
		apiFetch.mockImplementation( ( { path, method } ) => {
			if ( 'POST' === method && '/simplystatic/v1/pending-changes/clear' === path ) {
				return Promise.resolve( { cleared: true, total: 1, summary: { changes_count: 1 } } );
			}
			listRequests += 1;
			return 1 === listRequests
				? Promise.resolve( { ...firstPage, total: 1, total_pages: 1 } )
				: Promise.reject( new Error( 'Refresh offline' ) );
		} );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );
		fireEvent.click( await screen.findByRole( 'button', { name: 'Clear tracked changes' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Confirm clear' } ) );

		expect( await screen.findByText( 'The reviewed changes were cleared. Newer changes remain queued.' ) ).not.toBeNull();
		expect( await screen.findByText( 'The reviewed changes were cleared, but newer changes could not be loaded: Refresh offline' ) ).not.toBeNull();
	} );

	it( 'shows a recoverable error when the list request fails', async () => {
		apiFetch
			.mockRejectedValueOnce( new Error( 'Connection lost' ) )
			.mockResolvedValueOnce( { ...firstPage, total: 1, total_pages: 1 } );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );

		expect( await screen.findByText( 'Connection lost' ) ).not.toBeNull();
		fireEvent.click( screen.getByRole( 'button', { name: 'Try again' } ) );
		expect( await screen.findByRole( 'link', { name: 'About us' } ) ).not.toBeNull();
	} );

	it( 'starts a fresh review when a retained pagination token is stale', async () => {
		apiFetch
			.mockResolvedValueOnce( firstPage )
			.mockRejectedValueOnce( new Error( 'Review expired' ) )
			.mockResolvedValueOnce( {
				...firstPage,
				items: [ { id: 31, title: 'Fresh review', url: 'https://example.com/fresh/', type: 'content', modified_gmt: '2026-11-18 10:00:00' } ],
				total: 1,
				total_pages: 1,
				page: 1,
				review_token: 'fresh-token',
			} );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );
		await screen.findByRole( 'link', { name: 'About us' } );
		fireEvent.click( screen.getByRole( 'button', { name: 'Next' } ) );
		await screen.findByText( 'Review expired' );
		fireEvent.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( await screen.findByRole( 'link', { name: 'Fresh review' } ) ).not.toBeNull();
		expect( apiFetch ).toHaveBeenLastCalledWith( {
			path: '/simplystatic/v1/pending-changes?page=1&per_page=20',
			method: 'GET',
		} );
	} );

	it( 'does not replay a failed clear and requires confirmation for the fresh review', async () => {
		apiFetch.mockImplementation( ( { path, method } ) => {
			if ( 'POST' === method ) {
				return Promise.reject( new Error( 'Review expired' ) );
			}
			return Promise.resolve( {
				...firstPage,
				total: 1,
				total_pages: 1,
				page: 1,
				review_token: path.includes( 'review_token' ) ? 'old-token' : 'fresh-token',
			} );
		} );

		render( <PendingChangesModal onRequestClose={ jest.fn() } /> );
		fireEvent.click( await screen.findByRole( 'button', { name: 'Clear tracked changes' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Confirm clear' } ) );
		await screen.findByText( 'Review expired' );

		expect( screen.queryByRole( 'button', { name: 'Confirm clear' } ) ).toBeNull();
		fireEvent.click( screen.getByRole( 'button', { name: 'Try again' } ) );
		expect( await screen.findByRole( 'button', { name: 'Clear tracked changes' } ) ).not.toBeNull();
		expect( apiFetch.mock.calls.filter( ( [ request ] ) => 'POST' === request.method ) ).toHaveLength( 1 );
		expect( apiFetch ).toHaveBeenLastCalledWith( {
			path: '/simplystatic/v1/pending-changes?page=1&per_page=20',
			method: 'GET',
		} );
	} );
} );
