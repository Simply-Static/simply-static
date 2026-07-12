import GeneralSettings from "../pages/GeneralSettings";
import Diagnostics from "../pages/Diagnostics";
import Utilities from "../pages/Utilities";
import {useState, useEffect, useContext} from "@wordpress/element";
import {
    Flex,
    FlexItem,
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalNavigatorProvider as NavigatorProvider,
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalNavigatorScreen as NavigatorScreen,
    // eslint-disable-next-line @wordpress/no-unsafe-wp-apis
    __experimentalNavigatorButton as NavigatorButton,
    Button,
    Dashicon,
    Card,
    CardBody,
    Notice,
    Animate, __experimentalSpacer as Spacer, SelectControl
} from '@wordpress/components';
import DeploymentSettings from "../pages/DeploymentSettings";
import FormSettings from "../pages/FormSettings";
import SearchSettings from "../pages/SearchSettings";
import DebugSettings from "../pages/DebugSettings";
import IntegrationsSettings from "../pages/IntegrationsSettings";
import UAMSettings from "../pages/UAMSettings";
import Generate from "../pages/Generate";
import Optimize from "../pages/Optimize";
import HideWP from "../pages/HideWP";
import Workflow from "../pages/Workflow";
import {SettingsContext} from "../context/SettingsContext";
import SidebarMultisite from "./SidebarMultisite";
import SidebarSite from "./SidebarSite";
import PromoSidebar from "./PromoSidebar";

const {__} = wp.i18n;

const settingsRoutes = [
    '/',
    '/diagnostics',
    '/general',
    '/deployment',
    '/forms',
    '/search',
    '/optimize',
    '/hide-wp',
    '/workflow',
    '/utilities',
    '/debug',
    '/uam',
    '/integrations',
];

const hashRouteAliases = {
    'activity-log': '/',
    'generate': '/',
    'deploy': '/deployment',
};

