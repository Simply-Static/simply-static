global.wp = {
	i18n: {
		__: ( value ) => value,
	},
	apiFetch: jest.fn(),
};

jest.mock( '@wordpress/components', () => {
	const React = require( '@wordpress/element' );
	const wrap =
		( tag ) =>
		( { children, ...props } ) =>
			React.createElement( tag, props, children );

	return {
		Animate: ( { children } ) => children(),
		Button: ( { children, isBusy, ...props } ) =>
			React.createElement( 'button', props, children ),
		Card: wrap( 'section' ),
		CardBody: wrap( 'div' ),
		CardHeader: wrap( 'header' ),
		ClipboardButton: wrap( 'button' ),
		Notice: ( { children } ) =>
			React.createElement( 'div', { role: 'alert' }, children ),
		__experimentalSpacer: () => null,
	};
} );

jest.mock( '../components/HelperVideo', () => () => null );

const { fireEvent, render, screen, waitFor } = require( '@testing-library/react' );
const { SettingsContext } = require( '../context/SettingsContext' );
const Utilities = require( './Utilities' ).default;

const createContext = ( overrides = {} ) => ( {
	importSettings: jest.fn().mockResolvedValue( {} ),
	resetSettings: jest.fn().mockResolvedValue( {} ),
	migrateSettings: jest.fn().mockResolvedValue( {} ),
	resetDatabase: jest.fn().mockResolvedValue( {} ),
	resetBackgroundQueue: jest.fn().mockResolvedValue( {} ),
	isRunning: false,
	isPaused: false,
	isRollbackRunning: false,
	...overrides,
} );

const renderUtilities = ( context ) =>
	render(
		<SettingsContext.Provider value={ context }>
			<Utilities />
		</SettingsContext.Provider>
	);

describe( 'Utilities', () => {
	it( 'allows partial JSON to be typed without invoking an import', () => {
		const context = createContext();
		renderUtilities( context );

		fireEvent.change( screen.getByLabelText( 'Settings JSON to import' ), {
			target: { value: '{' },
		} );
		fireEvent.click( screen.getByRole( 'button', { name: 'Import Settings' } ) );

		expect( context.importSettings ).not.toHaveBeenCalled();
		expect( screen.getByRole( 'alert' ).textContent ).toMatch( /JSON/ );
	} );

	it( 'awaits a valid import before showing success', async () => {
		const context = createContext();
		renderUtilities( context );

		fireEvent.change( screen.getByLabelText( 'Settings JSON to import' ), {
			target: { value: '{"delivery_method":"zip"}' },
		} );
		fireEvent.click( screen.getByRole( 'button', { name: 'Import Settings' } ) );

		await waitFor( () =>
			expect( context.importSettings ).toHaveBeenCalledWith( {
				delivery_method: 'zip',
			} )
		);
		expect( await screen.findByText( 'Settings imported successfully.' ) ).toBeTruthy();
	} );

	it( 'locks destructive maintenance controls during an export', () => {
		renderUtilities( createContext( { isRunning: true } ) );

		expect( screen.getByRole( 'button', { name: 'Reset Plugin Settings' } ).disabled ).toBe( true );
		expect( screen.getByRole( 'button', { name: 'Reset Database Table' } ).disabled ).toBe( true );
		expect( screen.getByRole( 'button', { name: 'Migrate settings' } ).disabled ).toBe( true );
		expect( screen.getByRole( 'button', { name: 'Reset Background Queue' } ).disabled ).toBe( false );
	} );
} );
