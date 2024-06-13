import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, ToggleControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function MiscSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [debuggingMode, setDebuggingMode] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.debugging_mode) {
            setDebuggingMode(settings.debugging_mode);
        }
    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Temporary Files', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Your static files are temporarily saved to a directory before being copied to their destination or creating a ZIP.', 'simply-static')}</p>
                <TextControl
                    label={__('Temporary Files Directory', 'simply-static')}
                    type={"text"}
                    placeholder={options.temp_files_dir}
                    help={__('Specify the directory to save your temporary files. This directory must exist and be writeable.', 'simply-static')}
                    value={settings.temp_files_dir}
                    onChange={(temp_dir) => {
                        updateSetting('temp_files_dir', temp_dir);
                    }}
                />

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Basic Auth', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('If you\'ve secured WordPress with HTTP Basic Auth you can specify the username and password to use below.', 'simply-static')}</p>
                <TextControl
                    label={__('Basic Auth Username', 'simply-static')}
                    autoComplete={"off"}
                    type={"text"}
                    value={settings.http_basic_auth_username}
                    onChange={(username) => {
                        updateSetting('http_basic_auth_username', username);
                    }}
                />

                <TextControl
                    label={__('Basic Auth Password', 'simply-static')}
                    type={"password"}
                    autoComplete={"off"}
                    value={settings.http_basic_auth_password}
                    onChange={(username) => {
                        updateSetting('http_basic_auth_password', username);
                    }}
                />

                {'pro' === options.plan &&
                    <>
                        <ToggleControl
                            label={__('Use Simply Static Basic Auth', 'simply-static')}
                            help={
                                settings.http_basic_auth_on
                                    ? __('Enable.', 'simply-static')
                                    : __('Disable.', 'simply-static')
                            }
                            checked={settings.http_basic_auth_on}
                            onChange={(value) => {
                                updateSetting('http_basic_auth_on', value);
                            }}
                        />
                        {
                            settings.http_basic_auth_on &&
                            ( !settings.http_basic_auth_username || !settings.http_basic_auth_password ) &&
                            <Notice status={"warning"} isDismissible={false}>
                                {__('Requires Username & Password to work', 'simply-static')}
                            </Notice>
                        }
                    </>
                }

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Debugging', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Debugging Mode', 'simply-static')}
                    help={
                        debuggingMode
                            ? __('Enable debugging mode.', 'simply-static')
                            : __('Disable debugging mode.', 'simply-static')
                    }
                    checked={debuggingMode}
                    onChange={(value) => {
                        setDebuggingMode(value);
                        updateSetting('debugging_mode', value);
                    }}
                />
                <TextControl
                    label={__('Origin URL', 'simply-static')}
                    type={"url"}
                    help={__('If the URL of your WordPress installation differs from the public-facing URL (Proxy Setup), add the public URL here.', 'simply-static')}
                    placeholder={options.home}
                    autoComplete={"off"}
                    value={settings.origin_url}
                    onChange={(origin_url) => {
                        updateSetting('origin_url', origin_url);
                    }}
                />
            </CardBody>
        </Card>
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

export default MiscSettings;