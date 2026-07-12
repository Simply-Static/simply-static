import apiFetch from "@wordpress/api-fetch";
import {useRef, useState} from "@wordpress/element";
import useInterval from "../../hooks/useInterval";
import {useEffect} from "react";
import Site from "./Site";
const {__} = wp.i18n;

function Sites (props) {
    const [sites, setSites] = useState([]);
	const [anyRunning, setAnyRunning] = useState(false);
	const [siteToTriggerCron, setSiteToTriggerCron] = useState(0);
	const refreshInFlight = useRef(false);

    const triggerCron = (blogId) => {
        apiFetch({
            path: '/simplystatic/v1/trigger-cron',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        }).then(resp => {
            var json = JSON.parse(resp);
            if (json.status === 200) {
                // Show success message or update UI
                console.log('CRON triggered successfully for site ' + blogId);
            } else {
                console.error('Failed to trigger CRON:', json.message);
            }
            let id = getNextSiteId(blogId);
            setSiteToTriggerCron(id);

        }).catch(error => {
            console.error('Error triggering CRON:', error);
        });
    }

    function getNextSiteId(siteId) {
        let ids = getSiteIds();

        if ( ids.length === 0 ) {
            return 0;
        }

        let index = ids.indexOf(siteId);
        if ( index === -1 ) {
            return ids[0];
        }

        index++;
        let id = ids[index] || ids[0];

        return id;
    }

    function getSiteIds() {
        let siteIds = [];
        sites.forEach(function (site) {
            siteIds.push(site.id);
        });

        return siteIds;
    }

	function refreshSites() {
		if (refreshInFlight.current) {
			return;
		}
		refreshInFlight.current = true;
		apiFetch({
            path: '/simplystatic/v1/sites',
            method: 'GET',
        }).then(resp => {
            let sitesObjects = [];
            let haveRunningSite= false;
            resp.data.forEach(function (site) {

                sitesObjects.push(site);

                if ( site.running ) {
                    haveRunningSite = true;
                }
            });

            setSites(sitesObjects);

			setAnyRunning(haveRunningSite);
		}).catch(() => {
			// Keep the last known site state and allow the next scheduled retry.
		}).finally(() => {
			refreshInFlight.current = false;
		});
	}

	useInterval(() => {
		refreshSites();
	}, anyRunning ? 10000 : 300000); // Keep active exports responsive without repeatedly scanning a large network every few seconds.

    useInterval(() => {

        if ( ! siteToTriggerCron && sites.length > 0 ) {
            setSiteToTriggerCron(sites[0].id);
        }

        if ( ! siteToTriggerCron ) {
            return;
        }

        triggerCron(siteToTriggerCron);

    }, anyRunning ? 150000 : null); // Run Cron every 2.5mins. 1 site per iteration.

    useEffect(() => {
        refreshSites();
    }, []);

    return (
        <>
            <table className={'wp-list-table widefat fixed striped posts simple-static-sites'}>
                <thead>
                    <tr>
                        <th>{__('Name', 'simply-static')}</th>
                        <th>{__('Status', 'simply-static')}</th>
                        <th>{__('Actions', 'simply-static')}</th>
                    </tr>
                </thead>
                <tbody>
                    { sites.map( (site) => { return <Site setAnyRunning={setAnyRunning} site={site} key={site.id} /> } ) }
                </tbody>
            </table>

        </>
    )
}

export default Sites;
