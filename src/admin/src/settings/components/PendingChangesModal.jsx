import apiFetch from '@wordpress/api-fetch';
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';

const { __, _n, sprintf } = wp.i18n;
const PER_PAGE = 20;

const getErrorMessage = ( error, fallback ) => {
	const dataMessage = error && error.data && error.data.message;
	if ( 'string' === typeof dataMessage && dataMessage.trim() ) {
		return dataMessage.trim();
	}

	return error && 'string' === typeof error.message && error.message.trim()
		? error.message.trim()
		: fallback;
};

const getReasonMessage = ( reason ) => {
	if ( 'string' === typeof reason ) {
		return reason;
	}

	return reason && 'string' === typeof reason.message ? reason.message : '';
};

const getTypeLabel = ( type ) => {
	switch ( type ) {
		case 'media':
			return __( 'Media', 'simply-static' );
		case 'deleted':
			return __( 'Deleted', 'simply-static' );
		case 'sitewide':
			return __( 'Site-wide', 'simply-static' );
		default:
			return __( 'Content', 'simply-static' );
	}
};

const formatModified = ( value ) => {
	return value ? sprintf( __( '%s UTC', 'simply-static' ), value ) : '\u2014';
};

function PendingChangesModal( { actionsDisabled = false, onCleared, onRequestClose } ) {
	const requestId = useRef( 0 );
	const [ items, setItems ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ page, setPage ] = useState( 1 );
	const [ reviewToken, setReviewToken ] = useState( '' );
	const [ environment, setEnvironment ] = useState( '' );
	const [ language, setLanguage ] = useState( '' );
	const [ canClear, setCanClear ] = useState( false );
	const [ fullExportRequired, setFullExportRequired ] = useState( false );
	const [ fullExportReasons, setFullExportReasons ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ confirmingClear, setConfirmingClear ] = useState( false );
	const [ clearing, setClearing ] = useState( false );
	const [ clearMessage, setClearMessage ] = useState( '' );
	const [ clearRefreshError, setClearRefreshError ] = useState( '' );

	const loadPage = ( nextPage, token = reviewToken, afterClear = false ) => {
		const currentRequest = requestId.current + 1;
		requestId.current = currentRequest;
		setLoading( true );
		setError( '' );
		setConfirmingClear( false );

		let path = `/simplystatic/v1/pending-changes?page=${ nextPage }&per_page=${ PER_PAGE }`;
		if ( token ) {
			path += `&review_token=${ encodeURIComponent( token ) }`;
		}

		return apiFetch( { path, method: 'GET' } ).then( ( response ) => {
			if ( currentRequest !== requestId.current ) {
				return;
			}
			if ( ! response || 'object' !== typeof response || ! Array.isArray( response.items ) ) {
				throw new Error( __( 'Pending changes could not be loaded. Please try again.', 'simply-static' ) );
			}

			setItems( response.items );
			setTotal( Number.isSafeInteger( response.total ) && response.total >= 0 ? response.total : 0 );
			setTotalPages( Number.isSafeInteger( response.total_pages ) && response.total_pages > 0 ? response.total_pages : 1 );
			setPage( Number.isSafeInteger( response.page ) && response.page > 0 ? response.page : nextPage );
			setReviewToken( 'string' === typeof response.review_token ? response.review_token : token );
			setEnvironment( 'string' === typeof response.environment ? response.environment : '' );
			setLanguage( 'string' === typeof response.language ? response.language : '' );
			setCanClear( response.can_clear === true );
			setFullExportRequired( response.full_export_required === true );
			setFullExportReasons( Array.isArray( response.full_export_reasons ) ? response.full_export_reasons : [] );
		} ).catch( ( fetchError ) => {
			if ( currentRequest === requestId.current ) {
				const message = getErrorMessage( fetchError, __( 'Pending changes could not be loaded. Please try again.', 'simply-static' ) );
				setError( afterClear
					? sprintf( __( 'The reviewed changes were cleared, but newer changes could not be loaded: %s', 'simply-static' ), message )
					: message
				);
			}
		} ).finally( () => {
			if ( currentRequest === requestId.current ) {
				setLoading( false );
			}
		} );
	};

	useEffect( () => {
		loadPage( 1, '' );

		return () => {
			requestId.current += 1;
		};
	}, [] );

	const clearChanges = () => {
		if ( actionsDisabled || clearing || ! canClear || ! reviewToken ) {
			return;
		}

		setClearing( true );
		setError( '' );
		setClearMessage( '' );
		setClearRefreshError( '' );

		apiFetch( {
			path: '/simplystatic/v1/pending-changes/clear',
			method: 'POST',
			data: { review_token: reviewToken },
		} ).then( ( response ) => {
			if ( ! response || response.cleared !== true || ! Number.isSafeInteger( response.total ) || response.total < 0 ) {
				throw new Error( __( 'Pending changes could not be cleared. Please try again.', 'simply-static' ) );
			}
			const updatedSummary = response.summary && 'object' === typeof response.summary ? response.summary : {};

			setConfirmingClear( false );
			setTotal( response.total );
			setItems( [] );
			setPage( 1 );
			setTotalPages( 1 );
			setReviewToken( '' );
			if ( 'boolean' === typeof updatedSummary.full_export_required ) {
				setFullExportRequired( updatedSummary.full_export_required );
			}
			if ( Array.isArray( updatedSummary.full_export_reasons ) ) {
				setFullExportReasons( updatedSummary.full_export_reasons );
			}
			setClearMessage( response.total > 0
				? __( 'The reviewed changes were cleared. Newer changes remain queued.', 'simply-static' )
				: __( 'The tracked Changes Only list was cleared.', 'simply-static' )
			);

			if ( 'function' === typeof onCleared ) {
				Promise.resolve()
					.then( () => onCleared( response ) )
					.catch( () => setClearRefreshError(
						__( 'The changes were cleared, but the sidebar count could not be refreshed. Reload the page to check the latest count.', 'simply-static' )
					) );
			}

			if ( response.total > 0 ) {
				loadPage( 1, '', true );
			}
		} ).catch( ( clearError ) => {
			setConfirmingClear( false );
			setError( getErrorMessage( clearError, __( 'Pending changes could not be cleared. Please try again.', 'simply-static' ) ) );
		} ).finally( () => {
			setClearing( false );
		} );
	};

	const visibleReasons = fullExportReasons.map( getReasonMessage ).filter( Boolean );
	const totalMessage = sprintf(
		/* translators: %d: number of tracked changes. */
		_n( '%d tracked change is included in this review.', '%d tracked changes are included in this review.', total, 'simply-static' ),
		total
	);

	return <Modal
		title={ __( 'Pending changes', 'simply-static' ) }
		className="pending-changes-modal"
		onRequestClose={ onRequestClose }
	>
		<p className="pending-changes-summary">{ totalMessage }</p>
		{( environment || language ) && <p className="pending-changes-context">
			{environment && <span>{sprintf( __( 'Environment: %s', 'simply-static' ), environment )}</span>}
			<span>{sprintf( __( 'Language: %s', 'simply-static' ), language || __( 'All languages', 'simply-static' ) )}</span>
		</p>}

		{fullExportRequired && <Notice status="warning" isDismissible={ false }>
			<p><strong>{ __( 'A Full Site export is still required.', 'simply-static' ) }</strong></p>
			{visibleReasons.length > 0 && <ul>
				{visibleReasons.map( ( reason, index ) => <li key={ `${ index }-${ reason }` }>{ reason }</li> )}
			</ul>}
		</Notice>}

		{actionsDisabled && <Notice status="warning" isDismissible={ false }>
			<p>{ __( 'Pending changes cannot be reviewed or cleared while an export is running, paused, or rolling back.', 'simply-static' ) }</p>
		</Notice>}
		{clearMessage && <Notice status="success" isDismissible={ false }><p>{ clearMessage }</p></Notice>}
		{clearRefreshError && <Notice status="warning" isDismissible={ false }><p>{ clearRefreshError }</p></Notice>}

		{loading && <div className="pending-changes-loading" role="status">
			<Spinner />
			<span>{ __( 'Loading pending changes…', 'simply-static' ) }</span>
		</div>}

		{! loading && error && <Notice status="error" isDismissible={ false }>
			<p>{ error }</p>
			<Button variant="secondary" onClick={ () => loadPage( 1, '' ) } disabled={ actionsDisabled }>
				{ __( 'Try again', 'simply-static' ) }
			</Button>
		</Notice>}

		{! loading && ! error && items.length === 0 && <p className="pending-changes-empty">
			{ __( 'There are no tracked Changes Only entries in this review.', 'simply-static' ) }
		</p>}

		{! loading && ! error && items.length > 0 && <div className="pending-changes-table-wrap">
			<table className="widefat striped pending-changes-table">
				<caption className="screen-reader-text">{ __( 'Tracked pending changes', 'simply-static' ) }</caption>
				<thead><tr>
					<th scope="col">{ __( 'Item', 'simply-static' ) }</th>
					<th scope="col">{ __( 'Type', 'simply-static' ) }</th>
					<th scope="col">{ __( 'Modified', 'simply-static' ) }</th>
				</tr></thead>
				<tbody>{items.map( ( item ) => <tr key={ item.id }>
					<td>
						{item.url && 'deleted' !== item.type
							? <a href={ item.url } target="_blank" rel="noopener noreferrer">{ item.title || item.url }</a>
							: <span>{ item.title || item.url || __( 'Untitled item', 'simply-static' ) }</span>}
						{item.url && item.title && <code>{ item.url }</code>}
					</td>
					<td><span className={ `pending-change-type pending-change-type-${ item.type || 'content' }` }>{ getTypeLabel( item.type ) }</span></td>
					<td>{ formatModified( item.modified_gmt ) }</td>
				</tr> )}</tbody>
			</table>
		</div>}

		{! loading && ! error && totalPages > 1 && <div className="pending-changes-pagination">
			<Button variant="secondary" onClick={ () => loadPage( page - 1 ) } disabled={ actionsDisabled || page <= 1 }>
				{ __( 'Previous', 'simply-static' ) }
			</Button>
			<span>{sprintf( __( 'Page %1$d of %2$d', 'simply-static' ), page, totalPages )}</span>
			<Button variant="secondary" onClick={ () => loadPage( page + 1 ) } disabled={ actionsDisabled || page >= totalPages }>
				{ __( 'Next', 'simply-static' ) }
			</Button>
		</div>}

		{! loading && ! error && canClear && total > 0 && <div className="pending-changes-clear">
			{! confirmingClear
				? <Button variant="link" isDestructive onClick={ () => setConfirmingClear( true ) } disabled={ actionsDisabled || clearing }>
					{ __( 'Clear tracked changes', 'simply-static' ) }
				</Button>
				: <div className="pending-changes-confirmation" role="alert">
					<p>{ __( 'Clear all tracked Changes Only entries included in this review? This only resets the tracked Changes Only list. It does not undo WordPress, plugin, or theme updates, and Full Site exports still use the current source. Changes made after this review began will stay queued.', 'simply-static' ) }</p>
					<div>
						<Button variant="secondary" onClick={ () => setConfirmingClear( false ) } disabled={ clearing }>{ __( 'Cancel', 'simply-static' ) }</Button>
						<Button variant="primary" isDestructive onClick={ clearChanges } isBusy={ clearing } disabled={ actionsDisabled || clearing }>{ __( 'Confirm clear', 'simply-static' ) }</Button>
					</div>
				</div>}
		</div>}
	</Modal>;
}

export default PendingChangesModal;
