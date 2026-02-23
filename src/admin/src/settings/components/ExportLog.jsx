import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";
import DataTable from "react-data-table-component";
import {Flex, FlexItem, Spinner} from "@wordpress/components";

function ExportLog() {
    const {isRunning, blogId, isPro, settings} = useContext(SettingsContext);
    const [exportLog, setExportLog] = useState([]);
    const [loadingExportLog, setLoadingExportLog] = useState(false);
    const [perPageExportLog, setPerPageExportLog] = useState(25);
    const [exportPage, setExportPage] = useState(0);
    const [filterText, setFilterText] = useState('');
    const [exportType, setExportType] = useState('export');
    const [exportTypeId, setExportTypeId] = useState(null);

    // Determine export type based on available information
    useEffect(() => {
        // If delivery method is 'zip', always use 'export' type
        if (settings && settings.delivery_method === 'zip') {
            setExportType('export');
            return;
        }

        // Use the new export-type endpoint to get the export type information
        apiFetch({
            path: '/simplystatic/v1/export-type',
            method: 'GET',
        })
        .then(response => {
            const json = JSON.parse(response);
            if (json.status === 200 && json.data) {
                // If delivery method is 'zip', override with 'export' type
                if (settings && settings.delivery_method === 'zip') {
                    setExportType('export');
                } else {
                    setExportType(json.data.export_type);
                    setExportTypeId(json.data.export_type_id);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching export type:', error);
            // Fallback to using options.last_export_end
            if (settings && settings.delivery_method === 'zip') {
                setExportType('export');
            } else if (options.last_export_end) {
                setExportType('Update');
            } else {
                setExportType('export');
            }
        });
    }, [settings]);

    // Define base columns
    const urlColumnWidth = isPro() ? '40%' : '60%';
    const baseColumns = [
        {
            name: 'Code',
            selector: row => row.code,
            sortable: true,
            width: '12%'
        },
        {
            name: 'URL',
            selector: row => {
                // Display a safe path without throwing on invalid URLs
                const raw = row && typeof row.url === 'string' ? row.url : '';
                if (!raw) {
                    return <span>-</span>;
                }
                // Default to the raw value
                let pathOnly = raw;
                // If it's a relative path, just show it as-is
                if (raw.startsWith('/')) {
                    pathOnly = raw;
                } else {
                    // Try to parse absolute URLs safely
                    try {
                        const parsed = new URL(raw);
                        pathOnly = parsed.pathname + parsed.search + parsed.hash;
                    } catch (e) {
                        // Parsing failed (e.g., protocol-relative or malformed). Keep raw.
                    }
                }
                return <a target={'_blank'} href={raw}>{pathOnly}</a>;
            },
            sortable: true,
            sortFunction: (rowA, rowB) => rowA.url.localeCompare(rowB.url),
            width: urlColumnWidth,
            wrap: true
        }
    ];

    // Define Export-Type column (only included if Pro is activated)
    const exportTypeColumn = {
        name: 'Export-Type',
        selector: row => {
            // Display the export type and ID if it's a Build or Single export
            if (exportType === 'Build' || exportType === 'Single') {
                return `${exportType} (ID: ${exportTypeId})`;
            }
            return exportType;
        },
        sortable: true,
        width: '20%'
    };

    // Define Notes column
    const notesColumn = {
        name: 'Notes',
        wrap: true,
        selector: row => <span dangerouslySetInnerHTML={{__html: row.notes}}></span>,
        sortable: true,
        sortFunction: (rowA, rowB) => {
            // Remove HTML tags for sorting
            const notesA = rowA.notes.replace(/<[^>]*>/g, '');
            const notesB = rowB.notes.replace(/<[^>]*>/g, '');
            return notesA.localeCompare(notesB);
        },
        width: '28%'
    };

    // Combine columns based on whether Pro is activated
    const columns = isPro() 
        ? [...baseColumns, exportTypeColumn, notesColumn]
        : [...baseColumns, notesColumn];

    const handlePageChange = page => {
        getExportLog(page);
    };

    const handlePerRowsChange = (newPerPage, page) => {
        setPerPageExportLog(newPerPage);
        getExportLog(page, true);
    };

    const handleSearch = (e) => {
        const searchTerm = e.target.value;
        setFilterText(searchTerm);
        getExportLog(1, true, searchTerm);
    };

    function getExportLog(page, force = false, search = filterText) {
        page = page ?? 1;

        if (page !== exportPage || force) {
            setLoadingExportLog(true);
        }

        apiFetch({
            path: `/simplystatic/v1/export-log?page=${page}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}&search=${encodeURIComponent(search)}`,
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

        // Fetch the export type when isRunning changes
        if (isRunning) {
            apiFetch({
                path: '/simplystatic/v1/export-type',
                method: 'GET',
            })
            .then(response => {
                const json = JSON.parse(response);
                if (json.status === 200 && json.data) {
                    setExportType(json.data.export_type);
                    setExportTypeId(json.data.export_type_id);
                }
            })
            .catch(error => {
                console.error('Error fetching export type:', error);
            });
        }
    }, [isRunning]);

    return (
        <div className={"log-table-container"}>
            <Flex>
                <FlexItem>
                    <input
                        id={"export-search"}
                        className={'ss-export-log-search'}
                        type="text"
                        placeholder="Search..."
                        value={filterText}
                        onChange={handleSearch}
                    />
                </FlexItem>
            </Flex>
            <DataTable
                columns={columns}
                data={exportLog.static_pages || []}
                pagination
                paginationServer
                paginationTotalRows={exportLog.total_static_pages}
                paginationPerPage={25}
                paginationRowsPerPageOptions={[25, 50, 100, 200]}
                progressPending={loadingExportLog}
                progressComponent={
                    <div style={{padding: '24px', textAlign: 'center'}}>
                        <Spinner/>
                        <div style={{marginTop: '8px'}}>Loading...</div>
                    </div>
                }
                onChangeRowsPerPage={handlePerRowsChange}
                onChangePage={handlePageChange}
            />
        </div>
    )
}

export default ExportLog;
