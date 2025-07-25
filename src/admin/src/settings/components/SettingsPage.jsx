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
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import EnvironmentSidebar from "./EnvironmentSidebar";

const {__} = wp.i18n;

function SettingsPage() {
    const {
        isRunning,
        setIsRunning,
        isResumed,
        setIsResumed,
        isPaused,
        setIsPaused,
        blogId,
        settings,
        updateFromNetwork,
        getSettings,
        passedChecks,
        isPro,
        isStudio,
        canRunIntegration,
        showMobileNav,
        setShowMobileNav
    } = useContext(SettingsContext);
    const [activeItem, setActiveItem] = useState({activeItem: "/"});
    const [initialPage, setInitialPage] = useState(localStorage.getItem('ss-initial-page') ? localStorage.getItem('ss-initial-page') : options.initial);
    const [initialSet, setInitialSet] = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);
    const [selectedCopySite, setSelectedCopySite] = useState('current');
    const [selectablesSites, setSelectableSites] = useState([]);
    const [isUpdatingFromNetwork, setIsUpdatingFromNetwork] = useState(false);
    const [selectedExportType, setSelectedExportType] = useState('export');

    const runUpdateFromNetwork = (blogId) => {
        // Update settings from selected blog_id.
        updateFromNetwork(blogId);

        setIsUpdatingFromNetwork(true);

        setTimeout(function () {
            setIsUpdatingFromNetwork(false);
            window.location.reload();
        }, 2000);
    }

    useEffect(() => {
        setDisabledButton(isRunning || isPaused);

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

        if (options.selectable_sites && !options.is_network && options.is_multisite) {
            let sites = options.selectable_sites.map(function (site) {
                return {label: `${site.name}`, value: site.blog_id}
            });

            sites.unshift({label: __('Use current settings', 'simply-static'), value: 'current'});
            setSelectableSites(sites);
        }

        // Maybe set to update.
        if (options.last_export_end && ! isRunning) {
            setSelectedExportType('update');
        }

    }, [options, isRunning, isPaused]);

    const startExport = () => {
        setDisabledButton(true);
        setIsResumed(false);
        setIsPaused(false);

        apiFetch({
            path: '/simplystatic/v1/start-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
                'type': selectedExportType
            }
        }).then(resp => {
            setIsRunning(true);
        });
    }

    const cancelExport = () => {
        apiFetch({
            path: '/simplystatic/v1/cancel-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        }).then(resp => {
            setIsResumed(false);
            setIsPaused(false)
            setIsRunning(false);
            setDisabledButton(false);
        });
    }

    const pauseExport = () => {
        apiFetch({
            path: '/simplystatic/v1/pause-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        }).then(resp => {
            setIsRunning(false);
            setIsResumed(false);
            setIsPaused(true);
        });
    }

    const resumeExport = () => {
        apiFetch({
            path: '/simplystatic/v1/resume-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        }).then(resp => {
            setIsResumed(true);
            setIsPaused(false);
            setIsRunning(true);
        });
    }

    let buildOptions = '';
    if (Object.keys(options.builds).length) {
        const builds = Object.keys(options.builds).map((id) => <option value={id}>{options.builds[id]}</option>);

        // Sort builds alphabetically
        builds.sort((a, b) => {
            return a.props.children.localeCompare(b.props.children);
        });

        buildOptions = <optgroup label="Builds">
            {builds}
        </optgroup>
    }

    return (
        <div className={"plugin-settings-container"}>
            <NavigatorProvider initialPath={initialPage}>
                <Flex>
                    <a onClick={() => {
                        setShowMobileNav(!showMobileNav);
                    }} className={"show-nav"}><Dashicon icon="align-center"/> {__('Toggle menu', 'simply-static')}</a>
                    <FlexItem className={showMobileNav ? 'toggle-nav sidebar' : 'sidebar'}>
                        {options.is_network ?
                            <Card className={"plugin-nav"}>
                                <div className={"plugin-logo"}>
                                    <img alt="Logo"
                                         src={options.logo}/>
                                </div>
                                {'pro' === options.plan && isPro() ?
                                    <p className={"version-number"}>
                                        Free: <b>{options.version}</b><br></br>
                                        Pro: <b>{options.version_pro}</b>
                                    </p>
                                    :
                                    <p className={"version-number"}>Version: <b>{options.version}</b></p>
                                }
                                <div className={`generate-container ${disabledButton ? 'generating' : ''}`}>
                                    {!disabledButton && <Button onClick={() => {
                                        setSelectedExportType('export');
                                        startExport();
                                    }}
                                                                disabled={disabledButton}
                                                                className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                    >
                                        {!disabledButton && [<Dashicon icon="update"/>,
                                            __('Generate', 'simply-static')
                                        ]}

                                        {disabledButton && [<Dashicon icon="update spin"/>,
                                            __('Generating...', 'simply-static'),
                                        ]}
                                    </Button>}
                                    {disabledButton && <>
                                        {!isPaused && <Button
                                            label={__('Pause', 'simply-static')}
                                            showToolTip={true}
                                            className={"ss-generate-media-button"}
                                            onClick={() => pauseExport()}>
                                            <Dashicon icon={"controls-pause"}/>
                                        </Button>
                                        }
                                        {isPaused && <Button
                                            label={__('Resume', 'simply-static')}
                                            showToolTip={true}
                                            className={"ss-generate-media-button"}
                                            onClick={() => resumeExport()}>
                                            <Dashicon icon={"controls-play"}/>
                                        </Button>
                                        }
                                        <Button
                                            onClick={() => cancelExport()}
                                            label={__('Cancel', 'simply-static')}
                                            className={"ss-generate-cancel-button"}
                                            showToolTip={true}
                                        >
                                            <Dashicon icon={'no'}/>
                                        </Button>
                                    </>}

                                </div>
                                <Spacer margin={5}/>
                                <Button href="https://simplystatic.com/changelogs/" target="_blank">
                                    <Dashicon icon="editor-ul"/> {__('Changelog', 'simply-static')}
                                </Button>
                                <Button href="https://docs.simplystatic.com" target="_blank">
                                    <Dashicon icon="admin-links"/> {__('Documentation', 'simply-static')}
                                </Button>
                                {'free' === options.plan &&
                                    <Button href="https://simplystatic.com" target="_blank">
                                        <Dashicon
                                            icon="admin-site-alt3"/>Simply Static Pro
                                    </Button>
                                }
                            </Card>
                            :
                            <Card className={"plugin-nav"}>
                                <div className={"plugin-logo"}>
                                    <img alt="Logo"
                                         src={options.logo}/>
                                </div>
                                {'pro' === options.plan && isPro() ?
                                    <>
                                        {isStudio() ?
                                            <p className={"version-number"}>
                                                Free: <b>{options.version}</b><br></br>
                                                Pro: <b>{options.version_pro}</b><br></br>
                                                Studio: <b>{options.version_studio}</b>
                                            </p>
                                            :
                                            <p className={"version-number"}>
                                                Free: <b>{options.version}</b><br></br>
                                                Pro: <b>{options.version_pro}</b>
                                            </p>
                                        }
                                    </>
                                    :
                                    <p className={"version-number"}>Version: <b>{options.version}</b></p>
                                }

                                <div className={`generate-container ${disabledButton ? 'generating' : ''}`}>
                                    <SelectControl
                                        className={'generate-type'}
                                        value={selectedExportType}
                                        disabled={disabledButton}
                                        onChange={(value) => {
                                            setSelectedExportType(value);
                                        }}
                                    >
                                        <option value="export">{__('Export', 'simply-static')}</option>
                                        {'zip' !== settings.delivery_method && 'tiiny' !== settings.delivery_method &&
                                            <>
                                                {'pro' === options.plan && isPro() ?
                                                    <option value="update">{__('Export Changes', 'simply-static')}</option>
                                                    :
                                                    <option disabled
                                                            value="update">{__('Export Changes (Requires Simply Static Pro)', 'simply-static')}</option>
                                                }
                                            </>
                                        }
                                        {buildOptions}
                                    </SelectControl>
                                    <div className="generate-buttons-container">
                                        {!disabledButton && <Button onClick={() => {
                                            startExport();
                                        }}
                                                                    disabled={disabledButton}
                                                                    className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                        >
                                            {!disabledButton && [<Dashicon icon="update"/>,
                                                __('Generate', 'simply-static')
                                            ]}
                                            {disabledButton && <Dashicon icon="update spin"/>}
                                        </Button>}
                                        {disabledButton && <>
                                            {!isPaused && <Button
                                                label={__('Pause', 'simply-static')}
                                                className={"ss-generate-media-button"}
                                                showToolTip={true}
                                                onClick={() => pauseExport()}>
                                                <Dashicon icon={"controls-pause"}/>
                                            </Button>
                                            }
                                            {isPaused && <Button
                                                label={__('Resume', 'simply-static')}
                                                className={"ss-generate-media-button"}
                                                showToolTip={true}
                                                onClick={() => resumeExport()}>
                                                <Dashicon icon={"controls-play"}/>
                                            </Button>
                                            }
                                            <Button
                                                onClick={() => cancelExport()}
                                                label={__('Cancel', 'simply-static')}
                                                className={"ss-generate-cancel-button"}
                                                showToolTip={true}
                                            >
                                                <Dashicon icon={'no'}/>
                                            </Button>
                                        </>}
                                    </div>
                                </div>
                                <CardBody>
                                    {'pro' === options.plan && isPro() &&
                                        <>
                                            {(!options.is_network && canRunIntegration('environments')) &&
                                                <EnvironmentSidebar isRunning={isRunning} getSettings={getSettings}/>
                                            }
                                        </>
                                    }
                                    {!options.is_network && options.is_multisite &&
                                        <>
                                            <h4 className={"settings-headline"}> {__('Import', 'simply-static')}</h4>
                                            <SelectControl
                                                value={selectedCopySite}
                                                options={selectablesSites}
                                                help={__('Choose a subsite to import settings from.', 'simply-static')}
                                                onChange={(blog_id) => {
                                                    setSelectedCopySite(blog_id);
                                                }}
                                            />
                                            {selectedCopySite !== 'current' &&
                                                <Button isPrimary onClick={() => {
                                                    runUpdateFromNetwork(selectedCopySite);
                                                }}>{__('Import Settings', 'simply-static')}</Button>
                                            }
                                            {isUpdatingFromNetwork ?
                                                <Animate type="slide-in" options={{origin: 'top'}}>
                                                    {() => (
                                                        <Notice status="success" isDismissible={false}
                                                                className={"upgrade-network-notice"}>
                                                            <p>
                                                                {__('Settings successfully imported.', 'simply-static')}
                                                            </p>
                                                        </Notice>
                                                    )}
                                                </Animate>
                                                :
                                                ''
                                            }
                                        </>
                                    }
                                    <h4 className={"settings-headline"}> {__('Tools', 'simply-static')}</h4>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                                     path="/">
                                        <Dashicon icon="update"/> {__('Activity Log', 'simply-static')}
                                    </NavigatorButton>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/diagnostics')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/diagnostics' ? 'is-active-item' : ''}
                                                     path="/diagnostics">
                                        <Dashicon icon="bell"/> {__('Diagnostics', 'simply-static')}
                                    </NavigatorButton>
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}> {__('Settings', 'simply-static')}</h4>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/general')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/general' ? 'is-active-item' : ''}
                                                     path="/general">
                                        <Dashicon icon="admin-generic"/> {__('General', 'simply-static')}
                                    </NavigatorButton>
                                    {!options.is_network && !options.hidden_settings.includes('deployment') &&
                                        <NavigatorButton onClick={() => {
                                            setActiveItem('/deployment')
                                            setShowMobileNav(!showMobileNav);
                                        }}
                                                         className={activeItem === '/deployment' ? 'is-active-item' : ''}
                                                         path="/deployment">
                                            <Dashicon icon="migrate"/> {__('Deploy', 'simply-static')}
                                        </NavigatorButton>
                                    }
                                    {!options.is_network &&
                                        <>
                                            <NavigatorButton onClick={() => {
                                                setActiveItem('/forms')
                                                setShowMobileNav(!showMobileNav);
                                            }}
                                                             className={activeItem === '/forms' ? 'is-active-item' : ''}
                                                             path="/forms">
                                                <Dashicon icon="align-center"/> {__('Forms', 'simply-static')}
                                            </NavigatorButton>
                                            <NavigatorButton onClick={() => {
                                                setActiveItem('/search')
                                                setShowMobileNav(!showMobileNav);
                                            }}
                                                             className={activeItem === '/search' ? 'is-active-item' : ''}
                                                             path="/search">
                                                <Dashicon icon="search"/> {__('Search', 'simply-static')}
                                            </NavigatorButton>
                                            <NavigatorButton onClick={() => {
                                                setActiveItem('/optimize')
                                                setShowMobileNav(!showMobileNav);
                                            }}
                                                             className={activeItem === '/optimize' ? 'is-active-item' : ''}
                                                             path="/optimize">
                                                <Dashicon icon="dashboard"/> {__('Optimize', 'simply-static')}
                                            </NavigatorButton>
                                        </>
                                    }
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}> {__('Advanced', 'simply-static')}</h4>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/integrations')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/integrations' ? 'is-active-item' : ''}
                                                     path="/integrations">
                                        <Dashicon icon="block-default"/> {__('Integrations', 'simply-static')}
                                    </NavigatorButton>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/utilities')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/utilities' ? 'is-active-item' : ''}
                                                     path="/utilities">
                                        <Dashicon icon="admin-tools"/> {__('Utilities', 'simply-static')}
                                    </NavigatorButton>
                                    <NavigatorButton onClick={() => {
                                        setActiveItem('/debug')
                                        setShowMobileNav(!showMobileNav);
                                    }}
                                                     className={activeItem === '/debug' ? 'is-active-item' : ''}
                                                     path="/debug">
                                        <Dashicon icon="editor-help"/> {__('Debug', 'simply-static')}
                                    </NavigatorButton>
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}>Learn</h4>
                                    <Button href="https://docs.simplystatic.com" target="_blank">
                                        <Dashicon icon="admin-links"/> {__('Documentation', 'simply-static')}
                                    </Button>
                                    <Button
                                        href="https://www.youtube.com/playlist?list=PLcpe8_rNg8U5g1gCOa0Ge6T17f50nSvmg"
                                        target="_blank">
                                        <Dashicon icon="format-video"/> {__('Video Course', 'simply-static')}
                                    </Button>
                                    <Button href="https://simplystatic.com/tutorials/" target="_blank">
                                        <Dashicon icon="edit"/> {__('Tutorials', 'simply-static')}
                                    </Button>
                                    {!isStudio() &&
                                        <>
                                            <Button className={"ss-get-pro"} isPrimary
                                                    href="https://simplystatic.com/simply-static-studio/"
                                                    target="_blank">
                                                Try Simply Static Studio
                                            </Button>
                                        </>
                                    }
                                </CardBody>
                            </Card>
                        }
                    </FlexItem>
                    <FlexItem isBlock={true} className={!showMobileNav ? 'toggle-nav' : ''}>
                        <div class={"plugin-settings"}>
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