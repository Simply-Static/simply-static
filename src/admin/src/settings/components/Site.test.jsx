global.wp = { i18n: { __: ( value ) => value } };
global.options = { is_network: true, plan: 'pro' };

jest.mock( '@wordpress/api-fetch', () => ( { __esModule: true, default: jest.fn() } ) );
jest.mock( '@wordpress/components', () => {
	const React = require( '@wordpress/element' );
	return {
		Button: ( { children, onClick, disabled } ) => React.createElement( 'button', { onClick, disabled }, children ),
		Dashicon: () => null,
		Notice: ( { children } ) => React.createElement( 'div', { role: 'alert' }, children ),
	};
} );
jest.mock( './GenerateButtons', () => {
	const React = require( '@wordpress/element' );
	return { __esModule: true, default: ( { canGenerate, startExport } ) => React.createElement( 'button', { disabled: ! canGenerate, onClick: startExport }, 'Push' ) };
} );

const { fireEvent, render, screen, waitFor } = require( '@testing-library/react' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const Site = require( './Site' ).default;

const message = 'A platform backup is active. Wait for it to finish before starting an export.';
const fallback = 'The export could not be started. Please try again.';
function renderSite() {
	const setAnyRunning = jest.fn();
	const rendered = render( <table><tbody><Site site={ { id: 2395, name: 'Example', url: 'https://example.com', running: false, paused: false } } setAnyRunning={ setAnyRunning } /></tbody></table> );
	return { ...rendered, setAnyRunning };
}

describe( 'network push rejection feedback', () => {
	beforeEach( () => apiFetch.mockReset() );

	it.each( [ 409, 500, 503 ] )( 'shows an encoded status %s and leaves Push available', async ( status ) => {
		apiFetch.mockResolvedValue( JSON.stringify( { status, message } ) );
		const { setAnyRunning } = renderSite();
		fireEvent.click( screen.getByRole( 'button', { name: 'Push' } ) );
		await screen.findByText( message );
		expect( screen.getByRole( 'button', { name: 'Push' } ).disabled ).toBe( false );
		expect( setAnyRunning ).not.toHaveBeenCalled();
	} );

	it( 'handles rejected HTTP requests and clears the notice after a successful retry', async () => {
		apiFetch.mockRejectedValueOnce( { message, data: { status: 409 } } ).mockResolvedValueOnce( { status: 200 } );
		const { setAnyRunning } = renderSite();
		fireEvent.click( screen.getByRole( 'button', { name: 'Push' } ) );
		await screen.findByText( message );
		fireEvent.click( screen.getByRole( 'button', { name: 'Push' } ) );
		await waitFor( () => expect( setAnyRunning ).toHaveBeenCalledWith( true ) );
		expect( screen.queryByRole( 'alert' ) ).toBeNull();
	} );

	it( 'shows a fallback for transport failures and renders server prose as text', async () => {
		apiFetch.mockRejectedValueOnce( {} ).mockResolvedValueOnce( JSON.stringify( { status: 500, message: '<img src=x onerror="alert(1)">Rejected' } ) );
		const { container } = renderSite();
		fireEvent.click( screen.getByRole( 'button', { name: 'Push' } ) );
		await screen.findByText( fallback );
		fireEvent.click( screen.getByRole( 'button', { name: 'Push' } ) );
		await screen.findByText( '<img src=x onerror="alert(1)">Rejected' );
		expect( container.querySelector( '[onerror]' ) ).toBeNull();
	} );
} );
