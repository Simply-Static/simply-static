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

function FormSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [corsMethod, setCorsMethod] = useState('allowed_http_origins');
    const [useForms, setUseForms] = useState('no');
    const [useComments, setUseComments] = useState('no');

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
                <b>{__('Forms', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <SelectControl
                    label={__('Use forms?', 'simply-static')}
                    value={useForms}
                    help={__('Decide whether or not you want to use forms on your static site.', 'simply-static')}
                    options={[
                        {label: 'no', value: 'no'},
                        {label: 'yes', value: 'yes'},
                    ]}
                    onChange={(value) => {
                        setUseForms(value);
                    }}
                />
                {useForms === 'yes' &&
                    <>
                        <Button variant="secondary"
                                className={"create-form-config"}>{__('Create Form Config', 'simply-static')}</Button>
                    </>
                }

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Comments', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <SelectControl
                    label={__('Use comments?', 'simply-static')}
                    value={useComments}
                    help={__('Decide whether or not you want to use comments on your static site.', 'simply-static')}
                    options={[
                        {label: 'no', value: 'no'},
                        {label: 'yes', value: 'yes'},
                    ]}
                    onChange={(value) => {
                        setUseComments(value);
                    }}
                />
                {useComments === 'yes' &&
                    <TextControl
                        label={__('Redirect URL', 'simply-static')}
                        type={"url"}
                        placeholder={'https://static-example.com/thank-you'}
                        help={__('The page will be generated and committed automatically after a comment was added, but it might take a while so its good practice to redirect the visitor.', 'simply-static')}
                        value={''}
                        onChange={(value) => {

                        }}
                    />
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('CORS', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>
                    {__('When using Forms and Comments in Simply Static Pro you may encouter CORS issues as you make requests from your static website to your original one.', 'simply-static')}
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
                    placeholder={'https://static-example.com'}
                    help={__('Add the URL of your static website to allow CORS from it.', 'simply-static')}
                    value={''}
                    onChange={(value) => {

                    }}
                />
                <SelectControl
                    label={__('Select CORS method', 'simply-static')}
                    value={corsMethod}
                    help={__('Choose one of the methods to allow CORS for your website.', 'simply-static')}
                    options={[
                        {label: 'allowed_http_origins', value: 'allowed_http_origins'},
                        {label: 'wp_headers', value: 'wp_headers'},
                    ]}
                    onChange={(method) => {
                        setCorsMethod(method);
                    }}
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

export default FormSettings;