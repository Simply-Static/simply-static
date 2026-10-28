import { getExportStartErrorMessage, getRollbackConflict } from './export';

describe( 'getRollbackConflict', () => {
	it( 'does not turn ordinary start conflicts into a permanent rollback lock', () => {
		expect(
			getRollbackConflict( {
				status: 409,
				message: 'An export is already running.',
			} )
		).toBeNull();
		expect( getRollbackConflict( null ) ).toBeNull();
	} );

	it( 'preserves explicitly reported rollback state', () => {
		expect(
			getRollbackConflict( {
				rollback_running: true,
				snapshot_rollback: { progress: { percent: 25 } },
			} )
		).toEqual( {
			running: true,
			progress: { percent: 25 },
		} );
	} );
} );

describe( 'getExportStartErrorMessage', () => {
	const failureMessage = 'The export could not be started. Please try again.';
	const rollbackMessage = 'Rollback in progress.';
	const getMessage = ( payload ) =>
		getExportStartErrorMessage( payload, failureMessage, rollbackMessage );

	it( 'uses a generic failure for missing-message conflicts and network errors', () => {
		[
			null,
			undefined,
			{},
			{ status: 409 },
			{ data: { status: 409 } },
			{ message: '  ' },
			{ message: {} },
		].forEach( ( payload ) => {
			expect( getMessage( payload ) ).toBe( failureMessage );
		} );
	} );

	it( 'uses rollback wording only when the server explicitly reports rollback', () => {
		expect( getMessage( { status: 409, rollback_running: true } ) ).toBe(
			rollbackMessage
		);
		expect(
			getMessage( {
				data: { status: 409, snapshot_rollback: { running: true } },
			} )
		).toBe( rollbackMessage );
		expect(
			getMessage( {
				data: { status: 409, snapshot_rollback: { running: false } },
			} )
		).toBe( failureMessage );
	} );

	it( 'preserves useful response and apiFetch error messages', () => {
		expect(
			getMessage( {
				status: 409,
				message: 'An export is already running.',
			} )
		).toBe( 'An export is already running.' );
		expect(
			getMessage( { message: 'Connection lost.', data: { status: 503 } } )
		).toBe( 'Connection lost.' );
		expect(
			getMessage( {
				data: { status: 409, message: 'An export is already running.' },
			} )
		).toBe( 'An export is already running.' );
	} );
} );
