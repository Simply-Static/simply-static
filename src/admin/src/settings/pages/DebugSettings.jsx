import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, ToggleControl, TextareaControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;

function DebugSettings() {
    const {
        settings,
        updateSetting,
        saveSettings,
        settingsSaved,
        setSettingsSaved,
        isPro,
        isStudio
    } = useContext(SettingsContext);
    const [activateDebugLog, setActivateDebugLog] = useState(false);
    const [useServerCron, setUserServerCron] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.debugging_mode) {
            setActivateDebugLog(settings.debugging_mode);
        }

        if (settings.server_cron) {
            setUserServerCron(settings.server_cron);
        }
    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Basic Auth', 'simply-static')}<HelperVideo
                    title={__('How to set up basic auth', 'simply-static')}
                    videoUrl={'https://youtu.be/6udSR3_zSOU'}/></b>
            </CardHeader>
            <CardBody>
                <p>
                    {__('If you\'ve secured WordPress with HTTP Basic Auth you need to specify the username and password to use below.', 'simply-static')}
                </p>
                <TextControl
                    label={__('Basic Auth Username', 'simply-static')}
                    autoComplete={"off"}
                    type={"text"}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    value={settings.http_basic_auth_username}
                    onChange={(username) => {
                        updateSetting('http_basic_auth_username', username);
                    }}
                />
                <TextControl
                    label={__('Basic Auth Password', 'simply-static')}
                    type={"password"}
                    autoComplete={"off"}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    value={settings.http_basic_auth_password}
                    onChange={(username) => {
                        updateSetting('http_basic_auth_password', username);
                    }}
                />
                <ToggleControl
                    label={__('Enable Basic Auth', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        <>
                            {'free' === options.plan ?
                                <>
                                    {__('Automatically setting up Basic Auth requires Simply Static Pro.', 'simply-static')}
                                </>
                                :
                                <>
                                    {__('Once enabled we will put your entire website behind password protection.', 'simply-static')}
                                </>
                            }
                        </>
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={!!settings.http_basic_auth_on}
                    onChange={(value) => {
                        updateSetting('http_basic_auth_on', value);
                    }}
                />
                {
                    settings.http_basic_auth_on &&
                    (!settings.http_basic_auth_username || !settings.http_basic_auth_password) &&
                    <Notice status={"warning"} isDismissible={false}>
                        {__('Requires Username & Password to work', 'simply-static')}
                    </Notice>
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {!isStudio() &&
            <Card>
                <CardHeader>
                    <b>{__('Temporary Files', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={__('Temporary Files Directory', 'simply-static')}
                        type={"text"}
                        placeholder={options.temp_files_dir}
                        help={__('Optionally specify the directory to save your temporary files. This directory must exist and be writeable.', 'simply-static')}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        value={settings.temp_files_dir}
                        onChange={(temp_dir) => {
                            updateSetting('temp_files_dir', temp_dir);
                        }}
                    />
                </CardBody>
            </Card>
        }
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Whitelist Plugins', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <TextareaControl
                    label={__('Whitelist plugins in diagnostics', 'simply-static')}
                    placeholder={"autoptimize\nwp-search-with-algolia\nwp-rocket"}
                    help={__('If you want to exclude certain plugins from the diagnostics check add the plugin slugs here (one per line).', 'simply-static')}
                    __nextHasNoMarginBottom
                    value={settings.whitelist_plugins}
                    onChange={(value) => {
                        updateSetting('whitelist_plugins', value);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {!isStudio() &&
            <Card>
                <CardHeader>
                    <b>{__('Proxy Setup', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={__('Origin URL', 'simply-static')}
                        type={"url"}
                        help={__('If the URL of your WordPress installation differs from the public-facing URL (Proxy Setup), add the public URL here.', 'simply-static')}
                        placeholder={options.home}
                        autoComplete={"off"}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        value={settings.origin_url}
                        onChange={(origin_url) => {
                            updateSetting('origin_url', origin_url);
                        }}
                    />
                </CardBody>
            </Card>
        }
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Debug Log', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Activate Debug Log', 'simply-static')}
                    help={__('Enable it to download the debug log from Simply Static -> Generate.', 'simply-static')}
                    __nextHasNoMarginBottom
                    checked={activateDebugLog}
                    onChange={(value) => {
                        setActivateDebugLog(value);
                        updateSetting('debugging_mode', value);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {!isStudio() &&
            <Card>
                <CardHeader>
                    <b>{__('Cron', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <ToggleControl
                        label={__('Use server-side cron job', 'simply-static')}
                        help={__('Enable this if you use a server-side cron job instead of the default WP-Cron.', 'simply-static')}
                        __nextHasNoMarginBottom
                        checked={useServerCron}
                        onChange={(value) => {
                            setUserServerCron(value);
                            updateSetting('server_cron', value);
                        }}
                    />
                </CardBody>
            </Card>
        }
        <Spacer margin={5}/>
        {settingsSaved &&
            <>
                <Animate type="slide-in" options={{origin: 'top'}}>
                    {() => (
                        <Notice status="success" isDismissible={false}>
                            <p>
                                {__('Settings saved successfully.', 'simply-static')}
                            </p>
                        </Notice>
                    )}
                </Animate>
                <Spacer margin={5}/>
            </>
        }
        <div className={"save-settings"}>
            <Button onClick={setSavingSettings}
                    variant="primary">{__('Save Settings', 'simply-static')}</Button>
        </div>
    </div>)
}

export default DebugSettings;