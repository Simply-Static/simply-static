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
                'type': 'export'
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
            }
        }).then(resp => {
            setIsPaused(false);
            setIsRunning(true);
        });
    }

    return (
        <tr>
            <td>
                {site.name}<br/>
                <a href={site.url}>{site.url}</a>
            </td>
            <td>
                {isRunning ? 'Running' : ( isPaused ? 'Paused' : 'Idle' ) }
            </td>
            <td className={'generate-container'}>
                <Buttons
                    site={site}
                    canGenerate={canGenerate}
                    startExport={startExport}
                    isPaused={isPaused}
                    isRunning={isRunning}
                    cancelExport={cancelExport}
                    pauseExport={pauseExport}
                    resumeExport={resumeExport}
                >
                    <Button

                        onClick={() => window.location = site.settings_url}>
                        <Dashicon icon="admin-generic"/>
                    </Button>

                </Buttons>

            </td>
        </tr>
    )
}

export default Site;