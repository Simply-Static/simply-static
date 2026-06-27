import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    ToggleControl, TextControl, Dashicon, Flex, FlexItem, ExternalLink, TextareaControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import HelperVideo from "../components/HelperVideo";
import StudioNotice from "../components/StudioNotice";

const {__} = wp.i18n;

function Optimize() {
    const {
        settings,
        updateSetting,
        saveSettings,
        settingsSaved,
        setSettingsSaved,
        isPro,
        isStudio
    } = useContext(SettingsContext);
    const [minifyFiles, setMinifyFiles] = useState(false);
    const [minifyCss, setMinifyCss] = useState(false);
    const [minifyJavascript, setMinifyJavascript] = useState(false);

    const [cssOptimize, setCssOptimize] = useState(false);
    const [cssOptimizeDeferCss, setCssOptimizeDeferCss] = useState(false);
    const [cssOptimizeDeferJs, setCssOptimizeDeferJs] = useState(false);
    const [cssOptimizeGoogleFonts, setCssOptimizeGoogleFonts] = useState(false);
    const [cssOptimizePreconnectHints, setCssOptimizePreconnectHints] = useState(false);
    const [cssOptimizeDelayJs, setCssOptimizeDelayJs] = useState(false);

    const [cssJsAggregateCss, setCssJsAggregateCss] = useState(false);
    const [cssJsAggregateJs, setCssJsAggregateJs] = useState(false);
    const [cssJsAggregateProtectJquery, setCssJsAggregateProtectJquery] = useState(false);

    const [criticalCss, setCriticalCss] = useState(false);

    const [versionCss, setVersionCss] = useState(false);
    const [versionJs, setVersionJs] = useState(false);

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

        if (settings.minify_css) {
            setMinifyCss(settings.minify_css);
        }

        if (settings.minify_js) {
            setMinifyJavascript(settings.minify_js);
        }


        if (settings.use_css_optimize) {
            setCssOptimize(settings.use_css_optimize);
        }

        if (settings.css_optimize_defer_css) {
            setCssOptimizeDeferCss(settings.css_optimize_defer_css);
        }

        if (settings.css_optimize_defer_js) {
            setCssOptimizeDeferJs(settings.css_optimize_defer_js);
        }

        if (settings.css_optimize_google_fonts) {
            setCssOptimizeGoogleFonts(settings.css_optimize_google_fonts);
        }

        setCssOptimizePreconnectHints(!!settings.css_optimize_preconnect_hints);

        if (settings.css_optimize_delay_js) {
            setCssOptimizeDelayJs(settings.css_optimize_delay_js);
        }

        if (settings.css_js_aggregate_css) {
            setCssJsAggregateCss(settings.css_js_aggregate_css);
        }

        if (settings.css_js_aggregate_js) {
            setCssJsAggregateJs(settings.css_js_aggregate_js);
        }

        if (settings.css_js_aggregate_protect_jquery) {
            setCssJsAggregateProtectJquery(settings.css_js_aggregate_protect_jquery);
        }


        if (settings.use_critical_css) {
            setCriticalCss(settings.use_critical_css);
        }

        if (settings.version_css) {
            setVersionCss(settings.version_css);
        }

        if (settings.version_js) {
            setVersionJs(settings.version_js);
        }

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Minify & Aggregation', 'simply-static')}<HelperVideo
                            title={__('How to minify HTML, CSS and JavaScript?', 'simply-static')}
                            videoUrl={'https://youtu.be/52IKv5ai-i4'}/></b>
                    </FlexItem>
                    {('free' === options.plan || !isPro()) &&
                        <FlexItem>
                            <ExternalLink
                                href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                        </FlexItem>
                    }
                </Flex>
            </CardHeader>
            <CardBody>
                <p>{__('Minify and aggregate CSS, JavaScript, and HTML files to reduce file sizes and HTTP requests for faster page loads.', 'simply-static')}</p>
                <ToggleControl
                    label={__('Enable Minify & Aggregation?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        minifyFiles
                            ? __('Minify and aggregate files on your static website.', 'simply-static')
                            : __('Don\'t minify or aggregate files on your static website.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={minifyFiles}
                    onChange={(value) => {
                        setMinifyFiles(value);
                        updateSetting('use_minify', value);
                    }}
                />

                {minifyFiles &&
                    <>
                        {isStudio() &&
                            <StudioNotice
                                heading={__('Minification is handled by Studio', 'simply-static')}
                                cta={null}
                            >
                                <p>{__('Studio prepares optimized build assets automatically, so the low-level CSS and JavaScript minification options are not needed for Studio environments.', 'simply-static')}</p>
                            </StudioNotice>
                        }
                        <h4 style={{marginTop: '16px', marginBottom: '8px'}}>{__('Minify', 'simply-static')}</h4>

                        <ToggleControl
                            label={__('Minify CSS', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={
                                minifyCss
                                    ? __('Minify CSS files.', 'simply-static')
                                    : __('Don\'t minify CSS files.', 'simply-static')
                            }
                            disabled={isStudio() || 'free' === options.plan || !isPro()}
                            checked={minifyCss}
                            onChange={(value) => {
                                setMinifyCss(value);
                                updateSetting('minify_css', value);
                            }}
                        />
                        {minifyCss && <>
                            <TextareaControl
                                label={__('Exclude Stylesheet URLs', 'simply-static')}
                                __nextHasNoMarginBottom
                                help={__('Exclude URLs from minification (one per line).', 'simply-static')}
                                disabled={isStudio() || 'free' === options.plan || !isPro()}
                                value={settings.minify_css_exclude}
                                onChange={(excludes) => {
                                    updateSetting('minify_css_exclude', excludes);
                                }}
                            />
                        </>}

                        <ToggleControl
                            label={__('Minify JavaScript', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={
                                minifyJavascript
                                    ? __('Minify JavaScript files.', 'simply-static')
                                    : __('Don\'t minify JavaScript files.', 'simply-static')
                            }
                            disabled={isStudio() || 'free' === options.plan || !isPro()}
                            checked={minifyJavascript}
                            onChange={(value) => {
                                setMinifyJavascript(value);
                                updateSetting('minify_js', value);
                            }}
                        />
                        {minifyJavascript && <>
                            <TextareaControl
                                label={__('Exclude JavaScript URLs', 'simply-static')}
                                __nextHasNoMarginBottom
                                help={__('Exclude URLs from minification (one per line).', 'simply-static')}
                                disabled={isStudio() || 'free' === options.plan || !isPro()}
                                value={settings.minify_js_exclude}
                                onChange={(excludes) => {
                                    updateSetting('minify_js_exclude', excludes);
                                }}
                            />
                        </>}

                        <ToggleControl
                            label={__('Protect jQuery Globals', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Prevent mangling of jQuery, $, and wp global variables during JS minification.', 'simply-static')}
                            disabled={isStudio() || 'free' === options.plan || !isPro()}
                            checked={cssJsAggregateProtectJquery}
                            onChange={(value) => {
                                setCssJsAggregateProtectJquery(value);
                                updateSetting('css_js_aggregate_protect_jquery', value);
                            }}
                        />

                        <h4 style={{marginTop: '16px', marginBottom: '8px'}}>{__('Aggregate', 'simply-static')}</h4>

                        <ToggleControl
                            label={__('Aggregate CSS', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Combine multiple CSS files into fewer bundles grouped by media attribute and deferral status.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssJsAggregateCss}
                            onChange={(value) => {
                                setCssJsAggregateCss(value);
                                updateSetting('css_js_aggregate_css', value);
                            }}
                        />

                        <ToggleControl
                            label={__('Aggregate JavaScript', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Combine multiple JS files into fewer bundles grouped by loading attributes (defer, async, delayed) and position (head/body).', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssJsAggregateJs}
                            onChange={(value) => {
                                setCssJsAggregateJs(value);
                                updateSetting('css_js_aggregate_js', value);
                            }}
                        />

                        <TextareaControl
                            label={__('Exclude Patterns', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Glob patterns for files to exclude from aggregation (one per line). Use this for scripts that break when concatenated, such as Google Maps init scripts, payment gateway SDKs, or scripts using document.write().', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.css_js_aggregate_exclude_patterns}
                            placeholder={'*maps*\n*stripe*\n*paypal*'}
                            onChange={(patterns) => {
                                updateSetting('css_js_aggregate_exclude_patterns', patterns);
                            }}
                        />

                    </>
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Defer & Delay', 'simply-static')}</b>
                    </FlexItem>
                    {('free' === options.plan || !isPro()) &&
                        <FlexItem>
                            <ExternalLink
                                href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                        </FlexItem>
                    }
                </Flex>
            </CardHeader>
            <CardBody>
                <p>{__('Eliminate render-blocking CSS and JavaScript resources to improve Lighthouse scores and page load speed.', 'simply-static')}</p>
                <ToggleControl
                    label={__('Enable Defer & Delay?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        cssOptimize
                            ? __('Optimize render-blocking resources during static export.', 'simply-static')
                            : __('Don\'t optimize render-blocking resources.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={cssOptimize}
                    onChange={(value) => {
                        setCssOptimize(value);
                        updateSetting('use_css_optimize', value);
                    }}
                />

                {cssOptimize &&
                    <>
                        <ToggleControl
                            label={__('Defer Non-Critical CSS', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Convert non-critical stylesheets to load asynchronously using the media="print" pattern.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssOptimizeDeferCss}
                            onChange={(value) => {
                                setCssOptimizeDeferCss(value);
                                updateSetting('css_optimize_defer_css', value);
                            }}
                        />

                        {cssOptimizeDeferCss && <>
                            <TextareaControl
                                label={__('Critical CSS Patterns', 'simply-static')}
                                __nextHasNoMarginBottom
                                help={__('Glob patterns for stylesheets that must remain render-blocking (one per line). Default: */style.css, */styles.css, */global.css, */theme*.css, */wp-block-*.css', 'simply-static')}
                                disabled={('free' === options.plan || !isPro())}
                                value={settings.css_optimize_critical_patterns}
                                placeholder={'*/style.css\n*/styles.css\n*/global.css\n*/theme*.css\n*/wp-block-*.css'}
                                onChange={(patterns) => {
                                    updateSetting('css_optimize_critical_patterns', patterns);
                                }}
                            />
                        </>}

                        <ToggleControl
                            label={__('Defer Synchronous Scripts', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Add the defer attribute to render-blocking scripts. Preserves execution order (safe for jQuery dependencies).', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssOptimizeDeferJs}
                            onChange={(value) => {
                                setCssOptimizeDeferJs(value);
                                updateSetting('css_optimize_defer_js', value);
                            }}
                        />

                        {cssOptimizeDeferJs && <>
                            <TextareaControl
                                label={__('Defer Exclude Patterns', 'simply-static')}
                                __nextHasNoMarginBottom
                                help={__('Additional glob patterns for scripts to exclude from deferral (one per line). Built-in patterns already exclude WordPress core scripts: jQuery, wp-i18n, wp-hooks, wp-url, wp-api-fetch, and wp-api.', 'simply-static')}
                                disabled={('free' === options.plan || !isPro())}
                                value={settings.css_optimize_defer_js_excludes}
                                placeholder={'*my-critical-script.js*\n*another-script*'}
                                onChange={(patterns) => {
                                    updateSetting('css_optimize_defer_js_excludes', patterns);
                                }}
                            />
                        </>}

                        <ToggleControl
                            label={__('Optimize Google Fonts', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Preload Google Fonts stylesheets, inject the Google Fonts preconnect hint, and add display=swap for faster font loading.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssOptimizeGoogleFonts}
                            onChange={(value) => {
                                setCssOptimizeGoogleFonts(value);
                                updateSetting('css_optimize_google_fonts', value);
                            }}
                        />

                        <ToggleControl
                            label={__('Add Preconnect Hints', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Add preconnect resource hints for high-value third-party font, icon, script, and image origins found in exported pages.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssOptimizePreconnectHints}
                            onChange={(value) => {
                                setCssOptimizePreconnectHints(value);
                                updateSetting('css_optimize_preconnect_hints', value);
                            }}
                        />

                        <ToggleControl
                            label={__('Delay Non-Critical JavaScript', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Delay third-party scripts (analytics, tag managers, tracking pixels) until user interaction to reduce Total Blocking Time. Scripts load on first click, scroll, or keypress.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={cssOptimizeDelayJs}
                            onChange={(value) => {
                                setCssOptimizeDelayJs(value);
                                updateSetting('css_optimize_delay_js', value);
                            }}
                        />

                        {cssOptimizeDelayJs && <>
                            <TextareaControl
                                label={__('Additional Delay Patterns', 'simply-static')}
                                __nextHasNoMarginBottom
                                help={__('Additional glob patterns for scripts to delay (one per line). Built-in patterns already cover Google (Tag Manager, Analytics, Ads, ReCaptcha), HubSpot, Facebook/Meta Pixel, Hotjar, Clarity, Bing UET, Cloudflare (Insights, Turnstile), CleanTalk, Matomo/Piwik, Plausible, Twitter/X, LinkedIn, Pinterest, Tawk.to, Crisp, LiveChat, Intercom, Drift, Tidio, Segment, Amplitude, Mixpanel, Optimizely, Mouseflow, Lucky Orange, Crazy Egg, FullStory, LogRocket, and Sentry.', 'simply-static')}
                                disabled={('free' === options.plan || !isPro())}
                                value={settings.css_optimize_delay_js_patterns}
                                placeholder={'*example-tracking.com*\n*custom-analytics*'}
                                onChange={(patterns) => {
                                    updateSetting('css_optimize_delay_js_patterns', patterns);
                                }}
                            />
                        </>}
                    </>
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Image Optimization', 'simply-static')}<HelperVideo
                            title={__('How to optimize images with ShortPixel?', 'simply-static')}
                            videoUrl={'https://youtu.be/OIfKcXz3cxY'}/></b>
                    </FlexItem>
                    {('free' === options.plan || !isPro()) &&
                        <FlexItem>
                            <ExternalLink
                                href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                        </FlexItem>
                    }
                </Flex>
            </CardHeader>
            <CardBody>
                {isStudio() &&
                    <StudioNotice
                        heading={__('Image optimization is handled by Studio', 'simply-static')}
                        cta={null}
                    >
                        <p>{__('Studio optimizes the static build pipeline for its hosting environment, so you do not need to connect ShortPixel for Studio deployments.', 'simply-static')}</p>
                    </StudioNotice>
                }
                <ToggleControl
                    label={__('Optimize Images with ShortPixel?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        settings.shortpixel_enabled
                            ? __('Optimize images with the ShortPixel API.', 'simply-static')
                            : __('Don\'t optimize images with the ShortPixel API.', 'simply-static')
                    }
                    disabled={isStudio() || 'free' === options.plan || !isPro()}
                    checked={!!settings.shortpixel_enabled}
                    onChange={(value) => {
                        updateSetting('shortpixel_enabled', value);
                    }}
                />

                {settings.shortpixel_enabled && <>
                    <TextControl
                        label={__('ShortPixel API Key', 'simply-static')}
                        type={"password"}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        value={settings.shortpixel_api_key}
                        disabled={isStudio() || 'free' === options.plan || !isPro()}
                        onChange={(apiKey) => {
                            updateSetting('shortpixel_api_key', apiKey);
                        }}
                    />
                    <ToggleControl
                        label={__('Convert to webP', 'simply-static')}
                        __nextHasNoMarginBottom
                        help={
                            settings.shortpixel_webp_enabled
                                ? __('Convert images to webp format.', 'simply-static')
                                : __('Don\'t convert images to webp format', 'simply-static')
                        }
                        checked={!!settings.shortpixel_webp_enabled}
                        disabled={isStudio() || 'free' === options.plan || !isPro()}
                        onChange={(value) => {
                            updateSetting('shortpixel_webp_enabled', value);
                        }}
                    />
                    <ToggleControl
                        label={__('Backup the original images?', 'simply-static')}
                        __nextHasNoMarginBottom
                        help={
                            settings.shortpixel_backup_enabled
                                ? __('Back original images.', 'simply-static')
                                : __('Don\'t backup original images.', 'simply-static')
                        }
                        checked={!!settings.shortpixel_backup_enabled}
                        disabled={isStudio() || 'free' === options.plan || !isPro()}
                        onChange={(value) => {
                            updateSetting('shortpixel_backup_enabled', value);
                        }}
                    />
                    {settings.shortpixel_backup_enabled && <>
                        <Notice status={'warning'} isDismissible={false}>
                            {__('It will preserve every image which might increase your disk space usage.', 'simply-static')}
                        </Notice>
                        <Spacer padding={1}></Spacer>
                        <Button disabled={isStudio() || shortPixelResetting} onClick={restoreBackups}
                                variant="secondary">
                            {!shortPixelResetting && __('Restore Original Images', 'simply-static')}
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
                    <Flex>
                        <FlexItem>
                            <b>{__('Critical CSS', 'simply-static')}</b>
                        </FlexItem>
                        {!isStudio() &&
                            <FlexItem>
                                <ExternalLink
                                    href="https://simplystatic.com/simply-static-studio/"> {__('Requires Simply Static Studio', 'simply-static')}</ExternalLink>
                            </FlexItem>
                        }
                    </Flex>
                </CardHeader>
                <CardBody>
                    <p>{__('Generate critical above-the-fold CSS to eliminate render-blocking stylesheets and improve page load speed. Critical CSS is generated for the homepage and injected into every page.', 'simply-static')}</p>
                    <ToggleControl
                        label={__('Enable Critical CSS Generation?', 'simply-static')}
                        __nextHasNoMarginBottom
                        help={
                            criticalCss
                                ? __('Critical CSS will be generated during static export.', 'simply-static')
                                : __('Don\'t generate critical CSS.', 'simply-static')
                        }
                        disabled={!isStudio()}
                        checked={criticalCss}
                        onChange={(value) => {
                            setCriticalCss(value);
                            updateSetting('use_critical_css', value);
                        }}
                    />

                    {criticalCss && <>
                        <TextareaControl
                            label={__('Additional URLs', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('By default, critical CSS is generated from the homepage only. Add additional page URLs here (one per line) if those pages have significantly different styles.', 'simply-static')}
                            disabled={!isStudio()}
                            value={settings.critical_css_additional_urls}
                            placeholder={'https://example.com/about\nhttps://example.com/contact'}
                            onChange={(urls) => {
                                updateSetting('critical_css_additional_urls', urls);
                            }}
                        />
                        <TextareaControl
                            label={__('Custom CSS', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Add custom CSS that will be appended to the generated critical CSS. Use this to fix minor visual glitches.', 'simply-static')}
                            disabled={!isStudio()}
                            value={settings.critical_css_custom}
                            placeholder={'.header { position: sticky; top: 0; }'}
                            onChange={(css) => {
                                updateSetting('critical_css_custom', css);
                            }}
                        />
                    </>}
                </CardBody>
            </Card>
            <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Versioning', 'simply-static')}</b>
                    </FlexItem>
                    {('free' === options.plan || !isPro()) &&
                        <FlexItem>
                            <ExternalLink
                                href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                        </FlexItem>
                    }
                </Flex>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Version CSS?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        versionCss
                            ? __('Appends a version query string to CSS files.', 'simply-static')
                            : __('Don\'t append a version query string to CSS files.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={versionCss}
                    onChange={(value) => {
                        setVersionCss(value);
                        updateSetting('version_css', value);
                    }}
                />
                <ToggleControl
                    label={__('Version JavaScript?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        versionJs
                            ? __('Appends a version query string to JavaScript files.', 'simply-static')
                            : __('Don\'t append a version query string to JavaScript files.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={versionJs}
                    onChange={(value) => {
                        setVersionJs(value);
                        updateSetting('version_js', value);
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
            {'pro' === options.plan && isPro() &&
                <Button onClick={setSavingSettings}
                        variant="primary">{__('Save Settings', 'simply-static')}</Button>
            }
        </div>
    </div>)
}

export default Optimize;
