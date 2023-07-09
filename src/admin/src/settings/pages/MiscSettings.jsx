import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl, ToggleControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function MiscSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [ showSubsiteSettings, setShowSubsiteSettings ] = useState( false );
    const [ forceURLReplacement, setForceURLReplacement ] = useState( false );
    const [ clearDirectory, setClearDirectory ] = useState( false );

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {

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
                    placeholder={'/Users/patrickposner/Local Sites/simplystatic/app/public/wp-content/plugins/simply-static/static-files/'}
                    help={__('Specify the directory to save your temporary files. This directory must exist and be writeable.', 'simply-static')}
                    value={'/Users/patrickposner/Local Sites/simplystatic/app/public/wp-content/plugins/simply-static/static-files/'}
                    onChange={(value) => {

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
                    type={"text"}
                    value={''}
                    onChange={(value) => {

                    }}
                />

                <TextControl
                    label={__('Basic Auth Password', 'simply-static')}
                    type={"password"}
                    value={''}
                    onChange={(value) => {

                    }}
                />

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Multisite', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Here you can configure settings related to WordPress Multisite.', 'simply-static')}</p>
                <ToggleControl
                    label={__('Show subsite settings', 'simply-static')}
                    help={
                        showSubsiteSettings
                            ? 'Show admin settings in subsites.'
                            : 'Hide admin settings in subsites.'
                    }
                    checked={ showSubsiteSettings }
                    onChange={ () => {
                        setShowSubsiteSettings( ( state ) => ! state );
                    } }
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Additional Settings', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Here you can configure some additional settings like clearing the local directory before running an export or activating force replacement for all URLs.', 'simply-static')}</p>
                <ToggleControl
                    label={__('Force URL replacements', 'simply-static')}
                    help={
                        forceURLReplacement
                            ? 'Replace all occurrences of the WordPress URL with the static URL.'
                            : 'Replace only occurrences of the WordPress URL that match the tags'
                    }
                    checked={ forceURLReplacement }
                    onChange={ () => {
                        setForceURLReplacement( ( state ) => ! state );
                    } }
                />

                <ToggleControl
                    label={__('Clear Directory', 'simply-static')}
                    help={
                        clearDirectory
                            ? 'Clear local directory before running an export.'
                            : 'Don\'t clear local directory before running an export.'
                    }
                    checked={ clearDirectory }
                    onChange={ () => {
                        setClearDirectory( ( state ) => ! state );
                    } }
                />

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {settingsSaved &&
            <Animate type="slide-in" options={{origin: 'top'}}>
                {() => (
                    <Notice status="success" isDismissible={false}>
                        <p>
                            {__('Settings saved successfully.', 'simply-static')}
                        </p>
                    </Notice>
                )}
            </Animate>
        }
        <div className={"save-settings"}>
            <Button onClick={setSavingSettings}
                    variant="primary">{__('Save Settings', 'simply-static')}</Button>
        </div>
    </div>)
}

export default MiscSettings;