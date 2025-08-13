import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";
import DataTable from "react-data-table-component";
import {Flex, FlexItem, Spinner} from "@wordpress/components";

function ExportLog() {
    const {isRunning, blogId, isPro} = useContext(SettingsContext);
    const [exportLog, setExportLog] = useState([]);
    const [loadingExportLog, setLoadingExportLog] = useState(false);
    const [perPageExportLog, setPerPageExportLog] = useState(25);
    const [exportPage, setExportPage] = useState(0);
    const [filterText, setFilterText] = useState('');
    const [allData, setAllData] = useState([]);
    const [loadingAllData, setLoadingAllData] = useState(false);
    const [totalPages, setTotalPages] = useState(0);
    const [exportType, setExportType] = useState('Export');
    const [exportTypeId, setExportTypeId] = useState(null);

    // Determine export type based on available information
    useEffect(() => {
        // Use the new export-type endpoint to get the export type information
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
            // Fallback to using options.last_export_end
            if (options.last_export_end) {
                setExportType('Update');
            } else {
                setExportType('Export');
            }
        });
    }, []);

    // Define base columns
    const baseColumns = [
        {
            name: 'Code',
            selector: row => row.code,
            sortable: true,
            maxWidth: '100px'
        },
        {
            name: 'URL',
            selector: row => {
                // Strip domain from URL for display
                const url = new URL(row.url);
                const pathOnly = url.pathname + url.search + url.hash;
                return <a target={'_blank'} href={row.url}>{pathOnly}</a>;
            },
            sortable: true,
            sortFunction: (rowA, rowB) => rowA.url.localeCompare(rowB.url)
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
        maxWidth: '200px'
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
        }
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

    const handleSearch = async (e) => {
        const searchTerm = e.target.value;
        setFilterText(searchTerm);

        // If search term is not empty and we don't have all data yet, fetch all data
        // But only if we're not running a Build or Single export
        if (searchTerm && allData.length === 0 && exportType !== 'Build' && exportType !== 'Single') {
            await fetchAllData();
        }
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

                // Calculate total pages
                const total = json.data.total_static_pages || 0;
                const calculatedTotalPages = Math.ceil(total / perPageExportLog);
                setTotalPages(calculatedTotalPages);
            } else {
                exportLog.total_static_pages = json.data.total_static_pages;
                setExportLog(exportLog);
            }
            setExportPage(page);
        });
    }

    // Function to fetch all data for search
    async function fetchAllData() {
        setLoadingAllData(true);

        try {
            // First, get the first page to determine total pages
            const firstPageResponse = await apiFetch({
                path: `/simplystatic/v1/export-log?page=1&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
                method: 'GET',
            });

            const firstPageJson = JSON.parse(firstPageResponse);
            const totalItems = firstPageJson.data.total_static_pages || 0;
            const calculatedTotalPages = Math.ceil(totalItems / perPageExportLog);

            // Create an array of promises for all pages
            const pagePromises = [];
            for (let i = 1; i <= calculatedTotalPages; i++) {
                pagePromises.push(
                    apiFetch({
                        path: `/simplystatic/v1/export-log?page=${i}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
                        method: 'GET',
                    })
                );
            }

            // Execute all promises
            const responses = await Promise.all(pagePromises);

            // Combine all pages of data
            let allPages = [];
            responses.forEach(response => {
                const json = JSON.parse(response);
                if (json.data && json.data.static_pages) {
                    allPages = [...allPages, ...json.data.static_pages];
                }
            });

            // Update state with the fetched data
            setAllData(allPages);

            // Log for debugging
            console.log(`Fetched ${allPages.length} total items from ${calculatedTotalPages} pages`);

            return allPages;
        } catch (error) {
            console.error('Error fetching all data:', error);
            return [];
        } finally {
            setLoadingAllData(false);
        }
    }

    useInterval(() => {
        getExportLog();

        // If we have a search term and already have all data, refresh the all data
        if (filterText && allData.length > 0) {
            fetchAllData();
        }
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

    // Filter data based on search term
    // When running a Build or Single export, only search in the current export log data
    const dataToFilter = (filterText && allData.length > 0 && exportType !== 'Build' && exportType !== 'Single') 
        ? allData 
        : (exportLog.static_pages || []);
    const filteredData = dataToFilter.filter(
        item => {
            if (!filterText) return true;
            const searchTerm = filterText.toLowerCase();
            return (
                (item.code && item.code.toString().toLowerCase().includes(searchTerm)) ||
                (item.url && item.url.toLowerCase().includes(searchTerm)) ||
                (item.notes && item.notes.toLowerCase().includes(searchTerm))
            );
        }
    );

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
                data={filterText ? filteredData : (exportLog.static_pages || [])}
                pagination
                paginationServer={!filterText}
                paginationTotalRows={filterText ? filteredData.length : exportLog.total_static_pages}
                paginationPerPage={25}
                paginationRowsPerPageOptions={[25, 50, 100, 200]}
                progressPending={loadingExportLog || (loadingAllData && filterText)}
                progressComponent={
                    <div style={{padding: '24px', textAlign: 'center'}}>
                        <Spinner/>
                        <div style={{marginTop: '8px'}}>
                            {loadingAllData && filterText ? 'Loading all data for search...' : 'Loading...'}
                        </div>
                    </div>
                }
                onChangeRowsPerPage={handlePerRowsChange}
                onChangePage={filterText ? undefined : handlePageChange}
            />
        </div>
    )
}

export default ExportLog;
