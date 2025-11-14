import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import Integration from "../components/Integration";

const {__} = wp.i18n;

function IntegrationsSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved, maybeQueueIntegration, maybeUnqueueIntegration} = useContext(SettingsContext);

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
        maybeQueueIntegration(integration);
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
        maybeUnqueueIntegration(integration);
    }

    const toggleIntegration = ( integration, value ) => {

        if ( value ) {
            saveIntegration(integration);
        } else {
            removeIntegration(integration);
        }
    }

    const canRunIntegrations = Object.keys(options.integrations).filter( ( item ) => { return options.integrations[item].can_run && !options.integrations[item].always_active } );
    const canNotRunIntegrations = Object.keys(options.integrations).filter( ( item ) => { return !options.integrations[item].can_run && !options.integrations[item].always_active });

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Integrations', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                {__('Control Integrations that will be active during the export of the static site.', 'simply-static')}
                <Spacer margin={10} />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {canRunIntegrations.map( ( item ) => {
            const integration = options.integrations[item];
            return <Integration key={integration.id || item} integration={integration} settings={settings} toggleIntegration={toggleIntegration} />

        })}

        <Spacer margin={5}/>
        {canNotRunIntegrations.map( ( item ) => {
            const integration = options.integrations[item];
            return <Integration key={integration.id || item} integration={integration} settings={settings} toggleIntegration={toggleIntegration} />
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