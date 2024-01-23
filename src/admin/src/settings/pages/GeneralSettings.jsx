import {
    Button,
    ClipboardButton,
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

function GeneralSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [replaceType, setReplaceType] = useState('relative');
    const [scheme, setScheme] = useState('https://');
    const [host, setHost] = useState('');
    const [path, setPath] = useState('/');
    const [forceURLReplacement, setForceURLReplacement] = useState(false);
    const [hasCopied, setHasCopied] = useState(false);
    const [generate404, setGenerate404] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.destination_url_type) {
            setReplaceType(settings.destination_url_type);
        }

        if (settings.destination_scheme) {
            setScheme(settings.destination_scheme);
        }

        if (settings.destination_host) {
            setHost(settings.destination_host);
        }

        if (settings.relative_path) {
            setPath(settings.relative_path);
        }

        if (settings.force_replace_url) {
            setForceURLReplacement(settings.force_replace_url);
        }

        if (settings.generate_404) {
            setGenerate404(settings.generate_404);
        }

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Replacing URLs', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('When exporting your static site, any links to your WordPress site will be replaced by one of the following: absolute URLs, relative URLs, or URLs contructed for offline use.', 'simply-static')}</p>
                <SelectControl
                    label={__('Replacing URLs', 'simply-static')}
                    value={replaceType}
                    options={[
                        {label: __('Absolute URLs', 'simply-static'), value: 'absolute'},
                        {label: __('Relative Path', 'simply-static'), value: 'relative'},
                        {label: __('Offline Usage', 'simply-static'), value: 'offline'},
                    ]}
                    onChange={(type) => {
                        setReplaceType(type);
                        updateSetting('destination_url_type', type);
                    }}
                />
                {replaceType === 'absolute' &&
                    <>
                        <Flex>
                            <FlexItem style={{minWidth: "15%"}}>
                                <SelectControl
                                    label={__('Scheme', 'simply-static')}
                                    value={scheme}
                                    options={[
                                        {label: 'https://', value: 'https://'},
                                        {label: 'http://', value: 'http://'},
                                        {label: '//', value: '//'},
                                    ]}
                                    onChange={(scheme) => {
                                        setScheme(scheme);
                                        updateSetting('destination_scheme', scheme);
                                    }}
                                />
                            </FlexItem>
                            <FlexItem style={{minWidth: "85%"}}>
                                <TextControl
                                    label={__('Host', 'simply-static')}
                                    type={"text"}
                                    placeholder={"example.com"}
                                    value={host}
                                    onChange={(host) => {
                                        setHost(host);
                                        updateSetting('destination_host', host);
                                    }}
                                />
                            </FlexItem>
                        </Flex>
                        <p>{__('Convert all URLs for your WordPress site to absolute URLs at the domain specified above.', 'simply-static')}</p>
                    </>
                }
                {replaceType === 'relative' &&
                    <>
                        <TextControl
                            label={__('Path', 'simply-static')}
                            type={"text"}
                            placeholder={"/"}
                            value={path}
                            onChange={(path) => {
                                setPath(path);
                                updateSetting('relative_path', path);
                            }}
                        />
                        <p>
                            {__('Convert all URLs for your WordPress site to relative URLs that will work at any domain.', 'simply-static')}<br></br>
                            {__('Optionally specify a path above if you intend to place the files in a subdirectory.', 'simply-static')}
                        </p>
                        <p>
                            <Notice status={"warning"} isDismissible={false}>
                                <b>{__('Example', 'simply-static')}: </b>
                                {__('enter /path above if you wanted to serve your files at www.example.com/path/', 'simply-static')}
                            </Notice>
                        </p>
                    </>
                }
                {replaceType === 'offline' &&
                    <p>{__('Convert all URLs for your WordPress site so that you can browse the site locally on your own computer without hosting it on a web server.', 'simply-static')}</p>
                }
                <ToggleControl
                    label={__('Force URL replacements', 'simply-static')}
                    help={
                        forceURLReplacement
                            ? __('Replace all occurrences of the WordPress URL with the static URL (includes inline CSS and JS).', 'simply-static')
                            : __('Replace only occurrences of the WordPress URL that match our tag list.', 'simply-static')
                    }
                    checked={forceURLReplacement}
                    onChange={(value) => {
                        setForceURLReplacement(value);
                        updateSetting('force_replace_url', value);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Include', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <TextareaControl
                    label={__('Additional URLs', 'simply-static')}
                    placeholder={options.home + "/hidden-page/"}
                    help={__('If you want to create static copies of pages or files that aren\'t linked to, add the URLs here (one per line).', 'simply-static')}
                    value={settings.additional_urls}
                    onChange={(value) => {
                        updateSetting('additional_urls', value);
                    }}
                />
                <TextareaControl
                    label={__('Additional Files and Directories', 'simply-static')}
                    placeholder={options.home_path + "additional-directory/\n" + options.home_path + "additional-file.html"}
                    help={__('Sometimes you may want to include additional files (such as files referenced via AJAX) or directories. Add the paths to those files or directories here (one per line).', 'simply-static')}
                    value={settings.additional_files}
                    onChange={(value) => {
                        updateSetting('additional_files', value);
                    }}
                />
                <ClipboardButton
                    variant="secondary"
                    text={options.home_path}
                    onCopy={() => setHasCopied(true)}
                    onFinishCopy={() => setHasCopied(false)}
                >
                    {hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')}
                </ClipboardButton>
                <Spacer margin={5}/>
                <ToggleControl
                    label={__('Generate 404 Page?', 'simply-static')}
                    help={
                        generate404
                            ? __('Generate a 404 page.', 'simply-static')
                            : __('Don\'t generate a 404 page.', 'simply-static')
                    }
                    checked={generate404}
                    onChange={(value) => {
                        setGenerate404(value);
                        updateSetting('generate_404', value);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Exclude', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <TextareaControl
                    label={__('Urls to exclude', 'simply-static')}
                    placeholder={"wp-json.php\nwp-login.php\n.jpg"}
                    help={__('Specify URLs (or parts of URLs) you want to exclude from the processing (one per line).', 'simply-static')}
                    value={settings.urls_to_exclude}
                    onChange={(value) => {
                        updateSetting('urls_to_exclude', value);
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

export default GeneralSettings;