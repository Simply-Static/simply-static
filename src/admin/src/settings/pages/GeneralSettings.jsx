import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function GeneralSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [replaceType, setReplaceType] = useState('absolute');
    const [scheme, setScheme] = useState('https://');
    const [url, setUrl] = useState('');
    const [path, setPath] = useState('/');
    const [ text, setText ] = useState( '' );

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
                                    }}
                                />
                            </FlexItem>
                            <FlexItem style={{minWidth: "85%"}}>
                                <TextControl
                                    label={__('Url', 'simply-static')}
                                    type={"text"}
                                    placeholder={"example.com"}
                                    value={url}
                                    onChange={(value) => {
                                        setUrl(value);
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
                            onChange={(value) => {
                                setPath(value);
                            }}
                        />
                        <p>
                            {__('Convert all URLs for your WordPress site to relative URLs that will work at any domain.', 'simply-static')}<br></br>
                            {__('Optionally specify a path above if you intend to place the files in a subdirectory.', 'simply-static')}
                        </p>
                        <p>
                            <b>{__('Example', 'simply-static')}: </b>
                            {__('enter /path/ above if you wanted to serve your files at www.example.com/path/', 'simply-static')}
                        </p>
                    </>
                }
                {replaceType === 'offline' &&
                    <p>{__('Convert all URLs for your WordPress site so that you can browse the site locally on your own computer without hosting it on a web server.', 'simply-static')}</p>
                }
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
                    placeholder={"https://simply-static.local/hidden-page/"}
                    help={__('Simply Static will create a static copy of any page it can find a link to, starting at https://simply-static.local/. If you want to create static copies of pages or files that aren\'t linked to, add the URLs here (one per line).', 'simply-static')}
                    value={ text }
                    onChange={ ( value ) => {

                    } }
                />
                <TextareaControl
                    label={__('Additional Files and Directories', 'simply-static')}
                    placeholder={"/Users/patrickposner/Local Sites/simplystatic/app/public/additional-directory/\n/Users/patrickposner/Local Sites/simplystatic/app/public/additional-file.html"}
                    help={__('Sometimes you may want to include additional files (such as files referenced via AJAX) or directories. Add the paths to those files or directories here (one per line).', 'simply-static')}
                    value={ text }
                    onChange={ ( value ) => {

                    } }
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
                    label={__('Urls and Patterns to exclude', 'simply-static')}
                    placeholder={"wp-json.php\nwp-login.php\n.jpg"}
                    help={__('Specify URLs you want to exclude from the processing (one per line). You can also specify regex pattern to match.', 'simply-static')}
                    value={ text }
                    onChange={ ( value ) => {

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

export default GeneralSettings;