global.wp = {
	i18n: {
		__: ( value ) => value,
	},
};

global.options = {
	blog_id: 1,
	can_view_diagnostics: false,
};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '../../hooks/useInterval', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

const { act, render, waitFor } = require( '@testing-library/react' );
const { useContext } = require( '@wordpress/element' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const SettingsContextProvider = require( './SettingsContext' ).default;
const {
	SettingsContext,
	parseActionResponse,
} = require( './SettingsContext' );

describe( 'parseActionResponse', () => {
	it( 'accepts successful encoded responses', () => {
		expect( parseActionResponse( '{"status":200,"message":"Ok"}' ) ).toEqual( {
			status: 200,
			message: 'Ok',
		} );
	} );

	it( 'rejects application errors returned with HTTP 200', () => {
		expect( () =>
			parseActionResponse( '{"status":409,"message":"Export active"}' )
		).toThrow( 'Export active' );
	} );

	it( 'rejects invalid encoded responses', () => {
		expect( () => parseActionResponse( 'not-json' ) ).toThrow(
			'The server returned an invalid response.'
		);
	} );
} );

describe( 'SettingsContext maintenance actions', () => {
	let currentContext;

	function CaptureContext() {
		currentContext = useContext( SettingsContext );
		return null;
	}

	beforeEach( () => {
		currentContext = null;
		apiFetch.mockReset();
		apiFetch.mockImplementation( ( request ) => {
			if ( request.path === '/simplystatic/v1/settings' && ! request.method ) {
				return Promise.resolve( { delivery_method: 'local' } );
			}
			if ( request.path === '/simplystatic/v1/is-running' ) {
				return Promise.resolve(
					JSON.stringify( { running: false, paused: false } )
				);
			}
			return Promise.resolve( JSON.stringify( { status: 200 } ) );
		} );
	} );

	async function renderProvider() {
		render(
			<SettingsContextProvider>
				<CaptureContext />
			</SettingsContextProvider>
		);
		await waitFor( () => expect( currentContext ).not.toBeNull() );
		await waitFor( () =>
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/simplystatic/v1/settings',
			} )
		);
	}

	it( 'marks portable imports and refreshes the server-sanitized settings', async () => {
		await renderProvider();

		await act( async () => {
			await currentContext.importSettings( { delivery_method: 'zip' } );
		} );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/simplystatic/v1/settings',
			method: 'POST',
			data: {
				delivery_method: 'zip',
				__simply_static_import: true,
			},
		} );
		expect( currentContext.settings ).toEqual( { delivery_method: 'local' } );
	} );

	it( 'does not overwrite migrated settings with a stale client snapshot', async () => {
		await renderProvider();

		await act( async () => {
			await currentContext.migrateSettings();
		} );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/simplystatic/v1/migrate',
			method: 'POST',
			data: { migrate: true },
		} );
		expect(
			apiFetch.mock.calls.filter(
				( [ request ] ) =>
					request.path === '/simplystatic/v1/settings' &&
					request.method === 'POST'
			)
		).toHaveLength( 0 );
	} );

	it( 'rejects encoded maintenance conflicts instead of reporting success', async () => {
		await renderProvider();
		apiFetch.mockImplementationOnce( () =>
			Promise.resolve(
				JSON.stringify( { status: 409, message: 'Worker active' } )
			)
		);

		await expect( currentContext.resetDatabase() ).rejects.toThrow(
			'Worker active'
		);
	} );
} );
