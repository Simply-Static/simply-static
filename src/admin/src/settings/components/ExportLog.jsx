import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
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
            sortable: false,
            maxWidth: '100px'
        },
        {
            name: 'URL',
            selector: row => <a target={'_blank'} href={row.url}>{row.url}</a>,
            sortable: false,

        },
        {
            name: 'Notes',
            wrap: true,
            selector: row => <span dangerouslySetInnerHTML={{__html: row.notes}}></span>,
        },
    ];

    const handlePageChange = page => {
        getExportLog(page);
    };

    const handlePerRowsChange = (newPerPage, page) => {
        setPerPageExportLog(newPerPage);
        getExportLog(page, true);
    };

    function getExportLog(page, force = false) {
        page = page ?? 1;

        if (page !== exportPage || force) {
            setLoadingExportLog(true);
        }

        apiFetch({
            path: `/simplystatic/v1/export-log?page=${page}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
            method: 'GET',
        }).then(resp => {
            var json = JSON.parse(resp);
            if (page !== exportPage || force) {
                setExportLog(json.data);
                setLoadingExportLog(false);
            } else {
                exportLog.total_static_pages = json.data.total_static_pages;
                setExportLog(exportLog);
            }
            setExportPage(page);
        });
    }

    useInterval(() => {
        getExportLog();
    }, isRunning ? 5000 : null);

    useEffect(() => {
        getExportLog(1, true);
    }, [isRunning]);

    return (
        <div className={"log-table-container"}>
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
        </div>
    )
}

export default ExportLog;