import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
} from "@wordpress/components";
import {useContext} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import Integration from "../components/Integration";

const {__} = wp.i18n;

function IntegrationsSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved, maybeQueueIntegration} = useContext(SettingsContext);

    const setSavingSettings = async () => {
        try {
            const { shouldReload } = await saveSettings();
            setSettingsSaved(true);
            if (shouldReload) {
                // Give the success notice a brief moment, then reload to reflect UI changes
                // and return to the Integrations page route afterwards.
                setTimeout(() => {
                    try {
                        if (typeof window !== 'undefined') {
                            // Persist redirect target so we can restore it deterministically after reload.
                            const target = '/integrations';
                            if (window.localStorage) {
                                try {
                                    // Explicitly set the initial-page key consumed by SettingsPage on mount
                                    // so the NavigatorProvider starts at the Integrations route after reload.
                                    window.localStorage.setItem('ss-initial-page', target);
                                } catch (e) {}
                            }
                        }
                    } catch (e) {
                        // ignore and proceed to reload
                    }
                    window.location.reload();
                }, 400);
                return;
            }
        } catch (e) {
            // noop; keep previous behavior
        }

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
        // Deactivation may also require an immediate UI reload (e.g., hiding UI pieces)
        // So we treat deactivation the same as activation for reload-flagged integrations
        maybeQueueIntegration(integration);
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