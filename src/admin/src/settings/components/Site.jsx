import apiFetch from "@wordpress/api-fetch";
import Buttons from "./GenerateButtons";
import {useState} from "@wordpress/element";
import {useEffect} from "react";
import {Button, Dashicon, Notice} from "@wordpress/components";
import {getExportStartErrorMessage} from "../utils/export";

const {__} = wp.i18n;

function Site(props) {
    const {site, setAnyRunning} = props;
    const blogId = site.id;
    const [canGenerate, setCanGenerate] = useState(!site.running && !site.paused);
    const [isRunning, setIsRunning] = useState(site.running);
    const [isPaused, setIsPaused] = useState(site.paused);
    const [exportError, setExportError] = useState('');

    useEffect(() => {
        if (isRunning) {
            setAnyRunning(true);
        }
    }, [isRunning]);

    useEffect(() => {
        setIsPaused(site.paused);
        setIsRunning(site.running);
        setCanGenerate(!site.running && !site.paused);
    }, [site]);

    const startExport = () => {
        setExportError('');
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
            const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (json && Number(json.status) >= 400) {
                throw json;
            }
            setIsRunning(true);
        }).catch(error => {
            setExportError(getExportStartErrorMessage(
                error,
                __('The export could not be started. Please try again.', 'simply-static'),
                __('Rollback in progress. Please wait before starting an export.', 'simply-static')
            ));
            setIsRunning(false);
            setCanGenerate(true);
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
                {site.status}
            </td>
            <td className={'generate-container network-generate-container'}>
                {exportError && <Notice status="error" isDismissible={false}>
                    <p>{exportError}</p>
                </Notice>}
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
                    {!canRunFromNetwork() &&
                        <Button
                            label={'Generate'}
                            aria-label={__('Generate', 'simply-static')}
                            className={'generate'}
                            disabled
                        >
                            <Dashicon icon="update"/>
                            {__('Generate', 'simply-static')}
                        </Button>
                    }
                    <Button
                        className={"sub-site-settings"}
                        onClick={() => window.location = site.settings_url}>
                        <Dashicon icon="admin-generic"/>
                    </Button>
                    {!canRunFromNetwork() &&
                        <Button
                            target={'_blank'}
                            variant={'link'}
                            href={"https://simplystatic.com/pricing/"}
                        >
                            {__('Upgrade to manage from network.', 'simply-static')}
                        </Button>
                    }
                </Buttons>

            </td>
        </tr>
    )
}

export default Site;