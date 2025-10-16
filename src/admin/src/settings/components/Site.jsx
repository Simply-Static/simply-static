import apiFetch from "@wordpress/api-fetch";
import Buttons from "./GenerateButtons";
import {useState} from "@wordpress/element";
import {useEffect} from "react";
import {Button, Dashicon} from "@wordpress/components";
const {__} = wp.i18n;

function Site( props ) {
    const { site, setAnyRunning } = props;
    const blogId   = site.id;
    const [canGenerate, setCanGenerate] = useState(!site.running && !site.paused);
    const [isRunning, setIsRunning] = useState(site.running);
    const [isPaused, setIsPaused] = useState(site.paused);

    useEffect(() => {
        if ( isRunning ) {
            setAnyRunning(true);
        }
    }, [isRunning]);

    useEffect(() => {
        setIsPaused(site.paused);
        setIsRunning(site.running);
        setCanGenerate(!site.running && !site.paused);
    }, [site]);

    const startExport = () => {
        setCanGenerate(false);
        setIsPaused(false);

        apiFetch({
            path: '/simplystatic/v1/start-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
                'type': 'export',
                'is_network_admin': options.is_network
            }
        }).then(resp => {
            var json = JSON.parse(resp);
            if (json.status === 500 ) {
                setCanGenerate(true);
                return;
            }
            setIsRunning(true);
        });
    }

    const cancelExport = () => {
        apiFetch({
            path: '/simplystatic/v1/cancel-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
                'is_network_admin': options.is_network
            }
        }).then(resp => {
            setIsPaused(false)
            setIsRunning(false);
            setCanGenerate(true);
        });
    }

    const pauseExport = () => {
        apiFetch({
            path: '/simplystatic/v1/pause-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
                'is_network_admin': options.is_network
            }
        }).then(resp => {
            setIsRunning(false);
            setIsPaused(true);
        });
    }

    const resumeExport = () => {
        apiFetch({
            path: '/simplystatic/v1/resume-export',
            method: 'POST',
            data: {
                'blog_id': blogId,
                'is_network_admin': options.is_network
            }
        }).then(resp => {
            setIsPaused(false);
            setIsRunning(true);
        });
    }

    const canRunFromNetwork = () => {
        return options.plan === 'pro';
    }

    return (
        <tr>
            <td>
                {site.name}<br/>
                <a href={site.url}>{site.url}</a>
            </td>
            <td>
                { site.status }
            </td>
            <td className={'generate-container'}>
                <Buttons
                    site={site}
                    canGenerate={canGenerate}
                    startExport={canRunFromNetwork() ? startExport : null}
                    isPaused={isPaused}
                    isRunning={isRunning}
                    cancelExport={canRunFromNetwork() ? cancelExport : null}
                    pauseExport={canRunFromNetwork() ? pauseExport : null}
                    resumeExport={canRunFromNetwork() ? resumeExport : null}
                >
                    { ! canRunFromNetwork() &&
                        <Button
                            label={'test'}
                            showTooltip={true}
                            className={'generate'}
                            disabled
                        >
                            <Dashicon icon="update"/>
                            { __('Generate', 'simply-static') }
                        </Button>
                    }

                    <Button
                        onClick={() => window.location = site.settings_url}>
                        <Dashicon icon="admin-generic"/>
                    </Button>

                    { ! canRunFromNetwork() &&
                        <Button
                            target={'_blank'}
                            variant={'link'}
                            href={"https://simplystatic.com/pricing/"}
                        >
                            { __('Upgrade to manage from here', 'simply-static') }
                        </Button>
                    }

                </Buttons>

            </td>
        </tr>
    )
}

export default Site;