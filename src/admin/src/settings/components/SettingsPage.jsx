import GeneralSettings from "../pages/GeneralSettings";
import Diagnostics from "../pages/Diagnostics";
import Utilities from "../pages/Utilities";
import {useState, useEffect, useContext} from "@wordpress/element";
import {Flex, FlexItem} from '@wordpress/components';

import {
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
    CardDivider
} from '@wordpress/components';
import {SettingsContext} from "../context/SettingsContext";
import DeploymentSettings from "../pages/DeploymentSettings";
import FormSettings from "../pages/FormSettings";
import SearchSettings from "../pages/SearchSettings";
import MiscSettings from "../pages/MiscSettings";
import Generate from "../pages/Generate";
import Optimize from "../pages/Optimize";

const {__} = wp.i18n;

function SettingsPage() {

    const [activeItem, setActiveItem] = useState({activeItem: "/"});
    const [initialSet, setInitialSet] = useState(false);
    const {saveSettings, setSettingsSaved} = useContext(SettingsContext);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (!initialSet) {
            setActiveItem('/');
            setInitialSet(true);
        }
    });

    return (
        <div className={"plugin-settings-container"}>
            <NavigatorProvider initialPath="/">
                <Flex>
                    <FlexItem>
                        <Card className={"plugin-nav"}>
                            <div className={"plugin-logo"}>
                                <img alt="Logo"
                                     src={options.logo}/>
                            </div>
                            {/* eslint-disable-next-line no-undef */}
                            <p>Version: <b>{options.version}</b></p>
                            <div className={"generate-container"}>
                                <NavigatorButton onClick={() => setActiveItem('/')}
                                                 className={activeItem === '/' ? 'is-active-item generate' : 'generate'} path="/">
                                    <Dashicon icon="update" /> {__('Generate Static Files', 'content-protector')}
                                </NavigatorButton>

                            </div>
                            <CardBody>
                                <h4 className={"settings-headline"}> {__('Tools', 'content-protector')}</h4>
                                <NavigatorButton onClick={() => setActiveItem('/')}
                                                 className={activeItem === '/' ? 'is-active-item generate' : 'generate'} path="/">
                                    <Dashicon icon="update" /> {__('Activity Log', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/diagnostics')}
                                                 className={activeItem === '/diagnostics' ? 'is-active-item' : ''}
                                                 path="/diagnostics">
                                    <Dashicon icon="editor-help" /> {__('Diagnostics', 'content-protector')}
                                </NavigatorButton>
                            </CardBody>
                            <CardBody>
                                <h4 className={"settings-headline"}> {__('Settings', 'content-protector')}</h4>
                                <NavigatorButton onClick={() => setActiveItem('/general')}
                                                 className={activeItem === '/general' ? 'is-active-item' : ''} path="/general">
                                    <Dashicon icon="admin-generic" /> {__('General', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/deployment')}
                                                 className={activeItem === '/deployment' ? 'is-active-item' : ''} path="/deployment">
                                    <Dashicon icon="migrate" /> {__('Deployment', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/forms')}
                                                 className={activeItem === '/forms' ? 'is-active-item' : ''} path="/forms">
                                    <Dashicon icon="align-center" /> {__('Forms', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/search')}
                                                 className={activeItem === '/search' ? 'is-active-item' : ''} path="/search">
                                    <Dashicon icon="search" /> {__('Search', 'content-protector')}
                                </NavigatorButton>
                            </CardBody>
                            <CardBody>
                                <h4 className={"settings-headline"}> {__('Advanced', 'content-protector')}</h4>
                                <NavigatorButton onClick={() => setActiveItem('/optimize')}
                                                 className={activeItem === '/optimize' ? 'is-active-item' : ''} path="/optimize">
                                    <Dashicon icon="dashboard" /> {__('Optimize', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/utilities')}
                                                 className={activeItem === '/utilities' ? 'is-active-item' : ''}
                                                 path="/utilities">
                                    <Dashicon icon="admin-tools" /> {__('Utilities', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/misc')}
                                                 className={activeItem === '/misc' ? 'is-active-item' : ''}
                                                 path="/misc">
                                    <Dashicon icon="block-default" /> {__('Misc', 'content-protector')}
                                </NavigatorButton>
                            </CardBody>
                            <CardBody>
                                <h4 className={"settings-headline"}> {__('Simply Static', 'content-protector')}</h4>
                                <Button href="https://simplystatic.com/changelogs/" target="_blank">
                                    <Dashicon icon="editor-ul" /> {__('Changelog', 'content-protector')}
                                </Button>
                                <Button href="https://simplystatic.com/docs/" target="_blank">
                                    <Dashicon icon="admin-links" /> {__('Documentation', 'content-protector')}
                                </Button>
                                {!options.is_pro &&
                                    <Button href="https://simplystatic.com/simply-static-pro/" target="_blank">
                                        <Dashicon icon="admin-site-alt3" /> {__('Simply Static Pro', 'content-protector')}
                                    </Button>
                                }
                            </CardBody>
                        </Card>
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
                    {activeItem === '/forms' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/forms">
                                <div className={"plugin-settings"}>
                                    <FormSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/search' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/search">
                                <div className={"plugin-settings"}>
                                    <SearchSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/optimize' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/optimize">
                                <div className={"plugin-settings"}>
                                    <Optimize/>
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
                    {activeItem === '/diagnostics' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/diagnostics">
                                <div className={"plugin-settings"}>
                                    <Diagnostics/>
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
                </Flex>
            </NavigatorProvider>
        </div>
    )
}

export default SettingsPage;