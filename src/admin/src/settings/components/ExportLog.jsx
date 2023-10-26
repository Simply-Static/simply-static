import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import {Button} from "@wordpress/components";

import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";
import DataTable from "react-data-table-component";

function ExportLog() {
    const {isRunning, blogId} = useContext(SettingsContext);
    const [exportLog, setExportLog] = useState([]);
    const [loadingExportLog, setLoadingExportLog] = useState(false);
    const [perPageExportLog, setPerPageExportLog] = useState(25);
    const [exportPage, setExportPage] = useState(0);

    const columns = [
        {
            name: 'Code',
            selector: row => row.code,
            sortable: !isRunning,
            maxWidth: '100px'
        },
        {
            name: 'URL',
            selector: row => <a target={'_blank'} href={row.url}>{row.url}</a>,
            sortable: !isRunning,

        },
        {
            name: 'Notes',
            wrap: true,
            selector: row => <span dangerouslySetInnerHTML={{__html: row.notes}}></span>,
        },
    ];

    const convertToCSV = (objArray) => {

        var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;
        var str = 'URL,Code,Notes\r\n';

        var data = array.static_pages;

        for (var i = 0; i < data.length; i++) {
            let lineUrl   = data[i]['url'] || '';
            let lineCode  = data[i]['code'] || '';
            let lineNotes = data[i]['notes'] ? data[i]['notes'].replace(/(<([^>]+)>)/gi, "") : '';
            console.log(data[i]['notes'].replace(/(<([^>]+)>)/gi, ""));
            str += lineUrl +',' + lineCode + ',' + lineNotes + '\r\n';
        }

        return str;
    }

    const exportWholeLog = () => {
        apiFetch({
            path: `/simplystatic/v1/export-log?page=0&per_page=0&blog_id=${blogId}&is_network_admin=${options.is_network}`,
            method: 'GET',
        }).then(resp => {
            var json = JSON.parse( resp );
            console.log(json);
            exportCSV( json.data );
        } );
    }

    /**
     * Export CSV
     * @param items
     * @param fileTitle
     */
    const exportCSV = (items) => {
        // Convert Object to JSON
        var jsonObject = JSON.stringify(items);

        var csv = convertToCSV(jsonObject);

        var exportedFilenmae = 'simply-static-export-log.csv';

        var blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        if (navigator.msSaveBlob) { // IE 10+
            navigator.msSaveBlob(blob, exportedFilenmae);
        } else {
            var link = document.createElement("a");
            if (link.download !== undefined) { // feature detection
                // Browsers that support HTML5 download attribute
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", exportedFilenmae);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    }

    const handlePageChange = page => {
        getExportLog(page);
    };

    const handlePerRowsChange = (newPerPage, page) => {
        setPerPageExportLog(newPerPage);
        getExportLog( page, true );
    };

    function getExportLog( page, force = false ) {
        page = page ?? 1;

        if ( page !== exportPage || force ) {
            setLoadingExportLog(true);
        }

        apiFetch({
            path: `/simplystatic/v1/export-log?page=${page}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
            method: 'GET',
        }).then(resp => {
            var json = JSON.parse( resp );
            if ( page !== exportPage || force ) {
                setExportLog( json.data );
                setLoadingExportLog(false);
            } else {
                exportLog.total_static_pages = json.data.total_static_pages;
                setExportLog(exportLog);
            }
            setExportPage(page);
        } );
    }

    useInterval(() => {
        getExportLog();
    }, isRunning ? 5000 : null);

    useEffect(() => {
        getExportLog( 1, true);
    }, [isRunning]);

    const actionsMemo = React.useMemo(() =>
        {
            let showExportButton = !isRunning && Object.keys(exportLog).length > 0;

            return showExportButton && <Button onClick={() => exportWholeLog()}>Export</Button>
        },
        [ isRunning, exportLog ]
    );

    return (
        <DataTable
            actions={actionsMemo}
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
    )
}

export default ExportLog;