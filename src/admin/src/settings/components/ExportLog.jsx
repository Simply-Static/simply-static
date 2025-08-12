import {useContext, useEffect, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import useInterval from "../../hooks/useInterval";
import DataTable from "react-data-table-component";
import {Flex, FlexItem, Spinner} from "@wordpress/components";

function ExportLog() {
    const {isRunning, blogId} = useContext(SettingsContext);
    const [exportLog, setExportLog] = useState([]);
    const [loadingExportLog, setLoadingExportLog] = useState(false);
    const [perPageExportLog, setPerPageExportLog] = useState(25);
    const [exportPage, setExportPage] = useState(0);
    const [filterText, setFilterText] = useState('');
    const [allData, setAllData] = useState([]);
    const [loadingAllData, setLoadingAllData] = useState(false);
    const [totalPages, setTotalPages] = useState(0);

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
            sortFunction: (rowA, rowB) => rowA.url.localeCompare(rowB.url)
        },
        {
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
        },
    ];

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
        if (searchTerm && allData.length === 0) {
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
    }, isRunning ? 5000 : null);

    useEffect(() => {
        getExportLog(1, true);
    }, [isRunning]);

    // Filter data based on search term
    const dataToFilter = (filterText && allData.length > 0) ? allData : (exportLog.static_pages || []);
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
