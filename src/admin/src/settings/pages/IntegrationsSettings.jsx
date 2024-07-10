import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, ToggleControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function IntegrationsSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    const saveIntegration = ( integration ) => {
        let integrations = settings.integrations;
        if ( false === integrations ) {
            integrations = [];
        }

        if ( integrations.indexOf(integration) >= 0 ) {
            return;
        }

        integrations.push(integration);
        updateSetting( 'integrations', integrations );
    }

    const removeIntegration = ( integration ) => {
        let integrations = settings.integrations;
        if ( false === integrations ) {
            integrations = [];
        }

        const index =  integrations.indexOf(integration);
        if ( index < 0 ) {
            return;
        }

        integrations.splice(index, 1);
        updateSetting( 'integrations', integrations );
    }

    const toggleIntegration = ( integration, value ) => {
        console.log(value);
        console.log(integration);
        if ( value ) {
            saveIntegration(integration);
        } else {
            removeIntegration(integration);
        }
    }

console.log(settings.integrations);
    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Integrations', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Control Integrations that will be active during the export of the static site.', 'simply-static')}</p>

            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {Object.keys( options.integrations ).map( ( item ) => {
            console.log(settings);
            const integration = options.integrations[item];
            let isActive    = integration.active;
            const isPro     = integration.pro;

            if (typeof settings.integrations !== 'undefined' && settings.integrations !== false ) {
                isActive = settings.integrations.indexOf( integration.id ) >= 0;
            }

            let canUse = options.plan === 'pro' || !isPro;

            return [<Card>
                        <CardHeader>
                            <div>
                                <strong>{integration.name || integration.id}</strong>
                                {integration.description != '' && [
                                    <br/>,
                                    integration.description
                                ]}
                            </div>
                            {canUse && <ToggleControl
                                checked={isActive}
                                onChange={(value) => {
                                     toggleIntegration(integration.id, value);
                                }}
                            />}
                            {!canUse &&
                                <Button variant="primary" href={"https://simplystatic.com/pricing/"}>
                                    {__('Get the Pro version', 'simply-static')}
                                </Button>
                            }
                        </CardHeader>
            </Card>,
                <Spacer margin={5}/>
            ]
        })}
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

export default IntegrationsSettings;