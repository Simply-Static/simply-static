import './style.scss';

/**
 * External dependencies
 */
import debounce from 'debounce';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import {__} from '@wordpress/i18n';

const ssPluginInstall = {
    /**
     * Init function.
     */
    init() {
        if (this.isSSCompatibleTab()) {
            this.removeAdditionalInfo();
        } else {
            this.addSSMessage();
        }
        this.addSSMessageInSearchResult();
    },

    /**
     * Check if "Simply Static Compatible" tab is open or not.
     *
     * @return {boolean} Is Simply Static-compatible tab.
     */
    isSSCompatibleTab() {
        const queryParams = new URLSearchParams(
            window.location.search.substr(1)
        );
        return queryParams.get('tab') === ssPlugins.SS_COMPATIBLE;
    },

    /**
     * Add message for AMP Compatibility in SS-compatible plugins card after search result comes in.
     */
    addSSMessageInSearchResult() {
        const pluginFilterForm = document.getElementById('plugin-filter');
        const pluginInstallSearch = document.querySelector(
            '.plugin-install-php .wp-filter-search'
        );
        if (!pluginFilterForm || !pluginInstallSearch) {
            return;
        }

        const startSearchResults = debounce(() => {
            pluginInstallSearch.removeEventListener(
                'input',
                startSearchResults,
                {once: true}
            ); // For IE 11 which doesn't support once events.

            // Replace the class for our custom SS-compatible tab once doing a search.
            const wrap = document.querySelector(
                '.plugin-install-tab-ss-compatible'
            );
            if (wrap) {
                wrap.classList.remove('plugin-install-tab-simply-static-compatible');
                wrap.classList.add('plugin-install-tab-search-result');
            }

            // Start watching for changes the first time a search is being made.
            const mutationObserver = new MutationObserver(() => {
                this.addSSMessage();
            });
            mutationObserver.observe(pluginFilterForm, {childList: true});
        }, 1000); // See timeout in core: <https://github.com/WordPress/WordPress/blob/b87617e2719d114d123a88ed7e489170f0204735/wp-admin/js/updates.js#L2578>

        pluginInstallSearch.addEventListener('input', startSearchResults, {
            once: true,
        });
    },

    /**
     * Add message for SS Compatibility in SS-compatible plugins card.
     */
    addSSMessage() {
        for (const pluginSlug of ssPlugins.SS_PLUGINS) {
            const pluginCardElement = document.querySelector(
                `.plugin-card.plugin-card-${pluginSlug}`
            );

            if (!pluginCardElement) {
                continue;
            }

            // Skip cards that have already been processed.
            if (
                pluginCardElement.classList.contains(
                    'ss-extension-card-message'
                )
            ) {
                continue;
            }

            const messageElement = document.createElement('div');
            const iconElement = document.createElement('span');
            const tooltipElement = document.createElement('span');

            messageElement.classList.add('ss-extension-card-message');
            iconElement.classList.add('ss-logo-icon');
            tooltipElement.classList.add('tooltiptext');

            tooltipElement.append(
                __('This is known to work well with the Simply Static plugin.', 'simply-static')
            );

            messageElement.append(iconElement);
            messageElement.append(tooltipElement);

            pluginCardElement.appendChild(messageElement);
        }
    },

    /**
     * Remove the additional info from the plugin card in the "AMP Compatible" tab.
     */
    removeAdditionalInfo() {
        const pluginCardBottom = document.querySelectorAll(
            '.plugin-install-tab-ss-compatible .plugin-card-bottom'
        );
        for (const elementNode of pluginCardBottom) {
            elementNode.remove();
        }
    },
};

domReady(() => {
    ssPluginInstall.init();
});