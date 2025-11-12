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
import Generate from "../pages/Generate";
import Optimize from "../pages/Optimize";
import Workflow from "../pages/Workflow";
import {SettingsContext} from "../context/SettingsContext";
import SidebarMultisite from "./SidebarMultisite";
import SidebarSite from "./SidebarSite";

const {__} = wp.i18n;

function SettingsPage() {
    const {
        isRunning,
        isPaused,
        passedChecks,
        isPro,
        showMobileNav,
        setShowMobileNav
    } = useContext(SettingsContext);
    const [activeItem, setActiveItem] = useState({activeItem: "/"});
    const [initialPage, setInitialPage] = useState(localStorage.getItem('ss-initial-page') ? localStorage.getItem('ss-initial-page') : options.initial);
    const [initialSet, setInitialSet] = useState(false);


    useEffect(() => {
        // Change initial page.
        let initialPageRedirect = localStorage.getItem('ss-initial-page');

        if (!initialSet) {
            setInitialSet(true);

            if (initialPageRedirect) {
                setActiveItem(initialPageRedirect);
                setInitialPage(initialPageRedirect);
                localStorage.removeItem('ss-initial-page');
            } else {
                setActiveItem(options.initial);
                setInitialPage(options.initial);
            }
        }
    }, [options, isRunning, isPaused]);

    const minHeight = () => {
        return window.innerHeight - ( wpadminbar ? wpadminbar.clientHeight : 0 ) - 1;
    }

    return (
        <div className={"plugin-settings-container"} >
            <NavigatorProvider initialPath={initialPage} style={{minHeight: minHeight() + "px"}}>
                <Flex>
                    <a onClick={() => {
                        setShowMobileNav(!showMobileNav);
                    }} className={"show-nav"}><Dashicon icon="align-center"/> {__('Toggle menu', 'simply-static')}</a>
                    <FlexItem className={showMobileNav ? 'toggle-nav sidebar' : 'sidebar'}>
                        {options.is_network ?
                             <SidebarMultisite />
                            :
                            <SidebarSite setActiveItem={setActiveItem} activeItem={activeItem} />
                        }
                    </FlexItem>
                    <FlexItem isBlock={true} className={!showMobileNav ? 'toggle-nav' : ''}>
                        <div className={"plugin-settings"}>
                            {'no' === passedChecks && !options.is_network ?
                                <Animate type="slide-in" options={{origin: 'top'}}>
                                    {() => (

                                        <Notice status="notice" isDismissible={false}
                                                className={activeItem == '/' ? 'diagnostics-notice diagnostics-notice-generate' : 'diagnostics-notice'}>
                                            <p>
                                                {__('There are errors in diagnostics that may negatively affect your static export.', 'simply-static')}<br></br>
                                                {__('Please review them and get them fixed to avoid problems.', 'simply-static')}
                                            </p>
                                            <NavigatorButton isSecondary onClick={() => {
                                                setActiveItem('/diagnostics')
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
                            {activeItem === '/integrations' &&
                                <NavigatorScreen path="/integrations">
                                    <IntegrationsSettings/>
                                </NavigatorScreen>
                            }
                        </div>
                    </FlexItem>
                </Flex>
            </NavigatorProvider>
        </div>
    )
}

export default SettingsPage;
