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

function Optimize() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [minifyFiles, setMinifyFiles] = useState(false);
    const [minifyHtml, setMinifyHtml] = useState(false);
    const [minifyCss, setMinifyCss] = useState(false);
    const [minifyInlineCss, setMinifyInlineCss] = useState(false);
    const [minifyJavascript, setMinifyJavascript] = useState(false);
    const [minifyInlineJavascript, setMinifyInlineJavascript] = useState(false);

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
                <b>{__('Minify', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Minify Files?', 'simply-static')}
                    help={
                        minifyFiles
                            ? 'enable minify files on your static website.'
                            : 'Don\'t enable minify files on your static website.'
                    }
                    checked={minifyFiles}
                    onChange={() => {
                        setMinifyFiles((state) => !state);
                    }}
                />

                {minifyFiles &&
                    <>
                        <ToggleControl
                            label={__('Minify HTML', 'simply-static')}
                            help={
                                minifyHtml
                                    ? 'minify HTML files.'
                                    : 'Don\'t minify HTML files.'
                            }
                            checked={minifyHtml}
                            onChange={() => {
                                setMinifyHtml((state) => !state);
                            }}
                        />

                        <ToggleControl
                            label={__('Minify CSS', 'simply-static')}
                            help={
                                minifyCss
                                    ? 'minify CSS files.'
                                    : 'Don\'t minify CSS files.'
                            }
                            checked={minifyCss}
                            onChange={() => {
                                setMinifyCss((state) => !state);
                            }}
                        />
                        {minifyCss &&
                            <ToggleControl
                                label={__('Minify Inline CSS', 'simply-static')}
                                help={
                                    minifyInlineCss
                                        ? 'minify inline CSS.'
                                        : 'Don\'t minify inline CSS.'
                                }
                                checked={minifyInlineCss}
                                onChange={() => {
                                    setMinifyInlineCss((state) => !state);
                                }}
                            />
                        }
                        <ToggleControl
                            label={__('Minify JavaScript', 'simply-static')}
                            help={
                                minifyJavascript
                                    ? 'minify JavaScript files.'
                                    : 'Don\'t minify JavaScript files.'
                            }
                            checked={minifyJavascript}
                            onChange={() => {
                                setMinifyJavascript((state) => !state);
                            }}
                        />

                        {minifyJavascript &&
                            <ToggleControl
                                label={__('Minify Inline JavaScript', 'simply-static')}
                                help={
                                    minifyInlineJavascript
                                        ? 'minify inline JavaScript.'
                                        : 'Don\'t minify inline JavaScript.'
                                }
                                checked={minifyInlineJavascript}
                                onChange={() => {
                                    setMinifyInlineJavascript((state) => !state);
                                }}
                            />
                        }
                    </>
                }

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

export default Optimize;