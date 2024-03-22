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
    Spinner,
    Notice,
    Animate, __experimentalSpacer as Spacer, SelectControl, ToggleControl
} from '@wordpress/components';
import DeploymentSettings from "../pages/DeploymentSettings";
import FormSettings from "../pages/FormSettings";
import SearchSettings from "../pages/SearchSettings";
import MiscSettings from "../pages/MiscSettings";
import Generate from "../pages/Generate";
import Optimize from "../pages/Optimize";
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";

const {__} = wp.i18n;

function SettingsPage() {
    const {
        isRunning,
        setIsRunning,
        blogId,
        migrateSettings,
        saveSettings,
        updateFromNetwork
    } = useContext(SettingsContext);
    const [activeItem, setActiveItem] = useState({activeItem: "/"});
    const [initialPage, setInitialPage] = useState(options.initial);
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
        if (!initialSet) {
            setInitialSet(true);
            setActiveItem(options.initial);
            setInitialPage(options.initial);
        }

        if (options.selectable_sites && !options.is_network && options.is_multisite) {
            let sites = options.selectable_sites.map(function (site) {
                return {label: `${site.name}`, value: site.blog_id}
            });

            sites.unshift({label: __('Use current settings', 'simply-static'), value: 'current'});
            setSelectableSites(sites);
        }
    }, [options]);

    const startExport = () => {
        setDisabledButton(true);

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
            setIsRunning(false);
        });
    }

    const runMigrateSettings = () => {
        migrateSettings();
        saveSettings();
        location.reload();
    }

    useEffect(function () {
        setDisabledButton(isRunning);
    }, [isRunning]);

    let buildOptions = '';
    if ( Object.keys(options.builds).length ) {
        const builds = Object.keys(options.builds).map((id) => <option value={id}>{options.builds[id]}</option>);
        buildOptions = <optgroup label="Builds">
            {builds}
        </optgroup>
    }

    return (
        <div className={"plugin-settings-container"}>
            {'yes' === options.need_upgrade ?
                <Animate type="slide-in" options={{origin: 'top'}}>
                    {() => (
                        <Notice status="warning" isDismissible={false} className={"migrate-notice"}>
                            <p>
                                {__('You have to migrate your settings to version 3.x of Simply Static to ensure everything works smoothly with the new interface.', 'simply-static')}
                            </p>
                            <Button onClick={runMigrateSettings}
                                    variant="primary">{__('Migrate settings', 'simply-static')}</Button>
                        </Notice>
                    )}
                </Animate>
                :
                ''
            }
            <NavigatorProvider initialPath={initialPage}>
                <Flex>
                    <FlexItem>
                        {options.is_network ?
                            <Card className={"plugin-nav"}>
                                <div className={"plugin-logo"}>
                                    <img alt="Logo"
                                         src={options.logo}/>
                                </div>
                                {'pro' === options.plan ?
                                    <p>
                                        Free: <b>{options.version}</b><br></br>
                                        Pro: <b>{options.version_pro}</b>
                                    </p>
                                    :
                                    <p>Version: <b>{options.version}</b></p>
                                }
                                <div className={"generate-container"}>
                                    {'pro' === options.plan && <p>
                                        <SelectControl
                                            value={selectedExportType}
                                            onChange={(value) => {
                                                setSelectedExportType(value);
                                            }}
                                        >
                                            <option value="export">{ __( 'Export', 'simply-static' ) }</option>
                                            <option value="update">{ __( 'Update', 'simply-static' ) }</option>
                                            {buildOptions}
                                        </SelectControl>
                                    </p>}
                                    <Button onClick={() => {
                                        startExport();
                                    }}
                                            disabled={disabledButton}
                                            className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                    >
                                        {!disabledButton && [<Dashicon icon="update"/>,
                                            __('Generate Static Files', 'simply-static')
                                        ]}
                                        {disabledButton && [<Dashicon icon="update spin"/>,
                                            __('Generating...', 'simply-static'),
                                        ]}
                                    </Button>
                                    {disabledButton &&
                                        <span onClick={() => {
                                            cancelExport();
                                        }} className={"cancel-button"}>
                                            {__('Cancel Export', 'simply-static')}
                                        </span>
                                    }
                                </div>
                                <Spacer margin={5}/>
                                <Button href="https://simplystatic.com/changelogs/" target="_blank">
                                    <Dashicon icon="editor-ul"/> {__('Changelog', 'simply-static')}
                                </Button>
                                <Button href="https://simplystatic.com/docs/" target="_blank">
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
                                {'pro' === options.plan ?
                                    <p>
                                        Free: <b>{options.version}</b><br></br>
                                        Pro: <b>{options.version_pro}</b>
                                    </p>
                                    :
                                    <p>Version: <b>{options.version}</b></p>
                                }
                                <div className={"generate-container"}>
                                    {'pro' === options.plan && <SelectControl
                                            className={'generate-type'}
                                            value={selectedExportType}

                                            onChange={(value) => {
                                                setSelectedExportType(value);
                                            }}
                                        >

                                            <option value="export">{ __( 'Export', 'simply-static' ) }</option>
                                            <option value="update">{ __( 'Update', 'simply-static' ) }</option>
                                            {buildOptions}
                                        </SelectControl>}
                                    <Button onClick={() => {
                                        startExport();
                                    }}
                                            disabled={disabledButton}
                                            className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                    >
                                        {!disabledButton && [<Dashicon icon="update"/>,
                                            __('Generate Static Files', 'simply-static')
                                        ]}
                                        {disabledButton && [<Dashicon icon="update spin"/>,
                                            __('Generating...', 'simply-static'),
                                        ]}
                                    </Button>
                                    {disabledButton &&
                                        <span onClick={() => {
                                            cancelExport();
                                        }} className={"cancel-button"}>
                                            {__('Cancel Export', 'simply-static')}
                                        </span>
                                    }
                                </div>
                                <CardBody>
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
                                    <NavigatorButton onClick={() => setActiveItem('/')}
                                                     className={activeItem === '/' ? 'is-active-item generate' : 'generate'}
                                                     path="/">
                                        <Dashicon icon="update"/> {__('Activity Log', 'simply-static')}
                                    </NavigatorButton>
                                    <NavigatorButton onClick={() => setActiveItem('/diagnostics')}
                                                     className={activeItem === '/diagnostics' ? 'is-active-item' : ''}
                                                     path="/diagnostics">
                                        <Dashicon icon="editor-help"/> {__('Diagnostics', 'simply-static')}
                                    </NavigatorButton>
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}> {__('Settings', 'simply-static')}</h4>
                                    <NavigatorButton onClick={() => setActiveItem('/general')}
                                                     className={activeItem === '/general' ? 'is-active-item' : ''}
                                                     path="/general">
                                        <Dashicon icon="admin-generic"/> {__('General', 'simply-static')}
                                    </NavigatorButton>
                                    {!options.is_network &&
                                        <NavigatorButton onClick={() => setActiveItem('/deployment')}
                                                         className={activeItem === '/deployment' ? 'is-active-item' : ''}
                                                         path="/deployment">
                                            <Dashicon icon="migrate"/> {__('Deploy', 'simply-static')}
                                        </NavigatorButton>
                                    }
                                    {'pro' === options.plan && !options.is_network &&
                                        <>
                                            <NavigatorButton onClick={() => setActiveItem('/forms')}
                                                             className={activeItem === '/forms' ? 'is-active-item' : ''}
                                                             path="/forms">
                                                <Dashicon icon="align-center"/> {__('Forms', 'simply-static')}
                                            </NavigatorButton>
                                            <NavigatorButton onClick={() => setActiveItem('/search')}
                                                             className={activeItem === '/search' ? 'is-active-item' : ''}
                                                             path="/search">
                                                <Dashicon icon="search"/> {__('Search', 'simply-static')}
                                            </NavigatorButton>
                                            <NavigatorButton onClick={() => setActiveItem('/optimize')}
                                                             className={activeItem === '/optimize' ? 'is-active-item' : ''}
                                                             path="/optimize">
                                                <Dashicon icon="dashboard"/> {__('Optimize', 'simply-static')}
                                            </NavigatorButton>
                                        </>
                                    }
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}> {__('Advanced', 'simply-static')}</h4>
                                    <NavigatorButton onClick={() => setActiveItem('/utilities')}
                                                     className={activeItem === '/utilities' ? 'is-active-item' : ''}
                                                     path="/utilities">
                                        <Dashicon icon="admin-tools"/> {__('Utilities', 'simply-static')}
                                    </NavigatorButton>
                                    <NavigatorButton onClick={() => setActiveItem('/misc')}
                                                     className={activeItem === '/misc' ? 'is-active-item' : ''}
                                                     path="/misc">
                                        <Dashicon icon="block-default"/> {__('Misc', 'simply-static')}
                                    </NavigatorButton>
                                </CardBody>
                                <CardBody>
                                    <h4 className={"settings-headline"}>Simply Static</h4>
                                    <Button href="https://simplystatic.com/changelogs/" target="_blank">
                                        <Dashicon icon="editor-ul"/> {__('Changelog', 'simply-static')}
                                    </Button>
                                    <Button href="https://simplystatic.com/docs/" target="_blank">
                                        <Dashicon icon="admin-links"/> {__('Documentation', 'simply-static')}
                                    </Button>
                                    {'free' === options.plan &&
                                        <Button href="https://simplystatic.com" target="_blank">
                                            <Dashicon
                                                icon="admin-site-alt3"/>Simply Static Pro
                                        </Button>
                                    }
                                </CardBody>
                            </Card>
                        }
                    </FlexItem>
                    {activeItem === '/' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/">
                                <div className={"plugin-settings"}>
                                    <Generate/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/diagnostics' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/diagnostics">
                                <div className={"plugin-settings"}>
                                    <Diagnostics/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/general' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/general">
                                <div className={"plugin-settings"}>
                                    <GeneralSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/deployment' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/deployment">
                                <div className={"plugin-settings"}>
                                    <DeploymentSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/forms' && 'pro' === options.plan &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/forms">
                                <div className={"plugin-settings"}>
                                    <FormSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/search' && 'pro' === options.plan &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/search">
                                <div className={"plugin-settings"}>
                                    <SearchSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/optimize' && 'pro' === options.plan &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/optimize">
                                <div className={"plugin-settings"}>
                                    <Optimize/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/utilities' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/utilities">
                                <div className={"plugin-settings"}>
                                    <Utilities/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/misc' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/misc">
                                <div className={"plugin-settings"}>
                                    <MiscSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                </Flex>
            </NavigatorProvider>
        </div>
    )
}

export default SettingsPage;