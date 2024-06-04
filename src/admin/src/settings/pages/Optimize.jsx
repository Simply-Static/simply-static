import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    __experimentalInputControl as InputControl,
    Notice,
    Animate,
    ToggleControl, TextControl, Dashicon,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";

const {__} = wp.i18n;

function Optimize() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [minifyFiles, setMinifyFiles] = useState(false);
    const [minifyHtml, setMinifyHtml] = useState(false);
    const [minifyCss, setMinifyCss] = useState(false);
    const [minifyInlineCss, setMinifyInlineCss] = useState(false);
    const [minifyJavascript, setMinifyJavascript] = useState(false);
    const [minifyInlineJavascript, setMinifyInlineJavascript] = useState(false);
    const [renamePlugin, setRenamePlugin] = useState(false);
    const [wpContentDirectory, setWpContentDirectory] = useState('wp-content');
    const [wpIncludesDirectory, setWpIncludesDirectory] = useState('wp-includes');
    const [wpUploadsDirectory, setWpUploadsDirectory] = useState('wp-content/uploads');
    const [wpPluginsDirectory, setWpPluginsDirectory] = useState('wp-content/plugins');
    const [wpThemesDirectory, setWpThemesDirectory] = useState('wp-content/themes');
    const [themeStyleName, setThemeStyleName] = useState('style');
    const [authorUrl, setAuthorUrl] = useState('author');
    const [hideRESTAPI, setHideRESTAPI] = useState(false);
    const [hideStyleId, setHideStyleId] = useState(false);
    const [hideComments, setHideComments] = useState(false);
    const [hideVersion, setHideVersion] = useState(false);
    const [hidePrefetch, setHidePrefetch] = useState(false);
    const [hideGenerator, setHideGenerator] = useState(false);
    const [hideRSD, setHideRSD] = useState(false);
    const [hideEmojis, setHideEmojis] = useState(false);

    const [disableXMLRPC, setDisableXMLRPC] = useState(false);
    const [disableEmbed, setDisableEmbed] = useState(false);
    const [disableDbDebug, setDisableDbDebug] = useState(false);
    const [disableWLW, setDisableWLW] = useState(false);
    const [disableDirectory, setDisableDirectory] = useState(false);

    const [shortPixelResetting, setShortPixelResetting] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    const restoreBackups = () => {
        setShortPixelResetting(true);
        apiFetch({
            path: '/simplystatic/v1/shortpixel-restore',
            method: 'POST',
        }).then(resp => {
            const json = JSON.parse(resp);

            setShortPixelResetting(false);
            alert(json.message);
        }).catch((error) => {
            setShortPixelResetting(false);
            alert(error.message);
        });
    }

    useEffect(() => {
        if (settings.use_minify) {
            setMinifyFiles(settings.use_minify);
        }

        if (settings.minify_html) {
            setMinifyHtml(settings.minify_html);
        }

        if (settings.minify_css) {
            setMinifyCss(settings.minify_css);
        }

        if (settings.minify_inline_css) {
            setMinifyInlineCss(settings.minify_inline_css);
        }

        if (settings.minify_js) {
            setMinifyJavascript(settings.minify_js);
        }

        if (settings.minify_inline_js) {
            setMinifyInlineJavascript(settings.minify_inline_js);
        }

        if (settings.wp_content_directory) {
            setWpContentDirectory(settings.wp_content_directory);
        }

        if (settings.wp_includes_directory) {
            setWpIncludesDirectory(settings.wp_includes_directory);
        }

        if (settings.wp_uploads_directory) {
            setWpUploadsDirectory(settings.wp_uploads_directory);
        }

        if (settings.wp_plugins_directory) {
            setWpPluginsDirectory(settings.wp_plugins_directory);
        }

        if (settings.rename_plugins) {
            setRenamePlugin(settings.rename_plugins);
        }

        if (settings.wp_themes_directory) {
            setWpThemesDirectory(settings.wp_themes_directory);
        }

        if (settings.theme_style_name) {
            setThemeStyleName(settings.theme_style_name);
        }

        if (settings.author_url) {
            setAuthorUrl(settings.author_url);
        }

        if (settings.hide_rest_api) {
            setHideRESTAPI(settings.hide_rest_api);
        }

        if (settings.hide_style_id) {
            setHideStyleId(settings.hide_style_id);
        }

        if (settings.hide_comments) {
            setHideComments(settings.hide_comments);
        }

        if (settings.hide_version) {
            setHideVersion(settings.hide_version);
        }

        if (settings.hide_generator) {
            setHideGenerator(settings.hide_generator);
        }

        if (settings.hide_prefetch) {
            setHidePrefetch(settings.hide_prefetch);
        }

        if (settings.hide_rsd) {
            setHideRSD(settings.hide_rsd);
        }

        if (settings.hide_emotes) {
            setHideEmojis(settings.hide_emotes)
        }

        if (settings.disable_xmlrpc) {
            setDisableXMLRPC(settings.disable_xmlrpc)
        }

        if (settings.disable_embed) {
            setDisableEmbed(settings.disable_embed)
        }

        if (settings.disable_db_debug) {
            setDisableDbDebug(settings.disable_db_debug)
        }

        if (settings.disable_wlw_manifest) {
            setDisableWLW(settings.disable_wlw_manifest)
        }

        if (settings.disable_directory_browsing) {
            setDisableDirectory(settings.disable_directory_browsing)
        }

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Minify', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Minify Files?', 'simply-static')}
                    help={
                        minifyFiles
                            ? __('Enable minify files on your static website.', 'simply-static')
                            : __('Don\'t enable minify files on your static website.', 'simply-static')
                    }
                    checked={minifyFiles}
                    onChange={(value) => {
                        setMinifyFiles(value);
                        updateSetting('use_minify', value);
                    }}
                />

                {minifyFiles &&
                    <>
                        <ToggleControl
                            label={__('Minify HTML', 'simply-static')}
                            help={
                                minifyHtml
                                    ? __('Minify HTML files.', 'simply-static')
                                    : __('Don\'t minify HTML files.', 'simply-static')
                            }
                            checked={minifyHtml}
                            onChange={(value) => {
                                setMinifyHtml(value);
                                updateSetting('minify_html', value);
                            }}
                        />

                        <ToggleControl
                            label={__('Minify CSS', 'simply-static')}
                            help={
                                minifyCss
                                    ? __('Minify CSS files.', 'simply-static')
                                    : __('Don\'t minify CSS files.', 'simply-static')
                            }
                            checked={minifyCss}
                            onChange={(value) => {
                                setMinifyCss(value);
                                updateSetting('minify_css', value);
                            }}
                        />
                        {minifyCss &&
                            <ToggleControl
                                label={__('Minify Inline CSS', 'simply-static')}
                                help={
                                    minifyInlineCss
                                        ? __('Minify Inline CSS.', 'simply-static')
                                        : __('Don\'t minify Inline CSS.', 'simply-static')
                                }
                                checked={minifyInlineCss}
                                onChange={(value) => {
                                    setMinifyInlineCss(value);
                                    updateSetting('minify_inline_css', value);
                                }}
                            />
                        }
                        <ToggleControl
                            label={__('Minify JavaScript', 'simply-static')}
                            help={
                                minifyJavascript
                                    ? __('Minify JavaScript files.', 'simply-static')
                                    : __('Don\'t minify JavaScript files.', 'simply-static')
                            }
                            checked={minifyJavascript}
                            onChange={(value) => {
                                setMinifyJavascript(value);
                                updateSetting('minify_js', value);
                            }}
                        />

                        {minifyJavascript &&
                            <ToggleControl
                                label={__('Minify Inline JavaScript', 'simply-static')}
                                help={
                                    minifyInlineJavascript
                                        ? __('Minify Inline JavaScript.', 'simply-static')
                                        : __('Don\'t minify Inline JavaScript.', 'simply-static')
                                }
                                checked={minifyInlineJavascript}
                                onChange={(value) => {
                                    setMinifyInlineJavascript(value);
                                    updateSetting('minify_inline_js', value);
                                }}
                            />
                        }
                    </>
                }

                <Spacer padding={2}>
                    <hr/>
                </Spacer>

                <ToggleControl
                    label={__('Optimize Images with Shortpixel?', 'simply-static')}
                    help={
                        settings.shortpixel_enabled
                            ? __('Optimize images.', 'simply-static')
                            : __('Don\'t optimize images.', 'simply-static')
                    }
                    checked={settings.shortpixel_enabled}
                    onChange={(value) => {
                        updateSetting('shortpixel_enabled', value);
                    }}
                />

                {settings.shortpixel_enabled && <>
                    <TextControl
                        label={__('Shortpixel API Key', 'simply-static')}

                        type={"password"}
                        value={settings.shortpixel_api_key}
                        onChange={(apiKey) => {
                            updateSetting('shortpixel_api_key', apiKey);
                        }}
                    />
                    <Spacer padding={1}></Spacer>
                    <ToggleControl
                        label={__('Backup original images?', 'simply-static')}
                        help={
                            settings.shortpixel_backup_enabled
                                ? __('Backup.', 'simply-static')
                                : __('Don\'t back up.', 'simply-static')
                        }
                        checked={settings.shortpixel_backup_enabled}
                        onChange={(value) => {
                            updateSetting('shortpixel_backup_enabled', value);
                        }}
                    />
                    {settings.shortpixel_backup_enabled && <>
                        <Button disabled={shortPixelResetting} onClick={restoreBackups}
                                variant="secondary">
                            {! shortPixelResetting && __('Restore Original Images', 'simply-static')}
                            {shortPixelResetting && [
                                <Dashicon icon="update spin"/>,
                                __('Restoring...', 'simply-static')
                            ]}
                        </Button>
                    </>}
                </>}

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Replace', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <TextControl
                    label={__('wp-content directory', 'simply-static')}
                    help={  __('Replace the "wp-content" directory.', 'simply-static') }
                    type={"text"}
                    placeholder={"wp-content"}
                    value={wpContentDirectory}
                    onChange={(directory) => {
                        updateSetting('wp_content_directory', directory);
                    }}
                />

                <TextControl
                    label={__('wp-includes directory', 'simply-static')}
                    help={  __('Replace the "wp-includes" directory.', 'simply-static') }
                    type={"text"}
                    placeholder={"wp-includes"}
                    value={wpIncludesDirectory}
                    onChange={(directory) => {
                        updateSetting('wp_includes_directory', directory);
                    }}
                />

                <TextControl
                    label={__('uploads directory', 'simply-static')}
                    help={  __('Replace the "wp-content/uploads" directory.', 'simply-static') }
                    type={"text"}
                    placeholder={"uploads"}
                    value={wpUploadsDirectory}
                    onChange={(directory) => {
                        setWpUploadsDirectory(directory);
                        updateSetting('wp_uploads_directory', directory);
                    }}
                />

                <TextControl
                    label={__('plugins directory', 'simply-static')}
                    help={  __('Replace the "wp-content/plugins" directory.', 'simply-static') }
                    type={"text"}
                    placeholder={"plugins"}
                    value={wpPluginsDirectory}
                    onChange={(directory) => {
                        setWpPluginsDirectory(directory);
                        updateSetting('wp_plugins_directory', directory);
                    }}
                />

                <ToggleControl
                    label={__('Replace Plugin Names?', 'simply-static')}
                    help={
                        renamePlugin
                            ? __('Replace plugin names with a random string combinations.', 'simply-static')
                            : __('Keep plugin names.', 'simply-static')
                    }
                    checked={renamePlugin}
                    onChange={(value) => {
                        setRenamePlugin(value);
                        updateSetting('rename_plugins', value);
                    }}
                />

                <TextControl
                    label={__('themes directory', 'simply-static')}
                    help={  __('Replace the "wp-content/themes" directory.', 'simply-static') }
                    type={"text"}
                    placeholder={"themes"}
                    value={wpThemesDirectory}
                    onChange={(directory) => {
                        setWpThemesDirectory(directory);
                        updateSetting('wp_themes_directory', directory);
                    }}
                />

                <InputControl
                    label={__('Theme style name', 'simply-static')}
                    help={  __('Replace the style.css filename.', 'simply-static') }
                    type={"text"}
                    className={"ss-theme-style-name"}
                    suffix={'.css'}
                    placeholder={"style"}
                    value={themeStyleName}
                    onChange={(style) => {
                        setThemeStyleName(style);
                        updateSetting('theme_style_name', style);
                    }}
                />

                <TextControl
                    label={__('Author URL', 'simply-static')}
                    help={  __('Replace the author url.', 'simply-static') }
                    type={"text"}
                    placeholder={"author"}
                    value={authorUrl}
                    onChange={(url) => {
                        setAuthorUrl(url);
                        updateSetting('author_url', url);
                    }}
                />

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Hide', 'simply-static')}</b>
            </CardHeader>
            <CardBody>

                <ToggleControl
                    label={__('Hide REST API URLs', 'simply-static')}
                    checked={hideRESTAPI}
                    onChange={(value) => {
                        setHideRESTAPI(value);
                        updateSetting('hide_rest_api', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide Style/Script IDs', 'simply-static')}
                    checked={hideStyleId}
                    onChange={(value) => {
                        setHideStyleId(value);
                        updateSetting('hide_style_id', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide HTML Comments', 'simply-static')}
                    checked={hideComments}
                    onChange={(value) => {
                        setHideComments(value);
                        updateSetting('hide_comments', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide WordPress Version', 'simply-static')}
                    checked={hideVersion}
                    onChange={(value) => {
                        setHideVersion(value);
                        updateSetting('hide_version', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide WordPress Generator Meta', 'simply-static')}
                    checked={hideGenerator}
                    onChange={(value) => {
                        setHideGenerator(value);
                        updateSetting('hide_generator', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide DNS Prefetch WordPress link', 'simply-static')}
                    checked={hidePrefetch}
                    onChange={(value) => {
                        setHidePrefetch(value);
                        updateSetting('hide_prefetch', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide RSD Header', 'simply-static')}
                    checked={hideRSD}
                    onChange={(value) => {
                        setHideRSD(value);
                        updateSetting('hide_rsd', value);
                    }}
                />

                <ToggleControl
                    label={__('Hide Emojis if you don\'t use them', 'simply-static')}
                    checked={hideEmojis}
                    onChange={(value) => {
                        setHideEmojis(value);
                        updateSetting('hide_emotes', value);
                    }}
                />

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Disable', 'simply-static')}</b>
            </CardHeader>
            <CardBody>

                <ToggleControl
                    label={__('Disable XML-RPC', 'simply-static')}
                    checked={disableXMLRPC}
                    onChange={(value) => {
                        setDisableXMLRPC(value);
                        updateSetting('disable_xmlrpc', value);
                    }}
                />

                <ToggleControl
                    label={__('Disable Embed Scripts', 'simply-static')}
                    checked={disableEmbed}
                    onChange={(value) => {
                        setDisableEmbed(value);
                        updateSetting('disable_embed', value);
                    }}
                />

                <ToggleControl
                    label={__('Disable DB Debug in Frontend', 'simply-static')}
                    checked={disableDbDebug}
                    onChange={(value) => {
                        setDisableDbDebug(value);
                        updateSetting('disable_db_debug', value);
                    }}
                />

                <ToggleControl
                    label={__('Disable WLW Manifest Scripts', 'simply-static')}
                    checked={disableWLW}
                    onChange={(value) => {
                        setDisableWLW(value);
                        updateSetting('disable_wlw_manifest', value);
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

export default Optimize;