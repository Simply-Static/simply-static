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

/**
 * Preserve a start-export error without labeling unrelated failures as rollback.
 *
 * @param {*}      payload         REST response or apiFetch error.
 * @param {string} fallbackMessage Message for an ordinary start failure.
 * @param {string} rollbackMessage Message for an explicitly reported rollback.
 * @return {string} Message to show in the export alert.
 */
export function getExportStartErrorMessage(
	payload,
	fallbackMessage,
	rollbackMessage
) {
	const data =
		payload && payload.data && typeof payload.data === 'object'
			? payload.data
			: payload;
	const messages = [ payload && payload.message, data && data.message ];
	const message = messages.find(
		( value ) => typeof value === 'string' && value.trim()
	);

	if ( message ) {
		return message.trim();
	}

	return getRollbackConflict( data ) ? rollbackMessage : fallbackMessage;
}
