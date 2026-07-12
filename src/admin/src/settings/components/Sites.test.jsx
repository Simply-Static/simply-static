global.wp = {
	i18n: {
		__: ( value ) => value,
	},
};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '../../hooks/useInterval', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( './Site', () => {
	const React = require( '@wordpress/element' );
	return {
		__esModule: true,
		default: ( { site } ) =>
			React.createElement(
				'tr',
				null,
				React.createElement( 'td', null, site.name )
			),
	};
} );

const { act, render, screen, waitFor } = require( '@testing-library/react' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const useInterval = require( '../../hooks/useInterval' ).default;
const Sites = require( './Sites' ).default;

describe( 'Sites', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		useInterval.mockReset();
	} );

	it( 'prevents overlapping network scans and slows active polling', async () => {
		let resolveRequest;
		apiFetch.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveRequest = resolve;
			} )
		);

		render( <Sites /> );
		await waitFor( () => expect( apiFetch ).toHaveBeenCalledTimes( 1 ) );

		const initialRefresh = useInterval.mock.calls.find(
			( call ) => call[ 1 ] === 300000
		)[ 0 ];
		act( () => initialRefresh() );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			resolveRequest( {
				data: [ { id: 2, name: 'Running site', running: true } ],
			} );
		} );

		await screen.findByText( 'Running site' );
		expect(
			useInterval.mock.calls.some( ( call ) => call[ 1 ] === 10000 )
		).toBe( true );
	} );
} );
