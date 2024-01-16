import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    ToggleControl, TextControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function Optimize() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [minifyFiles, setMinifyFiles] = useState(false);
    const [minifyHtml, setMinifyHtml] = useState(false);
    const [minifyCss, setMinifyCss] = useState(false);
    const [minifyInlineCss, setMinifyInlineCss] = useState(false);
    const [minifyJavascript, setMinifyJavascript] = useState(false);
    const [minifyInlineJavascript, setMinifyInlineJavascript] = useState(false);
    const [renamePluginFolders, setRenamePluginFolders] = useState(false);
    const [wpContentFolder, setWpContentFolder] = useState('wp-content');
    const [wpIncludesFolder, setWpIncludesFolder] = useState('wp-includes');
    const [wpUploadsFolder, setWpUploadsFolder] = useState('wp-content/uploads');
    const [wpPluginsFolder, setWpPluginsFolder] = useState('wp-content/plugins');
    const [authorUrl, setAuthorUrl] = useState('author');

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
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

        if (settings.wp_content_folder) {
            setWpContentFolder(settings.wp_content_folder);
        }

        if (settings.wp_includes_folder) {
            setWpIncludesFolder(settings.wp_includes_folder);
        }

        if (settings.wp_uploads_folder) {
            setWpUploadsFolder(settings.wp_uploads_folder);
        }

        if (settings.wp_plugins_folder) {
            setWpPluginsFolder(settings.wp_plugins_folder);
        }

        if (settings.rename_plugin_folders) {
            setRenamePluginFolders(settings.rename_plugin_folders);
        }

        if (settings.author_url) {
            setAuthorUrl(settings.author_url);
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

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Change', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <TextControl
                    label={__('Folder wp-content', 'simply-static')}
                    help={  __('Change the folder wp-content', 'simply-static') }
                    type={"text"}
                    placeholder={"wp-content"}
                    value={wpContentFolder}
                    onChange={(folder) => {
                        setWpIncludesFolder(folder);
                        updateSetting('wp_content_folder', folder);
                    }}
                />

                <TextControl
                    label={__('Folder wp-includes', 'simply-static')}
                    help={  __('Change the folder wp-includes', 'simply-static') }
                    type={"text"}
                    placeholder={"wp-includes"}
                    value={wpIncludesFolder}
                    onChange={(folder) => {
                        setWpContentFolder(folder);
                        updateSetting('wp_includes_folder', folder);
                    }}
                />

                <TextControl
                    label={__('Folder uploads', 'simply-static')}
                    help={  __('Change the folder uploads', 'simply-static') }
                    type={"text"}
                    placeholder={"uploads"}
                    value={wpUploadsFolder}
                    onChange={(folder) => {
                        setWpUploadsFolder(folder);
                        updateSetting('wp_uploads_folder', folder);
                    }}
                />

                <TextControl
                    label={__('Folder plugins', 'simply-static')}
                    help={  __('Change the folder plugins', 'simply-static') }
                    type={"text"}
                    placeholder={"plugins"}
                    value={wpPluginsFolder}
                    onChange={(folder) => {
                        setWpPluginsFolder(folder);
                        updateSetting('wp_plugins_folder', folder);
                    }}
                />

                <ToggleControl
                    label={__('Rename Plugin Names?', 'simply-static')}
                    help={
                        renamePluginFolders
                            ? __('Rename.', 'simply-static')
                            : __('Keep original.', 'simply-static')
                    }
                    checked={renamePluginFolders}
                    onChange={(value) => {
                        setRenamePluginFolders(value);
                        updateSetting('rename_plugin_folders', value);
                    }}
                />

                <TextControl
                    label={__('Author URL', 'simply-static')}
                    help={  __('Change the author url', 'simply-static') }
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