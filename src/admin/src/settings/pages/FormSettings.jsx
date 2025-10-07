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
    const [pagesSlugs, setPagesSlugs] = useState(false);

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
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Embed Dynamic Content (iFrame)', 'simply-static')}<HelperVideo
                            title={__('Embed Dynamic Content (iFrame)', 'simply-static')}
                            videoUrl={'https://youtu.be/ZGRaG_Jma7E'}/></b>
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
                <p>
                    {__('We replace the HTML of the URLs with an iFrame that embeds the content directly from your WordPress website.', 'simply-static')}<br></br>
                    {__('This way you can use dynamic elements on your static website without the need of a specific integration.', 'simply-static')}
                </p>
                <Notice status="warning" isDismissible={false}>
                    <p>
                        {__('This requires your WordPress website to be online all the time.', 'simply-static')}
                    </p>
                </Notice>
                <Spacer margin={5}/>
                <TextareaControl
                    label={__('URLs to embed as an iFrame', 'simply-static')}
                    placeholder={options.home + "/my-form-page/"}
                    __nextHasNoMarginBottom
                    help={__('If you want to embed specific pages from your WordPress website into your static website, add the URLs here (one per line).', 'simply-static')}
                    disabled={('free' === options.plan || !isPro())}
                    value={settings.iframe_urls}
                    onChange={(value) => {
                        updateSetting('iframe_urls', value);
                    }}
                />
                <TextareaControl
                    label={__('Custom CSS', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={__('These styles will only apply to the embedded pages, not your entire website.', 'simply-static')}
                    disabled={('free' === options.plan || !isPro())}
                    value={settings.iframe_custom_css}
                    onChange={(value) => {
                        updateSetting('iframe_custom_css', value);
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

export default FormSettings;