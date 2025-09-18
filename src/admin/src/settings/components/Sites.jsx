import {Animate, Button, Notice} from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import {useState} from "@wordpress/element";
import {TerminalOutput} from "react-terminal-ui";
import useInterval from "../../hooks/useInterval";
import {useEffect} from "react";
import Site from "./Site";
const {__} = wp.i18n;

function Sites (props) {
    const [sites, setSites] = useState([]);
    const [anyRunning, setAnyRunning] = useState(false);

    function refreshSites() {
        apiFetch({
            path: '/simplystatic/v1/sites',
            method: 'GET',
        }).then(resp => {
            let sitesObjects = [];
            let haveRunningSite= false;
            resp.data.forEach(function (site) {
                console.log(site);

                sitesObjects.push(site);

                if ( site.running ) {
                    haveRunningSite = true;
                }
            });

            setSites(sitesObjects);

            setAnyRunning(haveRunningSite);
        } );
    }

    useInterval(() => {
        refreshSites();
    }, anyRunning ? 2500 : 300000); // Any running, check every 2-3secs. Not running, check every 5 mins.

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