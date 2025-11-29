import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, ToggleControl, TextareaControl, Flex, FlexItem, ExternalLink,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;

function FormSettings() {
    const {
        settings,
        updateSetting,
        saveSettings,
        settingsSaved,
        setSettingsSaved,
        isPro
    } = useContext(SettingsContext);
    const [corsMethod, setCorsMethod] = useState('allowed_http_origins');
    const [useForms, setUseForms] = useState(false);
    const [useComments, setUseComments] = useState(false);
    const [saveFormEntries, setSaveFormEntries] = useState(true);
    const [pagesSlugs, setPagesSlugs] = useState(false);
    const [enableCors, setEnableCors] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);

            if (useForms) {
                localStorage.setItem('ss-initial-page', '/forms');
                window.location.reload();
            }
        }, 2000);
    }

    const getPages = () => {
        apiFetch({path: '/simplystatic/v1/pages-slugs'}).then((fetched_pages) => {
            let pages = fetched_pages;

            pages.unshift({label: __('No page selected', 'simply-static'), value: ''});
            setPagesSlugs(pages);
        });
    }

    useEffect(() => {
        getPages();

        if (settings.fix_cors) {
            setCorsMethod(settings.fix_cors);
        }

        if (settings.use_forms) {
            setUseForms(settings.use_forms);
        }

        if (settings.use_comments) {
            setUseComments(settings.use_comments);
        }

        // Save Form Entries (default on)
        if (typeof settings.save_form_entries !== 'undefined') {
            setSaveFormEntries(!!settings.save_form_entries);
        } else {
            setSaveFormEntries(true);
        }

        // Enable CORS (default off)
        if (typeof settings.enable_cors !== 'undefined') {
            setEnableCors(!!settings.enable_cors);
        } else {
            setEnableCors(false);
        }
    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Forms', 'simply-static')}</b>
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
                    label={__('Use forms?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        useForms
                            ? __('Use Forms on your static website.', 'simply-static')
                            : __('Don\'t use forms on your static website.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={useForms}
                    onChange={(value) => {
                        setUseForms(value);
                        updateSetting('use_forms', value);
                    }}
                />
                {useForms && (
                    <ToggleControl
                        label={__('Store form entries', 'simply-static')}
                        __nextHasNoMarginBottom
                        help={
                            saveFormEntries
                                ? __('Store form entries inside WordPress (requires Simply Static Webhook).', 'simply-static')
                                : __('Do not store form entries in WordPress.', 'simply-static')
                        }
                        disabled={('free' === options.plan || !isPro())}
                        checked={!!saveFormEntries}
                        onChange={(value) => {
                            setSaveFormEntries(value);
                            updateSetting('save_form_entries', value);
                        }}
                    />
                )}
                {useForms && options.form_connection_url && 'free' !== options.plan &&
                    <Button href={options.form_connection_url}
                            variant="secondary">{__('Create a form connection', 'simply-static')}</Button>
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Comments', 'simply-static')}</b>
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
                    label={__('Use comments?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        useComments
                            ? __('Use comments on your static website.', 'simply-static')
                            : __('Don\'t use comments on your static website.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={useComments}
                    onChange={(value) => {
                        setUseComments(value);
                        updateSetting('use_comments', value);
                    }}
                />

                {useComments &&
                    <>
                        <SelectControl
                            label={__('Select a redirect page', 'content-protector')}
                            options={pagesSlugs}
                            help={__('The post will be regenerated after comment submission, but it might take a while so its good practice to redirect the visitor.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.comment_redirect}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            onChange={(value) => {
                                updateSetting('comment_redirect', value);
                            }}
                        />
                    </>
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {(useForms || useComments) && (
            <>
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('Captcha', 'simply-static')}</b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) && (
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            )}
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <p>{__('We will automatically protect your forms and comments with a captcha on your static site.', 'simply-static')}</p>
                        <SelectControl
                            label={__('Captcha Service', 'simply-static')}
                            help={__('Choose which captcha service to use. More options coming soon.', 'simply-static')}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.captcha_service || 'turnstile'}
                            options={[
                                { label: __('Cloudflare Turnstile', 'simply-static'), value: 'turnstile' },
                            ]}
                            onChange={(value) => updateSetting('captcha_service', value)}
                        />
                        <Spacer margin={3}/>
                        {(settings.captcha_service || 'turnstile') === 'turnstile' && (
                            <>
                                <TextControl
                                    label={__('Site Key', 'simply-static')}
                                    help={__('Your public key will be used on the static site.', 'simply-static')}
                                    placeholder={__('Enter your Turnstile site key', 'simply-static')}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    disabled={('free' === options.plan || !isPro())}
                                    value={settings.cloudflare_turnstile_site_key || ''}
                                    onChange={(value) => updateSetting('cloudflare_turnstile_site_key', value)}
                                />
                                <Spacer margin={3}/>
                                <TextControl
                                    label={__('Secret Key', 'simply-static')}
                                    help={__('Your secret key will be stored in WordPress.', 'simply-static')}
                                    placeholder={__('Enter your Turnstile secret key', 'simply-static')}
                                    type={'password'}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    disabled={('free' === options.plan || !isPro())}
                                    value={settings.cloudflare_turnstile_secret_key || ''}
                                    onChange={(value) => updateSetting('cloudflare_turnstile_secret_key', value)}
                                />
                                <Spacer margin={3}/>
                                <SelectControl
                                    label={__('Theme', 'simply-static')}
                                    help={__('Choose how the widget should look on your site.', 'simply-static')}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    disabled={('free' === options.plan || !isPro())}
                                    value={settings.cloudflare_turnstile_theme || 'auto'}
                                    options={[
                                        { label: __('Auto', 'simply-static'), value: 'auto' },
                                        { label: __('Light', 'simply-static'), value: 'light' },
                                        { label: __('Dark', 'simply-static'), value: 'dark' },
                                    ]}
                                    onChange={(value) => updateSetting('cloudflare_turnstile_theme', value)}
                                />
                                <Spacer margin={3}/>
                                <SelectControl
                                    label={__('Size', 'simply-static')}
                                    help={__('Choose the widget size.', 'simply-static')}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    disabled={('free' === options.plan || !isPro())}
                                    value={settings.cloudflare_turnstile_size || 'normal'}
                                    options={[
                                        { label: __('Normal', 'simply-static'), value: 'normal' },
                                        { label: __('Flexible', 'simply-static'), value: 'flexible' },
                                        { label: __('Compact', 'simply-static'), value: 'compact' },
                                    ]}
                                    onChange={(value) => updateSetting('cloudflare_turnstile_size', value)}
                                />
                            </>
                        )}
                    </CardBody>
                </Card>
                <Spacer margin={5}/>
            </>
        )}

        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('CORS', 'simply-static')}<HelperVideo
                            title={__('How to deal with CORS', 'simply-static')}
                            videoUrl={'https://youtu.be/fArtvZhkU14'}/></b>
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
                    label={__('Enable CORS', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={enableCors
                        ? __('CORS settings enabled. Configure options below.', 'simply-static')
                        : __('Turn on to configure CORS for Forms and Comments requests.', 'simply-static')}
                    disabled={('free' === options.plan || !isPro())}
                    checked={!!enableCors}
                    onChange={(value) => {
                        setEnableCors(value);
                        updateSetting('enable_cors', value);
                    }}
                />
                {enableCors && (
                    <>
                        <p>
                            {__('When using Forms and Comments in Simply Static Pro you may encounter CORS issues as you make requests from your static website to your original one.', 'simply-static')}
                        </p>
                        <Notice status="warning" isDismissible={false}>
                            <p>
                                {__('Due to the variety of server setups out there, you may need to make changes on your server.', 'simply-static')}
                            </p>
                        </Notice>
                        <Spacer margin={5}/>
                        <TextControl
                            label={__('Static URL', 'simply-static')}
                            type={"url"}
                            placeholder={'https://static-site.com'}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add the URL of your static website to allow CORS from it.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.static_url}
                            onChange={(url) => {
                                updateSetting('static_url', url);
                            }}
                        />
                        <SelectControl
                            label={__('Select CORS method', 'simply-static')}
                            value={corsMethod}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Choose one of the methods to allow CORS for your website.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            options={[
                                {label: 'allowed_http_origins', value: 'allowed_http_origins'},
                                {label: 'wp_headers', value: 'wp_headers'},
                            ]}
                            onChange={(method) => {
                                setCorsMethod(method);
                                updateSetting('fix_cors', method);
                            }}
                        />
                    </>
                )}
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

export default FormSettings;