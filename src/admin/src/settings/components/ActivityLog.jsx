import {
	useCallback,
	useContext,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { SettingsContext } from '../context/SettingsContext';
import { Dashicon, Notice } from '@wordpress/components';
import Terminal, { ColorMode, TerminalOutput } from 'react-terminal-ui';
import apiFetch from '@wordpress/api-fetch';
import useInterval from '../../hooks/useInterval';
import {
	parseActivityLogMessage,
	parseLogResponse,
	toInertLogText,
} from '../utils/log';

const { __ } = wp.i18n;
function ActivityLog() {
	const {
		isRunning,
		isResumed,
		blogId,
		snapshotRollback,
		isRollbackRunning,
	} = useContext( SettingsContext );
	const rollbackMessage =
		snapshotRollback && snapshotRollback.message
			? snapshotRollback.message
			: __(
					'Rollback in progress. Simply Static export actions are locked until the rollback has finished.',
					'simply-static'
			  );
	const rollbackProgress =
		snapshotRollback && snapshotRollback.progress
			? snapshotRollback.progress
			: {};
	const rollbackProgressText = rollbackProgress.label
		? `${ rollbackProgress.label }${
				rollbackProgress.total
					? ` (${ rollbackProgress.percent || 0 }%, ${
							rollbackProgress.completed || 0
					  }/${ rollbackProgress.total })`
					: ''
		  }`
		: rollbackMessage;
	const [ terminalLineData, setTerminalLineData ] = useState( [
		<TerminalOutput key="waiting">
			{ __( 'Waiting for new push…', 'simply-static' ) }
		</TerminalOutput>,
	] );
	const activityRequest = useRef( {
		blogId: null,
		controller: null,
		promise: null,
		queued: false,
		sequence: 0,
	} );
	const isMounted = useRef( true );

	const refreshActivityLog = useCallback( () => {
		// Polling is single-flight per site. Aborting a request on every interval
		// starves any endpoint that takes longer than 2.5 seconds and can discard
		// the final completion response when isRunning flips to false.
		if (
			activityRequest.current.promise &&
			activityRequest.current.blogId === blogId
		) {
			// Coalesce all ticks into one trailing refresh. This preserves the slow
			// response while still guaranteeing a final request after completion.
			activityRequest.current.queued = true;
			return activityRequest.current.promise;
		}

		const controller =
			typeof AbortController !== 'undefined'
				? new AbortController()
				: null;
		const sequence = activityRequest.current.sequence + 1;

		const request = {
			path:
				'/simplystatic/v1/activity-log?blog_id=' +
				blogId +
				'&is_network_admin=' +
				options.is_network,
			method: 'GET',
		};
		if ( controller ) {
			request.signal = controller.signal;
		}

		const promise = apiFetch( request )
			.then( ( resp ) => {
				const json = parseLogResponse( resp );
				if ( ! json || ! json.data || typeof json.data !== 'object' ) {
					return;
				}

				if (
					! isMounted.current ||
					sequence !== activityRequest.current.sequence
				) {
					return;
				}

				const terminal = Object.entries( json.data ).map(
					( [ message, entry ] ) => {
						const safeEntry =
							entry && typeof entry === 'object' ? entry : {};
						const date = toInertLogText( safeEntry.datetime );
						const content = parseActivityLogMessage(
							safeEntry.message
						);
						const error =
							message.includes( 'pause' ) ||
							message.includes( 'cancel' );
						const success = message.includes( 'resume' );

						return (
							<TerminalOutput key={ message }>
								[{ date }]{ ' ' }
								<span
									className={ `${ error ? 'is-error' : '' } ${
										success ? 'is-success' : ''
									}` }
								>
									{ content.before }
									{ content.before && content.link ? ' ' : null }
									{ content.link && (
										<a
											href={ content.link.href }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ content.link.label }
										</a>
									) }
									{ content.after &&
									( content.before || content.link )
										? ' '
										: null }
									{ content.after }
								</span>
							</TerminalOutput>
						);
					}
				);

				setTerminalLineData( terminal );
			} )
			.catch( ( error ) => {
				if ( ! error || error.name !== 'AbortError' ) {
					// Keep the last successful log visible when a refresh fails.
				}
			} )
			.finally( () => {
				if ( sequence === activityRequest.current.sequence ) {
					const refreshQueued = activityRequest.current.queued;
					activityRequest.current = {
						blogId,
						controller: null,
						promise: null,
						queued: false,
						sequence,
					};

					if ( refreshQueued && isMounted.current ) {
						refreshActivityLog();
					}
				}
			} );

		activityRequest.current = {
			blogId,
			controller,
			promise,
			queued: false,
			sequence,
		};

		return promise;
	}, [ blogId ] );

	useEffect( () => {
		isMounted.current = true;

		return () => {
			isMounted.current = false;
			if ( activityRequest.current.controller ) {
				activityRequest.current.controller.abort();
			}
		};
	}, [] );

	useInterval(
		() => {
			refreshActivityLog();
		},
		isRunning ? 2500 : null
	);

	useEffect( () => {
		if ( isRunning && ! isResumed ) {
			setTerminalLineData( [
				<TerminalOutput key="waiting">
					{ __( 'Waiting for new push…', 'simply-static' ) }
				</TerminalOutput>,
			] );
		}
		if ( isRunning && isResumed ) {
			setTerminalLineData( [
				<TerminalOutput key="resuming">
					{ __( 'Resuming the push…', 'simply-static' ) }
				</TerminalOutput>,
			] );
		}

		refreshActivityLog();
	}, [ isRunning, isResumed, refreshActivityLog ] );

	return (
		<>
			{ isRollbackRunning && (
				<Notice
					status="warning"
					isDismissible={ false }
					className={ 'rollback-lock-notice' }
				>
					<p>
						<Dashicon icon="update" />
						<strong>
							{ __( 'Rollback in progress', 'simply-static' ) }
						</strong>
						<span>{ rollbackProgressText }</span>
					</p>
				</Notice>
			) }
			<Terminal
				name={ __( 'Activity Log', 'simply-static' ) }
				height="250px"
				colorMode={ ColorMode.Dark }
			>
				{ terminalLineData }
			</Terminal>
		</>
	);
}

export default ActivityLog;
