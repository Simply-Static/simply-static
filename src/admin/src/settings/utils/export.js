/**
 * Normalize an explicit rollback lock from an export response.
 * Ordinary HTTP/status conflicts (for example an already-running export) are
 * intentionally not treated as rollback state.
 *
 * @param {*} payload Response/error data.
 * @return {Object|null} Snapshot rollback state, or null for a normal conflict.
 */
export function getRollbackConflict( payload ) {
	if ( ! payload || typeof payload !== 'object' ) {
		return null;
	}

	const snapshot =
		payload.snapshot_rollback &&
		typeof payload.snapshot_rollback === 'object'
			? payload.snapshot_rollback
			: {};
	if ( ! payload.rollback_running && ! snapshot.running ) {
		return null;
	}

	return { ...snapshot, running: true };
}
