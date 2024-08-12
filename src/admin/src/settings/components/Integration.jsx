import {Button, Card, CardHeader, ToggleControl} from "@wordpress/components";
import HelperVideo from "./HelperVideo"

const {__} = wp.i18n;

function Integration({integration, settings, toggleIntegration}) {
    let isActive = integration.active;
    const isPro = integration.pro;
    const canRun = integration.can_run;
    const alwaysActive = integration.always_active;

    if (typeof settings.integrations !== 'undefined' && settings.integrations !== false) {
        isActive = settings.integrations.indexOf(integration.id) >= 0;
    }

    let canUse = options.plan === 'pro' || !isPro;

    return <Card>
        <CardHeader className={'ss-integration'}>
            <div>
                <strong>
                    {integration.name || integration.id}
                    { integration.id === 'redirection' &&
                        <HelperVideo
                            title={__('Automated Redirects with Redirection', 'simply-static')}
                            videoUrl={'https://youtu.be/sS4BQcZ4dN8'}/>
                    }
                    { integration.id === 'complianz' &&
                        <HelperVideo
                            title={__('Cookie Consent with Complianz', 'simply-static')}
                            videoUrl={'https://youtu.be/GPKYtt8A5QE'}/>
                    }
                </strong>
                {integration.description != '' && [
                    <br/>,
                    integration.description
                ]}
            </div>
            {!canRun && <span className={'ss-align-right ss-no-shrink'}><em>Missing Plugin</em>{!canUse &&
                <div><Button variant="link" href={"https://simplystatic.com/pricing/"}>
                    {__('Requires Simply Static Pro', 'simply-static')}
                </Button></div>}</span>}
            {(canRun && canUse && !alwaysActive) &&
                <ToggleControl
                    className={'integration-toggle'}
                    checked={isActive}
                    onChange={(value) => {
                        toggleIntegration(integration.id, value);
                    }}
                />
            }
            {(canRun && canUse && alwaysActive) && <em>Always Active</em>}
            {(canRun && !canUse) &&
                <Button variant="primary" href={"https://simplystatic.com/pricing/"}>
                    {__('Get the Pro version', 'simply-static')}
                </Button>
            }
        </CardHeader>
    </Card>
}

export default Integration;