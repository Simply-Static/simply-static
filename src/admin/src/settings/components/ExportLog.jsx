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
    const [allData, setAllData] = useState([]);
    const [loadingAllData, setLoadingAllData] = useState(false);
    const [totalPages, setTotalPages] = useState(0);
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

    const handleSearch = async (e) => {
        const searchTerm = e.target.value;
        setFilterText(searchTerm);

        // If search term is not empty and we don't have all data yet, fetch all data
        // But only if we're not running a Build or Single export
        if (searchTerm && allData.length === 0 && exportType !== 'Build' && exportType !== 'Single') {
            await fetchAllData();
            setLastAllDataFetch(Date.now()); // Update the timestamp after fetching
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
            let calculatedTotalPages = Math.ceil(totalItems / perPageExportLog);

            // For very large sites, limit the number of pages we fetch to avoid timeouts
            const MAX_PAGES_TO_FETCH = 20; // This will fetch up to 500 items with default perPage of 25
            if (calculatedTotalPages > MAX_PAGES_TO_FETCH) {
                console.log(`Site has ${calculatedTotalPages} pages of data, limiting to ${MAX_PAGES_TO_FETCH} pages to prevent timeouts`);
                calculatedTotalPages = MAX_PAGES_TO_FETCH;
            }

            // Instead of fetching all pages at once, fetch them in batches
            const BATCH_SIZE = 5; // Process 5 pages at a time
            let allPages = [];

            // Add the first page data we already fetched
            if (firstPageJson.data && firstPageJson.data.static_pages) {
                allPages = [...firstPageJson.data.static_pages];
            }

            // Process remaining pages in batches
            for (let batchStart = 2; batchStart <= calculatedTotalPages; batchStart += BATCH_SIZE) {
                const batchEnd = Math.min(batchStart + BATCH_SIZE - 1, calculatedTotalPages);
                console.log(`Fetching batch of pages ${batchStart} to ${batchEnd}`);

                // Create batch of promises
                const batchPromises = [];
                for (let i = batchStart; i <= batchEnd; i++) {
                    batchPromises.push(
                        apiFetch({
                            path: `/simplystatic/v1/export-log?page=${i}&per_page=${perPageExportLog}&blog_id=${blogId}&is_network_admin=${options.is_network}`,
                            method: 'GET',
                        })
                    );
                }

                // Execute batch of promises
                const batchResponses = await Promise.all(batchPromises);

                // Process batch responses
                batchResponses.forEach(response => {
                    const json = JSON.parse(response);
                    if (json.data && json.data.static_pages) {
                        allPages = [...allPages, ...json.data.static_pages];
                    }
                });
            }

            // Update state with the fetched data
            setAllData(allPages);

            // Log for debugging
            console.log(`Fetched ${allPages.length} total items from ${calculatedTotalPages} pages (out of ${Math.ceil(totalItems / perPageExportLog)} total pages)`);

            return allPages;
        } catch (error) {
            console.error('Error fetching all data:', error);
            return [];
        } finally {
            setLoadingAllData(false);
        }
    }

    // Track the last time we fetched all data
    const [lastAllDataFetch, setLastAllDataFetch] = useState(0);

    useInterval(() => {
        getExportLog();

        // If we have a search term and already have all data, refresh the all data
        // but limit how often we do this to prevent overloading the server
        const currentTime = Date.now();
        const ALL_DATA_REFRESH_INTERVAL = 30000; // 30 seconds between full refreshes

        if (filterText && allData.length > 0 && (currentTime - lastAllDataFetch > ALL_DATA_REFRESH_INTERVAL)) {
            console.log('Refreshing all data for search (30-second interval)');
            fetchAllData();
            setLastAllDataFetch(currentTime);
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
