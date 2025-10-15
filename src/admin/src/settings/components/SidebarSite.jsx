import {
    __experimentalNavigatorButton as NavigatorButton,
    __experimentalSpacer as Spacer,
    Animate,
    Button,
    Card,
    CardBody,
    Dashicon, Notice,
    SelectControl
} from "@wordpress/components";
import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import VersionInfo from "./VersionInfo";
import GenerateButtons from "./GenerateButtons";
import EnvironmentSidebar from "./EnvironmentSidebar";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;
function SidebarSite( props = null ) {
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
        isPro,
        canRunIntegration,
        showMobileNav,
        setShowMobileNav,
        isDelayed
    } = useContext(SettingsContext);
    const { activeItem, setActiveItem} = props;
    const [disabledButton, setDisabledButton] = useState(false);
    const [selectedCopySite, setSelectedCopySite] = useState('current');
    const [selectablesSites, setSelectableSites] = useState([]);
    const [isUpdatingFromNetwork, setIsUpdatingFromNetwork] = useState(false);
    const [selectedExportType, setSelectedExportType] = useState('export');
    const [canRunExport, setCanRunExport] = useState(true);


    if ( options.is_multisite ) {

        const checkIfCanRun = () => {
            apiFetch({
                path: '/simplystatic/v1/check-can-run',
                method: 'GET'
            }).then(resp => {
                var json = JSON.parse(resp);

                if (json.can_run) {
                    setCanRunExport(true);
                } else {
                    setCanRunExport(false);
                }
            });
        }

        useInterval(() => {
            checkIfCanRun();
        }, isRunning ? null : 100000);

        useEffect(() => {
            checkIfCanRun();
        }, [])
    }

    useEffect(() => {
        setDisabledButton(isRunning || isPaused);

        if (options.selectable_sites && !options.is_network && options.is_multisite) {
            let sites = options.selectable_sites.map(function (site) {
                return {label: `${site.name}`, value: site.blog_id}
            });

            sites.unshift({label: __('Use current settings', 'simply-static'), value: 'current'});
            setSelectableSites(sites);
        }

    }, [options, isRunning, isPaused]);

    // Set the default export type when the component mounts or when settings change
    useEffect(() => {
        // Always use 'export' as the default option
        setSelectedExportType('export');
    }, [settings]);

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
            var json = JSON.parse(resp);
            if (json.status === 500) {
                alert(json.message);
                setDisabledButton(false);
                return;
            }
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

    const runUpdateFromNetwork = (blogId) => {
        // Update settings from selected blog_id.
        updateFromNetwork(blogId);

        setIsUpdatingFromNetwork(true);

        setTimeout(function () {
            setIsUpdatingFromNetwork(false);
            window.location.reload();
        }, 2000);
    }

    let buildOptions = '';
    if (Object.keys(options.builds).length) {
        const builds = Object.keys(options.builds).map((id) => <option key={id} value={id}>{options.builds[id]}</option>);

        // Sort builds alphabetically
        builds.sort((a, b) => {
            return a.props.children.localeCompare(b.props.children);
        });

        buildOptions = <optgroup label="Builds">
            {builds}
        </optgroup>
    }

    return <Card className={"plugin-nav"}>
        <div className={"plugin-logo"}>
            <img alt="Logo"
                 src={options.logo}/>
        </div>
        <VersionInfo/>

        <div className={`generate-container ${disabledButton ? 'generating' : ''}`}>
            <SelectControl
                className={'generate-type'}
                value={selectedExportType}
                disabled={disabledButton}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
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
            {canRunExport &&
                <GenerateButtons
                    canGenerate={!disabledButton}
                    startExport={startExport}
                    cancelExport={cancelExport}
                    pauseExport={pauseExport}
                    resumeExport={resumeExport}
                    isRunning={isRunning}
                    isPaused={isPaused}
                    isResumed={isResumed}
                    isDelayed={isDelayed}
                />
            }

            {!canRunExport && options.is_multisite && <>
                <Button

                    disabled={true}
                    className={'generate'}
                >
                    <Dashicon icon="update"/>
                    {__('Generate', 'simply-static')}

                </Button>
                <Button
                    target={'_blank'}
                    variant={'link'}
                    href={"https://simplystatic.com/pricing/"}
                >
                    { __('An export from another site is running. Upgrade to queue them.', 'simply-static') }
                </Button>
            </>}
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
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
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
        </CardBody>
    </Card>
}

export default SidebarSite;