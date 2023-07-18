import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import Terminal, {ColorMode, TerminalOutput} from "react-terminal-ui";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;
function ActivityLog() {
    const {isRunning, blogId} = useContext(SettingsContext);
    const [terminalLineData, setTerminalLineData] = useState([
        <TerminalOutput>Waiting for new export..</TerminalOutput>
    ]);

    function refreshActivityLog() {
        apiFetch({
            path: '/simplystatic/v1/activity-log?blog_id=' + blogId + '&is_network_admin=' + options.is_network,
            method: 'GET',
        }).then(resp => {
            var json = JSON.parse( resp );
            var terminal = [];
            for( var message in json.data ) {
                var date = json.data[message].datetime;
                var text = json.data[message].message;

                terminal.push(
                    <TerminalOutput>[{date}] <span dangerouslySetInnerHTML={{__html: text}}></span></TerminalOutput>
                );
            }

            setTerminalLineData( terminal );
        } );
    }

    useInterval(() => {
        refreshActivityLog();
    }, isRunning ? 5000 : null);

    useEffect(() => {
        if (isRunning) {
            setTerminalLineData([<TerminalOutput>Waiting for new export..</TerminalOutput>]);
        }
        refreshActivityLog();
    }, [isRunning]);

    return (<Terminal name={__('Activity Log', 'simply-static')} height="250px" colorMode={ColorMode.Dark}>
        {terminalLineData}
    </Terminal>)
}

export default ActivityLog;