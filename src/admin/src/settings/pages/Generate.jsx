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

const {__} = wp.i18n;

function Generate() {
    const {settings} = useContext(SettingsContext);
    const [isRunning, setIsRunning] = useState(false);
    const [logDeleted, setLogDeleted] = useState(false);

    const [terminalLineData, setTerminalLineData] = useState([
        <TerminalOutput>Setting up..</TerminalOutput>
    ]);


    const columns = [
        {
            name: 'Code',
            selector: row => row.code,
            sortable: true,
        },
        {
            name: 'URL',
            selector: row => row.url,
            sortable: true,
        },
        {
            name: 'Notes',
            selector: row => row.notes,
        },
    ];

    const data = [
        {
            id: 1,
            code: '200',
            url: 'https://simply-static.local/sitemap.xml',
            notes: 'Sitemap URL',
        },
        {
            id: 2,
            code: '200',
            url: 'https://simply-static.local/wp-content/uploads/simply-static/configs/forms.json',
            notes: 'Config File',
        },
        {
            id: 3,
            code: '200',
            url: 'https://simply-static.local/about/',
            notes: 'Found on /',
        },
        {
            id: 4,
            code: '200',
            url: 'https://simply-static.local/sitemap.xml',
            notes: 'Sitemap URL',
        },
        {
            id: 5,
            code: '200',
            url: 'https://simply-static.local/wp-content/uploads/simply-static/configs/forms.json',
            notes: 'Config File',
        },
        {
            id: 6,
            code: '200',
            url: 'https://simply-static.local/about/',
            notes: 'Found on /',
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


    useEffect(() => {

    }, [settings]);

    return (<div className={"inner-settings settings-wide"}>
        <Terminal name={__('Activity Log', 'simply-static')} height="250px" colorMode={ColorMode.Dark}>
            {terminalLineData}
        </Terminal>
        <Spacer margin={5}/>
        <Flex>
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
                    data={data}
                    pagination
                />
            </CardBody>
        </Card>
    </div>)
}

export default Generate;