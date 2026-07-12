import {
	useCallback,
	useContext,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { SettingsContext } from '../context/SettingsContext';
import apiFetch from '@wordpress/api-fetch';
import useInterval from '../../hooks/useInterval';
import DataTable from 'react-data-table-component';
import { Flex, FlexItem, Notice, Spinner } from '@wordpress/components';
import { getSafeLogUrl, parseLogResponse, toInertLogText } from '../utils/log';

const { __ } = wp.i18n;
const SEARCH_DEBOUNCE_MS = 300;
const DEFAULT_PER_PAGE = 25;
const MAX_PER_PAGE = 200;

function ExportLog() {
	const { isRunning, blogId, isPro } = useContext( SettingsContext );
	const [ exportLog, setExportLog ] = useState( {
		static_pages: [],
		total_static_pages: 0,
	} );
	const [ loadingExportLog, setLoadingExportLog ] = useState( false );
	const [ exportLogError, setExportLogError ] = useState( '' );
	const [ perPageExportLog, setPerPageExportLog ] =
		useState( DEFAULT_PER_PAGE );
	const [ exportPage, setExportPage ] = useState( 1 );
	const [ resetPagination, setResetPagination ] = useState( false );
	const [ filterText, setFilterText ] = useState( '' );
	const [ exportType, setExportType ] = useState( 'export' );
	const [ exportTypeId, setExportTypeId ] = useState( null );
	const currentPage = useRef( 1 );
	const currentPerPage = useRef( DEFAULT_PER_PAGE );
	const currentSearch = useRef( '' );
	const exportLogRequest = useRef( { controller: null, sequence: 0 } );
	const searchInitialized = useRef( false );
	const isMounted = useRef( true );

	const cancelExportLogRequest = useCallback( () => {
		exportLogRequest.current.sequence += 1;
		if ( exportLogRequest.current.controller ) {
			exportLogRequest.current.controller.abort();
		}
		exportLogRequest.current.controller = null;
	}, [] );

	const getExportLog = useCallback(
		( {
			page = currentPage.current,
			perPage = currentPerPage.current,
			search = currentSearch.current,
			showLoading = true,
			cancelPrevious = true,
		} = {} ) => {
			const safePage = Math.max( 1, Number.parseInt( page, 10 ) || 1 );
			const safePerPage = Math.min(
				MAX_PER_PAGE,
				Math.max(
					1,
					Number.parseInt( perPage, 10 ) || DEFAULT_PER_PAGE
				)
			);
			const safeSearch = typeof search === 'string' ? search : '';

			if ( ! cancelPrevious && exportLogRequest.current.controller ) {
				return Promise.resolve( null );
			}

			if ( cancelPrevious ) {
				cancelExportLogRequest();
			}

			const controller =
				typeof AbortController !== 'undefined'
					? new AbortController()
					: null;
			const sequence = exportLogRequest.current.sequence + 1;
			exportLogRequest.current = { controller, sequence };

			if ( showLoading ) {
				setLoadingExportLog( true );
			}
			setExportLogError( '' );

			const request = {
				path: `/simplystatic/v1/export-log?page=${ safePage }&per_page=${ safePerPage }&blog_id=${ encodeURIComponent(
					blogId
				) }&is_network_admin=${
					options.is_network
				}&search=${ encodeURIComponent( safeSearch ) }`,
				method: 'GET',
			};
			if ( controller ) {
				request.signal = controller.signal;
			}

			return apiFetch( request )
				.then( ( resp ) => {
					const json = parseLogResponse( resp );
					if (
						! json ||
						! json.data ||
						typeof json.data !== 'object'
					) {
						throw new Error(
							__(
								'Invalid export log response.',
								'simply-static'
							)
						);
					}

					if (
						! isMounted.current ||
						sequence !== exportLogRequest.current.sequence
					) {
						return null;
					}

					const data = json.data;
					setExportLog( ( previous ) => ( {
						...previous,
						...data,
						static_pages: Array.isArray( data.static_pages )
							? data.static_pages
							: previous.static_pages,
						total_static_pages:
							Number.parseInt( data.total_static_pages, 10 ) || 0,
					} ) );
					currentPage.current = safePage;
					setExportPage( safePage );

					return data;
				} )
				.catch( ( error ) => {
					if ( ! error || error.name !== 'AbortError' ) {
						if (
							isMounted.current &&
							sequence === exportLogRequest.current.sequence
						) {
							setExportLogError(
								error && error.message
									? error.message
									: __(
											'Could not load the export log.',
											'simply-static'
									  )
							);
						}
					}

					return null;
				} )
				.finally( () => {
					if ( sequence === exportLogRequest.current.sequence ) {
						exportLogRequest.current.controller = null;
						if ( isMounted.current ) {
							setLoadingExportLog( false );
						}
					}
				} );
		},
		[ blogId, cancelExportLogRequest ]
	);

	const fetchExportType = useCallback( () => {
		return apiFetch( {
			path: '/simplystatic/v1/export-type',
			method: 'GET',
		} )
			.then( ( response ) => {
				const json = parseLogResponse( response );
				if ( json && json.status === 200 && json.data ) {
					setExportType( json.data.export_type );
					setExportTypeId( json.data.export_type_id );
				}
			} )
			.catch( () => {
				setExportType( options.last_export_end ? 'Update' : 'export' );
				setExportTypeId( null );
			} );
	}, [] );

	useEffect( () => {
		isMounted.current = true;

		return () => {
			isMounted.current = false;
			cancelExportLogRequest();
		};
	}, [ cancelExportLogRequest ] );

	useEffect( () => {
		currentPage.current = 1;
		setExportPage( 1 );
		getExportLog( { page: 1 } );
	}, [ blogId, getExportLog ] );

	useEffect( () => {
		fetchExportType();
	}, [ fetchExportType, isRunning ] );

	useEffect( () => {
		if ( ! searchInitialized.current ) {
			searchInitialized.current = true;
			return undefined;
		}

		const timeout = window.setTimeout( () => {
			currentPage.current = 1;
			setExportPage( 1 );
			setResetPagination( ( value ) => ! value );
			getExportLog( { page: 1, search: filterText } );
		}, SEARCH_DEBOUNCE_MS );

		return () => window.clearTimeout( timeout );
	}, [ filterText, getExportLog ] );

	useInterval(
		() => {
			getExportLog( { showLoading: false, cancelPrevious: false } );
		},
		isRunning ? 5000 : null
	);

	const handlePageChange = ( page ) => {
		currentPage.current = page;
		setExportPage( page );
		getExportLog( { page } );
	};

	const handlePerRowsChange = ( newPerPage, page ) => {
		currentPerPage.current = newPerPage;
		currentPage.current = page;
		setPerPageExportLog( newPerPage );
		setExportPage( page );
		getExportLog( { page, perPage: newPerPage } );
	};

	const handleSearch = ( event ) => {
		const searchTerm = event.target.value;
		cancelExportLogRequest();
		setLoadingExportLog( false );
		currentSearch.current = searchTerm;
		setFilterText( searchTerm );
	};

	const urlColumnWidth = isPro() ? '40%' : '60%';
	const baseColumns = [
		{
			name: __( 'Code', 'simply-static' ),
			selector: ( row ) => row.code,
			sortable: true,
			width: '12%',
		},
		{
			name: __( 'URL', 'simply-static' ),
			selector: ( row ) => {
				const raw = row && typeof row.url === 'string' ? row.url : '';
				if ( ! raw ) {
					return <span>-</span>;
				}

				const safeUrl = getSafeLogUrl( raw );
				if ( ! safeUrl ) {
					return <span>{ raw }</span>;
				}

				return (
					<a
						target="_blank"
						rel="noopener noreferrer"
						href={ safeUrl.href }
					>
						{ safeUrl.label }
					</a>
				);
			},
			sortable: true,
			sortFunction: ( rowA, rowB ) =>
				String( ( rowA && rowA.url ) || '' ).localeCompare(
					String( ( rowB && rowB.url ) || '' )
				),
			width: urlColumnWidth,
			wrap: true,
		},
	];

	const exportTypeColumn = {
		name: __( 'Export Type', 'simply-static' ),
		selector: () => {
			if ( exportType === 'Build' || exportType === 'Single' ) {
				return `${ exportType } (ID: ${ exportTypeId })`;
			}
			return exportType;
		},
		sortable: true,
		width: '20%',
	};

	const notesColumn = {
		name: __( 'Notes', 'simply-static' ),
		wrap: true,
		selector: ( row ) => (
			<span>{ toInertLogText( row && row.notes ) }</span>
		),
		sortable: true,
		sortFunction: ( rowA, rowB ) =>
			toInertLogText( rowA && rowA.notes ).localeCompare(
				toInertLogText( rowB && rowB.notes )
			),
		width: '28%',
	};

	const columns = isPro()
		? [ ...baseColumns, exportTypeColumn, notesColumn ]
		: [ ...baseColumns, notesColumn ];

	return (
		<div className="log-table-container">
			<Flex>
				<FlexItem>
					<label
						className="screen-reader-text"
						htmlFor="export-search"
					>
						{ __( 'Search export log', 'simply-static' ) }
					</label>
					<input
						id="export-search"
						className="ss-export-log-search"
						type="search"
						placeholder={ __( 'Search…', 'simply-static' ) }
						value={ filterText }
						onChange={ handleSearch }
					/>
				</FlexItem>
			</Flex>
			{ exportLogError && (
				<Notice status="error" isDismissible={ false }>
					<p>{ exportLogError }</p>
				</Notice>
			) }
			<DataTable
				columns={ columns }
				data={ exportLog.static_pages || [] }
				pagination
				paginationServer
				paginationDefaultPage={ exportPage }
				paginationResetDefaultPageToggle={ resetPagination }
				paginationTotalRows={ exportLog.total_static_pages }
				paginationPerPage={ perPageExportLog }
				paginationRowsPerPageOptions={ [ 25, 50, 100, 200 ] }
				progressPending={ loadingExportLog }
				progressComponent={
					<div style={ { padding: '24px', textAlign: 'center' } }>
						<Spinner />
						<div style={ { marginTop: '8px' } }>
							{ __( 'Loading…', 'simply-static' ) }
						</div>
					</div>
				}
				onChangeRowsPerPage={ handlePerRowsChange }
				onChangePage={ handlePageChange }
			/>
		</div>
	);
}

export default ExportLog;