const getRouteFromHash = () => {
    if ('undefined' === typeof window || !window.location.hash) {
        return null;
    }

    let hash = window.location.hash.replace(/^#\/?/, '').split('?')[0];

    try {
        hash = decodeURIComponent(hash).trim();
    } catch (e) {
        return null;
    }

    if (!hash) {
        return null;
    }

    if (hashRouteAliases[hash]) {
        return hashRouteAliases[hash];
    }

    const route = `/${hash}`;

    return settingsRoutes.includes(route) ? route : null;
};

const getHashFromRoute = (route) => {
    return '/' === route ? '' : route.replace(/^\//, '');
};

const getRouteFromHistoryState = () => {
    if ('undefined' === typeof window || !window.history || !window.history.state) {
        return null;
    }

    const route = window.history.state.ssRoute;

    return settingsRoutes.includes(route) ? route : null;
};

function SettingsPage() {
    const {
        isRunning,
        isPaused,
        passedChecks,
        isPro,
        showMobileNav,
        setShowMobileNav,
        settings
    } = useContext(SettingsContext);
    const isRouteAvailable = (route) => {
        try {
            if (!settingsRoutes.includes(route)) {
                return false;
            }

            if (options.allowed_pages && !options.allowed_pages.includes(route)) {
                return false;
            }

            return !('/uam' === route && !options.uam_enabled);
        } catch (e) {
            return true;
        }
    }

    const resolveRoute = (route) => {
        return isRouteAvailable(route) ? route : options.initial;
    }

    const getInitialPage = () => {
        const hashRoute = getRouteFromHash();

        if (hashRoute) {
            return resolveRoute(hashRoute);
        }

        return resolveRoute(localStorage.getItem('ss-initial-page') ? localStorage.getItem('ss-initial-page') : options.initial);
    }

    const [activeItem, setActiveItem] = useState(getInitialPage);
    const [initialPage, setInitialPage] = useState(getInitialPage);
    const [initialSet, setInitialSet] = useState(false);

    // UAM enablement follows server-bootstrapped flag; changes require a page reload

    const replaceCurrentHistoryRoute = (route) => {
        if ('undefined' === typeof window || !window.history || !window.history.replaceState) {
            return;
        }

        window.history.replaceState({
            ...window.history.state,
            ssRoute: route,
        }, '', window.location.href);
    }

    useEffect(() => {
        // Change initial page.
        let initialPageRedirect = localStorage.getItem('ss-initial-page');
        const hashRoute = getRouteFromHash();

        if (!initialSet) {
            setInitialSet(true);

            let nextInitialPage = options.initial;

            if (hashRoute) {
                nextInitialPage = hashRoute;
            } else if (initialPageRedirect) {
                nextInitialPage = initialPageRedirect;
                localStorage.removeItem('ss-initial-page');
            }

            const resolvedInitialPage = resolveRoute(nextInitialPage);

            setActiveItem(resolvedInitialPage);
            setInitialPage(resolvedInitialPage);
            replaceCurrentHistoryRoute(resolvedInitialPage);
        }
    }, [options, isRunning, isPaused]);

    useEffect(() => {
        const updateActiveItemFromLocation = () => {
            const route = resolveRoute(getRouteFromHash() || getRouteFromHistoryState() || options.initial);

            setActiveItem(route);
            setInitialPage(route);
        };

        window.addEventListener('hashchange', updateActiveItemFromLocation);
        window.addEventListener('popstate', updateActiveItemFromLocation);

        return () => {
            window.removeEventListener('hashchange', updateActiveItemFromLocation);
            window.removeEventListener('popstate', updateActiveItemFromLocation);
        };
    }, []);

    const selectActiveItem = (route) => {
        setActiveItem(route);

        if ('undefined' === typeof window || !window.history) {
            return;
        }

        const hash = getHashFromRoute(route);

        if (hash) {
            if (window.location.hash !== `#${hash}`) {
                window.history.pushState({
                    ...window.history.state,
                    ssRoute: route,
                }, '', `#${hash}`);
            } else {
                replaceCurrentHistoryRoute(route);
            }
        } else if (window.location.hash) {
            window.history.pushState({
                ...window.history.state,
                ssRoute: route,
            }, '', `${window.location.pathname}${window.location.search}`);
        } else {
            replaceCurrentHistoryRoute(route);
        }
    }

    // No live redirect; visibility updates after settings page reload

    const minHeight = () => {
        return window.innerHeight - ( wpadminbar ? wpadminbar.clientHeight : 0 ) - 1;
    }

    return (
        <div className={"plugin-settings-container"} >
            <NavigatorProvider initialPath={initialPage} style={{minHeight: minHeight() + "px"}}>
				<Button type="button" aria-expanded={showMobileNav} aria-controls="simply-static-mobile-nav" onClick={() => {
                    setShowMobileNav(true);
				}} className={"show-nav"}><Dashicon icon="align-center"/> {__('Toggle menu', 'simply-static')}</Button>
                {showMobileNav &&
					<div id="simply-static-mobile-nav" className="mobile-nav-overlay" role="dialog" aria-modal="true" aria-label={__('Simply Static menu', 'simply-static')}>
                        <div className="mobile-nav-header">
                            <span>{__('Menu', 'simply-static')}</span>
							<Button isSmall variant="tertiary" aria-label={__('Close menu', 'simply-static')} onClick={() => setShowMobileNav(false)}>
                                <Dashicon icon="no-alt" />
                            </Button>
                        </div>
                        <div className="mobile-nav-content">
                            {options.is_network ?
                                <SidebarMultisite />
                                :
                                <SidebarSite setActiveItem={selectActiveItem} activeItem={activeItem} />
                            }
                        </div>
                    </div>
                }
                <Flex>
                    <FlexItem className="sidebar">
                        {options.is_network ?
                             <SidebarMultisite />
                            :
                            <SidebarSite setActiveItem={selectActiveItem} activeItem={activeItem} />
                        }
                    </FlexItem>
                    <FlexItem isBlock={true}>
                        <div className={"plugin-settings"}>
                            {'no' === passedChecks && !options.is_network ?
                                <Animate type="slide-in" options={{origin: 'top'}}>
                                    {() => (

                                        <Notice status="notice" isDismissible={false}
                                                className={activeItem == '/' ? 'diagnostics-notice diagnostics-notice-generate' : 'diagnostics-notice'}>
                                            <p>
                                                {__('There are errors in diagnostics that may negatively affect your static push.', 'simply-static')}<br></br>
                                                {__('Please review them and get them fixed to avoid problems.', 'simply-static')}
                                            </p>
                                            <NavigatorButton isSecondary onClick={() => {
                                                selectActiveItem('/diagnostics')
                                                setShowMobileNav(!showMobileNav);
                                            }}
                                                             className={activeItem === '/diagnostics' ? 'is-active-item' : ''}
                                                             path="/diagnostics">
                                                <Dashicon
                                                    icon="editor-help"/> {__('Visit Diagnostics', 'simply-static')}
                                            </NavigatorButton>
                                        </Notice>
                                    )}
                                </Animate>
                                :
                                ''
                            }
                            {'pro' === options.plan && !isPro() ?
                                <Animate type="slide-in" options={{origin: 'top'}}>
                                    {() => (
                                        <>
                                            <Notice status="error" isDismissible={false}
                                                    className={activeItem == '/' ? 'diagnostics-notice diagnostics-notice-generate' : 'diagnostics-notice'}>
                                                <p>
                                                    {__('You are using the pro version without a valid license.', 'simply-static')}<br></br>
                                                    {__('We have temporarily disabled all the pro features now. Please contact our support to have the problem solved.', 'simply-static')}
                                                </p>
                                                <Button isPrimary href={"https://simplystatic.com/support/"}
                                                        target="_blank">Contact Support</Button>
                                            </Notice>
                                            <Spacer margin={"5px"}/>
                                        </>
                                    )}
                                </Animate>
                                :
                                ''
                            }
                            {activeItem === '/' &&
                                <NavigatorScreen path="/">
                                    <Generate/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/diagnostics' &&
                                <NavigatorScreen path="/diagnostics">
                                    <Diagnostics/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/general' &&
                                <NavigatorScreen path="/general">
                                    <GeneralSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/deployment' &&
                                <NavigatorScreen path="/deployment">
                                    <DeploymentSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/forms' &&
                                <NavigatorScreen path="/forms">
                                    <FormSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/search' &&
                                <NavigatorScreen path="/search">
                                    <SearchSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/optimize' &&
                                <NavigatorScreen path="/optimize">
                                    <Optimize/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/hide-wp' &&
                                <NavigatorScreen path="/hide-wp">
                                    <HideWP/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/workflow' &&
                                <NavigatorScreen path="/workflow">
                                    <Workflow/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/utilities' &&
                                <NavigatorScreen path="/utilities">
                                    <Utilities/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/debug' &&
                                <NavigatorScreen path="/debug">
                                    <DebugSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/uam' && options.uam_enabled &&
                                <NavigatorScreen path="/uam">
                                    <UAMSettings/>
                                </NavigatorScreen>
                            }
                            {activeItem === '/integrations' &&
                                <NavigatorScreen path="/integrations">
                                    <IntegrationsSettings/>
                                </NavigatorScreen>
                            }
                        </div>
                    </FlexItem>
                    {'pro' !== options.plan &&
                        <FlexItem className={"promo-sidebar-container"}>
                            <PromoSidebar/>
                        </FlexItem>
                    }
                </Flex>
            </NavigatorProvider>
        </div>
    )
}

export default SettingsPage;
