import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import Terminal, {ColorMode, TerminalOutput} from 'react-terminal-ui';
import DataTable from 'react-data-table-component';
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;

function Generate() {
    const {settings} = useContext(SettingsContext);
    const [isRunning, setIsRunning] = useState(false);
    const [exportLog, setExportLog] = useState([]);
    const [logDeleted, setLogDeleted] = useState(false);
    const [loadingExportLog, setLoadingExportLog] = useState(false);
    const [totalExportLogRows, setExportLogTotalRows] = useState(0);
    const [perPageExportLog, setPerPageExportLog] = useState(10);

    const [terminalLineData, setTerminalLineData] = useState([
        <TerminalOutput>Setting up..</TerminalOutput>
    ]);

    const columns = [
        {
            name: 'Code',
            selector: row => row.code,
            sortable: true,
            maxWidth: '100px'
        },
        {
            name: 'URL',
            selector: row => <a target={'_blank'} href={row.url}>{row.url}</a>,
            sortable: true,

        },
        {
            name: 'Notes',
            wrap: true,
            selector: row => <span dangerouslySetInnerHTML={{__html: row.notes}}></span>,
        },
    ];


    const deleteLog = () => {
        apiFetch({
            path: '/simplystatic/v1/delete-log',
            method: 'POST',
        });

        setLogDeleted(true);

        setTimeout(function () {
            setLogDeleted(false);
        }, 2000);
    }

    const handlePageChange = page => {
        getExportLog(page);
    };

    const handlePerRowsChange = (newPerPage, page) => {
        setPerPageExportLog(newPerPage);
        getExportLog( page );
    };

    function getExportLog( page ) {
        page = page ?? 1;
        setLoadingExportLog(true);
        apiFetch({
            path: `/simplystatic/v1/export-log?page=${page}&per_page=${perPageExportLog}`,
            method: 'GET',
        }).then(resp => {
            var json = JSON.parse( resp );
            console.log(json);
            setExportLog( json.data );
            setLoadingExportLog(false);
        } );
    }

    function refreshActivityLog() {
        apiFetch({
            path: '/simplystatic/v1/activity-log',
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
            setIsRunning(json.running);
        } );
    }

    useInterval(() => {
        refreshActivityLog();
        getExportLog();
    }, isRunning ? 2000 : null);

    useEffect(() => {
        refreshActivityLog();
        getExportLog();
    }, []);

    useEffect(() => {

    }, [settings]);

    return (<div className={"inner-settings settings-wide"}>
        <Terminal name={__('Activity Log', 'simply-static')} height="250px" colorMode={ColorMode.Dark}>
            {terminalLineData}
        </Terminal>
        <Spacer margin={5}/>
        <Flex>
            {'pro' === options.plan && options.is_network &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Multisite', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <p>The options for the site selection are going here..</p>
                        </CardBody>
                    </Card>
                </FlexItem>
            }
            {settings.debugging_mode &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Debugging', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <Button variant="primary" href={options.log_file} target={"_blank"}
                                    style={{marginRight: "10px"}}>{__('Download Log', 'simply-static')}</Button>
                            <Button variant="secondary"
                                    onClick={deleteLog}>{__('Clear Log', 'simply-static')}</Button>
                            {logDeleted &&
                                <Animate type="slide-in" options={{origin: 'top'}}>
                                    {() => (
                                        <Notice status="success" isDismissible={false}>
                                            <p>
                                                {__('Log file successfully deleted.', 'simply-static')}
                                            </p>
                                        </Notice>
                                    )}
                                </Animate>
                            }
                        </CardBody>
                    </Card>
                </FlexItem>
            }
        </Flex>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Export Log', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <DataTable
                    columns={columns}
                    data={exportLog.static_pages}
                    pagination
                    paginationServer
                    paginationTotalRows={exportLog.total_static_pages}
                    paginationPerPage={25}
                    paginationRowsPerPageOptions={[25, 50, 100, 200]}
                    progressPending={loadingExportLog}
                    onChangeRowsPerPage={handlePerRowsChange}
                    onChangePage={handlePageChange}
                />
            </CardBody>
        </Card>
    </div>)
}

export default Generate;