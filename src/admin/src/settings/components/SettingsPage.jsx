import GeneralSettings from "../pages/GeneralSettings";
import SystemStatus from "../pages/SystemStatus";
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
                            <div className={"save-settings"}>
                                <Button onClick={setSavingSettings}
                                        variant="primary">{__('Save Settings', 'content-protector')}</Button>
                            </div>
                            <CardBody>
                                <NavigatorButton onClick={() => setActiveItem('/')}
                                                 className={activeItem === '/' ? 'is-active-item' : ''} path="/">
                                    {__('General', 'content-protector')}
                                </NavigatorButton>
                            </CardBody>
                            <CardDivider/>
                            <CardBody>
                                <NavigatorButton onClick={() => setActiveItem('/system-status')}
                                                 className={activeItem === '/system-status' ? 'is-active-item' : ''}
                                                 path="/system-status">
                                    {__('System Status', 'content-protector')}
                                </NavigatorButton>
                                <NavigatorButton onClick={() => setActiveItem('/utilities')}
                                                 className={activeItem === '/utilities' ? 'is-active-item' : ''}
                                                 path="/utilities">
                                    {__('Utilities', 'content-protector')}
                                </NavigatorButton>
                            </CardBody>
                            <CardDivider/>
                            <CardBody>
                                <Button href="https://simplystatic.com/docs/" target="_blank">
                                    {__('Documentation', 'content-protector')} <small><Dashicon
                                    icon="admin-links"/></small>
                                </Button>
                                <Button href="https://simplystatic.com/changelogs/" target="_blank">
                                    {__('Changelog', 'content-protector')} <small><Dashicon
                                    icon="admin-links"/></small>
                                </Button>
                                {!options.is_pro &&
                                    <Button href="https://simplystatic.com/simply-static-pro/" target="_blank"
                                            style={{color: "#6804cc"}}>
                                        {__('Simply Static Pro', 'content-protector')} <small><Dashicon
                                        icon="admin-links"/></small>
                                    </Button>
                                }
                            </CardBody>
                        </Card>
                    </FlexItem>
                    {activeItem === '/' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/">
                                <div className={"plugin-settings"}>
                                    <GeneralSettings/>
                                </div>
                            </NavigatorScreen>
                        </FlexItem>
                    }
                    {activeItem === '/system-status' &&
                        <FlexItem isBlock={true}>
                            <NavigatorScreen path="/system-status">
                                <div className={"plugin-settings"}>
                                    <SystemStatus/>
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