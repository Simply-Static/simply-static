global.wp = {
	i18n: {
		__: ( value ) => value,
		sprintf: ( value, number ) => value.replace( '%d', number ),
	},
};

global.options = {
	allowed_pages: [],
	builds: {},
	can_export_languages: false,
	integrations: { multilingual: { can_run: false } },
	is_multisite: false,
	is_network: false,
	languages: [],
	logo: '',
	plan: 'free',
	selectable_sites: [],
	uam_enabled: false,
};

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '../../hooks/useInterval', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	const React = require( '@wordpress/element' );
	const wrap = ( tag ) => ( { children, ...props } ) =>
		React.createElement( tag, props, children );

	return {
		__experimentalNavigatorButton: wrap( 'button' ),
		__experimentalSpacer: wrap( 'div' ),
		Animate: ( { children } ) =>
			'string' === typeof children ? children : children( {} ),
		Button: ( { children, href, target, onClick, disabled, className } ) =>
			href
				? React.createElement( 'a', { href, target, className }, children )
				: React.createElement(
						'button',
						{ type: 'button', onClick, disabled, className },
						children
				  ),
		Card: wrap( 'div' ),
		CardBody: wrap( 'div' ),
		Dashicon: ( { icon } ) =>
			React.createElement( 'span', { 'data-icon': icon } ),
		Notice: ( { children, className } ) =>
			React.createElement( 'div', { role: 'alert', className }, children ),
		SelectControl: ( {
			children,
			value,
			disabled,
			onChange,
			className,
			options: controlOptions = [],
		} ) =>
			React.createElement(
				'select',
				{
					value,
					disabled,
					className,
					onChange: ( event ) => onChange( event.target.value ),
				},
				children ||
					controlOptions.map( ( option ) =>
						React.createElement(
							'option',
							{ key: option.value, value: option.value },
							option.label
						)
					)
			),
		Tooltip: wrap( 'div' ),
	};
} );

jest.mock( './GenerateButtons', () => {
	const React = require( '@wordpress/element' );

	return {
		__esModule: true,
		default: ( { cancelExport, pauseExport, resumeExport } ) =>
			React.createElement(
				'div',
				null,
				React.createElement(
					'button',
					{ type: 'button', onClick: cancelExport },
					'Cancel'
				),
				React.createElement(
					'button',
					{ type: 'button', onClick: pauseExport },
					'Pause'
				),
				React.createElement(
					'button',
					{ type: 'button', onClick: resumeExport },
					'Resume'
				)
			),
	};
} );

jest.mock( './EnvironmentSidebar', () => ( {
	__esModule: true,
	default: () => null,
} ) );

jest.mock( './VersionInfo', () => ( {
	__esModule: true,
	default: () => null,
} ) );

const { act, fireEvent, render, screen, waitFor } = require( '@testing-library/react' );
const apiFetch = require( '@wordpress/api-fetch' ).default;
const { SettingsContext } = require( '../context/SettingsContext' );
const SidebarSite = require( './SidebarSite' ).default;

const actions = [
	{
		label: 'Cancel',
		path: '/simplystatic/v1/cancel-export',
		expected: {
			setIsResumed: false,
			setIsPaused: false,
			setIsRunning: false,
		},
	},
	{
		label: 'Pause',
		path: '/simplystatic/v1/pause-export',
		expected: {
			setIsRunning: false,
			setIsResumed: false,
			setIsPaused: true,
		},
	},
	{
		label: 'Resume',
		path: '/simplystatic/v1/resume-export',
		expected: {
			setIsResumed: true,
			setIsPaused: false,
			setIsRunning: true,
		},
	},
];

let actionResponse;

function renderSidebar( contextOverrides = {} ) {
	const setters = {
		setIsPaused: jest.fn(),
		setIsResumed: jest.fn(),
		setIsRunning: jest.fn(),
	};
	const context = {
		blogId: 17,
		canRunIntegration: () => false,
		getSettings: jest.fn(),
		isDelayed: 0,
		isPaused: false,
		isPro: () => false,
		isResumed: false,
		isRollbackRunning: false,
		isRunning: true,
		settings: {},
		setShowMobileNav: jest.fn(),
		setSnapshotRollback: jest.fn(),
		showMobileNav: false,
		snapshotRollback: { running: false },
		updateFromNetwork: jest.fn(),
		...setters,
		...contextOverrides,
	};

	const rendered = render(
		<SettingsContext.Provider value={ context }>
			<SidebarSite activeItem="/" setActiveItem={ jest.fn() } />
		</SettingsContext.Provider>
	);

	return { ...rendered, context, setters };
}

