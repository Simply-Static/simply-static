'use strict';

/**
 * Simply Static Free – Single Push Teaser
 *
 * Injects a disabled "Push" button (with a Pro-upsell tooltip) into
 * Gutenberg, Classic Editor, and Elementor – mirroring the placement
 * used by Simply Static Pro's real single-push button.
 */

var topDocument = (window.top && window.top.document) ? window.top.document : document;

function qs(selector, root) { return (root || document).querySelector(selector); }

document.addEventListener('DOMContentLoaded', function () {

    var TOOLTIP = 'You need Simply Static Pro to use Single Push.';

    // -------------------------------------------------------
    // Gutenberg: inject into the header settings area
    // -------------------------------------------------------
    function injectGutenbergButton() {
        var candidates = [
            '.edit-post-header__settings',
            '.edit-post-header__toolbar',
            '.edit-post-header .edit-post-header__toolbar',
            '.interface-interface-skeleton__header .edit-post-header__settings',
            '.interface-interface-skeleton__header .interface-pinned-items',
            '.interface-interface-skeleton__header .interface-interface-skeleton__actions'
        ];
        var container = null;
        for (var i = 0; i < candidates.length; i++) {
            var el = qs(candidates[i]);
            if (el) { container = el; break; }
        }
        if (!container) return false;
        if (qs('.ssp-export-button', container)) return true;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'components-button is-primary ssp-export-button';
        btn.textContent = 'Push';
        btn.disabled = true;
        btn.title = TOOLTIP;
        container.insertBefore(btn, container.firstChild);
        return true;
    }

    // -------------------------------------------------------
    // Classic Editor: inject near Publish / Update
    // -------------------------------------------------------
    function injectClassicButton() {
        var actions = qs('#major-publishing-actions');
        if (!actions) return false;
        if (qs('.ssp-export-button', actions)) return true;

        var publish = qs('#publish', actions);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'button ssp-export-button';
        btn.style.marginLeft = '8px';
        btn.textContent = 'Push';
        btn.disabled = true;
        btn.title = TOOLTIP;
        if (publish) {
            publish.insertAdjacentElement('afterend', btn);
        } else {
            actions.appendChild(btn);
        }
        return true;
    }

    // -------------------------------------------------------
    // Elementor: floating bottom-right button
    // -------------------------------------------------------
    (function () {
        var STYLE_ID = 'ssp-floating-export-style';

        function ensureFloatingStyles(doc) {
            try {
                doc = doc || topDocument || document;
                if (doc.getElementById(STYLE_ID)) return;
                var css = '' +
                    '.ssp-floating-export{position:fixed;right:20px;bottom:20px;z-index:10010;background:#6804cc;color:#fff;border:0;border-radius:28px;padding:10px 16px;font-weight:600;line-height:1;cursor:not-allowed;box-shadow:0 6px 18px rgba(0,0,0,.15);opacity:.7;}' +
                    '@media (max-width: 782px){ .ssp-floating-export{ right:14px; bottom:14px; } }';
                var style = doc.createElement('style');
                style.id = STYLE_ID;
                style.appendChild(document.createTextNode(css));
                (doc.head || document.documentElement).appendChild(style);
            } catch (e) {}
        }

        function isElementorEditorContext() {
            try {
                var search = (window.top && window.top.location && window.top.location.search) ? window.top.location.search : window.location.search;
                var params = new URLSearchParams(search || '');
                if (params.get('action') === 'elementor') return true;
            } catch (e) {}
            return false;
        }

        function injectFloatingButton() {
            if (!isElementorEditorContext()) return false;
            ensureFloatingStyles();
            var existing = topDocument.querySelector('.ssp-floating-export');
            if (existing) return true;

            var btn = topDocument.createElement('button');
            btn.type = 'button';
            btn.className = 'ssp-floating-export';
            btn.setAttribute('aria-label', 'Simply Static Push');
            btn.textContent = 'Push';
            btn.disabled = true;
            btn.title = TOOLTIP;
            try { (topDocument.body || document.body).appendChild(btn); return true; } catch (e) { return false; }
        }

        function setupFloatingLifecycle() {
            injectFloatingButton();
            try {
                if (window.elementor && elementor.on) {
                    elementor.on('editor:loaded', function () { setTimeout(injectFloatingButton, 50); });
                    elementor.on('document:loaded', function () { setTimeout(injectFloatingButton, 50); });
                    elementor.on('preview:loaded', function () { setTimeout(injectFloatingButton, 50); });
                }
            } catch (e) {}
            try {
                var obs = new MutationObserver(function () { injectFloatingButton(); });
                obs.observe(topDocument.body || document.body, { childList: true, subtree: true });
            } catch (e) {}
        }

        if (window.requestIdleCallback) { requestIdleCallback(setupFloatingLifecycle); } else { setTimeout(setupFloatingLifecycle, 200); }
    })();

    // -------------------------------------------------------
    // Brand styles (shared with Pro – same selectors)
    // -------------------------------------------------------
    function ensureBrandStyles() {
        if (topDocument.getElementById('ssp-export-style') || document.getElementById('ssp-export-style')) return;
        var css = '.ssp-export-button{background-color:#6804cc!important;border-color:#6804cc!important;color:#fff!important}.ssp-export-button[disabled]{opacity:.7;cursor:not-allowed}';
        var style = topDocument.createElement('style');
        style.id = 'ssp-export-style';
        style.appendChild(document.createTextNode(css));
        (topDocument.head || document.head).appendChild(style);
    }

    ensureBrandStyles();

    // -------------------------------------------------------
    // Inject into Gutenberg & Classic editors
    // -------------------------------------------------------
    function tryInjectAll() {
        injectGutenbergButton();
        injectClassicButton();
    }

    tryInjectAll();
    if (window.requestIdleCallback) { requestIdleCallback(function () { tryInjectAll(); }); } else { setTimeout(function () { tryInjectAll(); }, 250); }
    try { var injectObserver = new MutationObserver(function () { tryInjectAll(); }); injectObserver.observe(document.body, { childList: true, subtree: true }); } catch (e) {}
});
