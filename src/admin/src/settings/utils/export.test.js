import { getRollbackConflict } from './export';

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
