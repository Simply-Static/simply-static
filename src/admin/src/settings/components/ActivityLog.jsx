import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import {Dashicon, Notice} from "@wordpress/components";
import Terminal, {ColorMode, TerminalOutput} from "react-terminal-ui";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;
function ActivityLog() {
    const {isRunning, isResumed, isPaused, blogId, snapshotRollback, isRollbackRunning} = useContext(SettingsContext);
    const rollbackMessage = snapshotRollback && snapshotRollback.message ? snapshotRollback.message : __('Rollback in progress. Simply Static export actions are locked until the rollback has finished.', 'simply-static');
    const rollbackProgress = snapshotRollback && snapshotRollback.progress ? snapshotRollback.progress : {};
    const rollbackProgressText = rollbackProgress.label
        ? `${rollbackProgress.label}${rollbackProgress.total ? ` (${rollbackProgress.percent || 0}%, ${rollbackProgress.completed || 0}/${rollbackProgress.total})` : ''}`
        : rollbackMessage;
    const [terminalLineData, setTerminalLineData] = useState([
        <TerminalOutput key="waiting">Waiting for new push..</TerminalOutput>
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
                var error = message.includes('pause') || message.includes('cancel');
                var success = message.includes('resume');

                terminal.push(
                    <TerminalOutput key={message}>[{date}] <span className={`${error ? 'is-error' : '' } ${success ? 'is-success' : '' }`} dangerouslySetInnerHTML={{__html: text}}></span></TerminalOutput>
                );
            }

            setTerminalLineData( terminal );
        } );
    }

    useInterval(() => {
        refreshActivityLog();
    }, isRunning ? 2500 : null);

    useEffect(() => {
        if (isRunning && !isResumed) {
            setTerminalLineData([<TerminalOutput key="waiting">Waiting for new push..</TerminalOutput>]);
        }
        if (isRunning && isResumed) {
            setTerminalLineData([<TerminalOutput key="resuming">Resuming the push..</TerminalOutput>]);
        }

        refreshActivityLog();
    }, [isRunning]);

    return (
        <>
            {isRollbackRunning &&
                <Notice status="warning" isDismissible={false} className={"rollback-lock-notice"}>
                    <p>
                        <Dashicon icon="update"/>
                        <strong>{__('Rollback in progress', 'simply-static')}</strong>
                        <span>{rollbackProgressText}</span>
                    </p>
                </Notice>
            }
            <Terminal name={__('Activity Log', 'simply-static')} height="250px" colorMode={ColorMode.Dark}>
                {terminalLineData}
            </Terminal>
        </>
    )
}

export default ActivityLog;
