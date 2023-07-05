// eslint-disable-next-line import/no-extraneous-dependencies
import { createRoot } from '@wordpress/element';

import Settings from './settings/Settings'

if (options.screen === 'simplystatic-settings') {
    let settings = createRoot(document.getElementById('simplystatic-settings'));
    settings.render(<Settings/>);
}

