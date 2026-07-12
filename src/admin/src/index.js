import { createRoot } from '@wordpress/element';

import Settings from './settings/Settings';

if ( window.options && window.options.screen === 'simplystatic-settings' ) {
	const container = document.getElementById( 'simplystatic-settings' );
	if ( container ) {
		const settings = createRoot( container );
		settings.render( <Settings /> );
	}
}