describe( 'SidebarSite actions', () => {
	beforeEach( () => {
		options.is_multisite = false;
		options.selectable_sites = [];
		actionResponse = JSON.stringify( { status: 200 } );
		apiFetch.mockReset();
		apiFetch.mockImplementation( ( { path } ) => {
			if ( '/simplystatic/v1/unpushed-changes' === path ) {
				return Promise.resolve(
					JSON.stringify( { status: 200, data: { total: 0 } } )
				);
			}
			if ( '/simplystatic/v1/check-can-run' === path ) {
				return Promise.resolve( JSON.stringify( { can_run: true } ) );
			}

			return Promise.resolve( actionResponse );
		} );
		global.alert = jest.fn();
	} );

	it.each(
		actions.flatMap( ( action ) =>
			[ 409, 500 ].map( ( status ) => ( { ...action, status } ) )
		)
	)(
		'does not mutate state when $label returns encoded status $status',
		async ( action ) => {
			const { status } = action;
			const message = `<img src=x onerror="alert(1)">${ action.label } failed`;
			actionResponse = JSON.stringify( { status, message } );
			const { container, setters } = renderSidebar();

			fireEvent.click( screen.getByRole( 'button', { name: action.label } ) );

			await waitFor( () =>
				expect( global.alert ).toHaveBeenCalledWith( message )
			);
			expect( container.querySelector( '[onerror]' ) ).toBeNull();
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: action.path,
				method: 'POST',
				data: { blog_id: 17 },
			} );
			Object.values( setters ).forEach( ( setter ) =>
				expect( setter ).not.toHaveBeenCalled()
			);
		}
	);

	it.each( actions )(
		'updates state only after an explicit success for $label',
		async ( action ) => {
			actionResponse = JSON.stringify( { status: '200' } );
			const { setters } = renderSidebar();

			fireEvent.click( screen.getByRole( 'button', { name: action.label } ) );

			await waitFor( () => {
				Object.entries( action.expected ).forEach( ( [ setter, value ] ) =>
					expect( setters[ setter ] ).toHaveBeenCalledWith( value )
				);
			} );
			expect( global.alert ).not.toHaveBeenCalled();
		}
	);

	it( 'rejects a response without an explicit success status', async () => {
		actionResponse = JSON.stringify( { message: 'Ambiguous response' } );
		const { setters } = renderSidebar();

		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		await waitFor( () =>
			expect( global.alert ).toHaveBeenCalledWith( 'Ambiguous response' )
		);
		Object.values( setters ).forEach( ( setter ) =>
			expect( setter ).not.toHaveBeenCalled()
		);
	} );

	it( 'waits for a network import and clears its busy state on failure', async () => {
		options.is_multisite = true;
		options.selectable_sites = [ { blog_id: 2, name: 'Second site' } ];
		let rejectImport;
		const updateFromNetwork = jest.fn(
			() =>
				new Promise( ( resolve, reject ) => {
					rejectImport = reject;
				} )
		);
		const message = '<img src=x onerror="alert(1)">Import failed';
		const { container } = renderSidebar( { updateFromNetwork } );

		await waitFor( () =>
			expect( screen.getAllByRole( 'combobox' ) ).toHaveLength( 2 )
		);
		fireEvent.change( screen.getAllByRole( 'combobox' )[ 1 ], {
			target: { value: '2' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Import Settings' } )
		);

		await waitFor( () =>
			expect( updateFromNetwork ).toHaveBeenCalledWith( '2' )
		);
		expect(
			screen.getByRole( 'button', { name: 'Import Settings' } ).disabled
		).toBe( true );
		expect( screen.getByText( 'Importing settings…' ) ).not.toBeNull();

		await act( async () => {
			rejectImport( { data: { status: 500, message } } );
		} );

		await waitFor( () =>
			expect( global.alert ).toHaveBeenCalledWith( message )
		);
		expect(
			screen.queryByText( 'Importing settings…' )
		).toBeNull();
		expect(
			screen.getByRole( 'button', { name: 'Import Settings' } ).disabled
		).toBe( false );
		expect( container.querySelector( '[onerror]' ) ).toBeNull();
	} );
} );
