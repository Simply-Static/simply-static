import {
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Button,
    Notice,
    Animate,
    ClipboardButton,
    // Note: We are not using TextareaControl here to keep the field read-only without extra handlers.
} from "@wordpress/components";
import {useState, useContext} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;

function Utilities() {

    const {
        settings,
        importSettings,
        saveSettings,
        resetSettings,
        migrateSettings,
        resetDatabase,
        resetBackgroundQueue
    } = useContext(SettingsContext);
    const [isExport, setIsExport] = useState(false);
    const [isImport, setIsImport] = useState(false);
    const [isReset, setIsReset] = useState(false);
    const [isResetDatabase, setIsResetDatabase] = useState(false);
    const [isMigrate, setIsMigrate] = useState(false);
    const [hasCopied, setHasCopied] = useState(false);
    const [importData, setImportData] = useState(false);
    const [exportJson, setExportJson] = useState('');
    const [exportLoading, setExportLoading] = useState(false);
    const [exportError, setExportError] = useState('');
    const [isResetBackgroundQueue, setIsResetBackgroundQueue] = useState(false);

    const setImportDataValue = event => {
        setImportData(JSON.parse(event.target.value));
    };

    const runImportSettings = () => {
        importSettings(importData);
        setIsImport(true);

        setTimeout(function () {
            setIsImport(false);
        }, 2000);
    }

    const runResetSettings = () => {
        resetSettings();
        setIsReset(true);

        setTimeout(function () {
            setIsReset(false);
        }, 2000);
    }

    const runResetDatabase = () => {
        resetDatabase();
        setIsResetDatabase(true);

        setTimeout(function () {
            setIsResetDatabase(false);
        }, 2000);
    }


    const runMigrateSettings = () => {
        migrateSettings();
        saveSettings();
        setIsMigrate(true);

        setTimeout(function () {
            setIsMigrate(false);
            location.reload();
        }, 2000);
    }

    const startExport = () => {
        setIsExport(true);
        setExportLoading(true);
        setExportError('');
        // Fetch sanitized settings intended for export (excludes site-specific keys)
        wp.apiFetch({ path: '/simplystatic/v1/settings/export', method: 'GET' })
            .then((resp) => {
                try {
                    // Endpoint returns JSON-encoded string (php json_encode). If resp is string, try parse; else use as object
                    let obj = resp;
                    if (typeof resp === 'string') {
                        obj = JSON.parse(resp);
                    }
                    const pretty = JSON.stringify(obj || {}, null, 2);
                    setExportJson(pretty);
                } catch (e) {
                    setExportError(__('Failed to prepare export JSON.', 'simply-static'));
                    setExportJson('');
                }
            })
            .catch(() => {
                setExportError(__('Failed to fetch export data.', 'simply-static'));
                setExportJson('');
            })
            .finally(() => setExportLoading(false));
    };

    return (
        <div className={"inner-settings"}>
            <Card>
                <CardHeader>
                    <b>{__('Export', 'simply-static')}<HelperVideo
                        title={__('Export & Import settings', 'simply-static')}
                        videoUrl={'https://youtu.be/fmM123Y-gwg'}/></b>
                </CardHeader>
                <CardBody>
                    {!isExport ?
                        <p>
                            <Button onClick={startExport}
                                    variant="primary">{__('Export Settings', 'simply-static')}</Button>
                        </p>
                        :
                        <>
                            <>
                                {exportLoading && (
                                    <p>{__('Preparing export...', 'simply-static')}</p>
                                )}
                                {exportError && (
                                    <Notice status="error" isDismissible={true}>
                                        <p>{exportError}</p>
                                    </Notice>
                                )}
                                {!exportLoading && !exportError && (
                                    <>
                                        <div style={{marginBottom: '12px'}}>
                                            <textarea
                                                className="ss-export-textarea"
                                                value={exportJson}
                                                readOnly
                                                style={{
                                                    width: '100%',
                                                    height: '300px',
                                                    fontFamily: 'Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
                                                    fontSize: '12px',
                                                    whiteSpace: 'pre',
                                                    overflow: 'auto',
                                                    boxSizing: 'border-box'
                                                }}
                                            />
                                        </div>
                                        <p>
                                            <ClipboardButton
                                                variant="secondary"
                                                text={exportJson}
                                                onCopy={() => setHasCopied(true)}
                                                onFinishCopy={() => setHasCopied(false)}
                                            >
                                                {hasCopied ? __('Copied!', 'simply-static') : __('Copy export data', 'simply-static')}
                                            </ClipboardButton>
                                        </p>
                                    </>
                                )}
                            </>
                        </>
                    }
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Import', 'simply-static')}<HelperVideo
                        title={__('Export & Import settings', 'simply-static')}
                        videoUrl={'https://youtu.be/fmM123Y-gwg'}/></b>
                </CardHeader>
                <CardBody>
                    <p>
                        {__('Paste in the JSON string you got from your export to import all settings for the plugin.', 'simply-static')}
                    </p>
                    <textarea rows="8" name="import-data" onChange={setImportDataValue}></textarea>
                    <p>
                        <Button onClick={runImportSettings}
                                variant="primary">{__('Import Settings', 'simply-static')}</Button>
                    </p>
                    {isImport ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Settings imported successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Reset', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('By clicking the "Reset Plugin Settings", you will reset all plugin settings. This can be useful if you want to import a new set of settings or you want a fresh start.', 'simply-static')}</p>
                    <p>{__('If you click the "Reset Database Table" button instead, you will keep all your settings, and we will only recreate our DB table.', 'simply-static')}</p>
                    <p>{__('If the background process is stuck and your debug log shows "There is already an export running", use the "Reset Background Queue" button to clear the queue and locks.', 'simply-static')}</p>
                    <p>
                        <Button onClick={runResetSettings}
                                variant="primary">{__('Reset Plugin Settings', 'simply-static')}</Button>
                        <Button onClick={runResetDatabase} className={"reset-db-btn"}
                                variant="primary">{__('Reset Database Table', 'simply-static')}</Button>
                        <Button onClick={() => { resetBackgroundQueue(); setIsResetBackgroundQueue(true); setTimeout(() => setIsResetBackgroundQueue(false), 2000); }} className={"reset-bg-btn"}
                                variant="primary">{__('Reset Background Queue', 'simply-static')}</Button>
                    </p>
                    {isReset ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Settings resetted successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                    {isResetDatabase ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Database table resetted successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                    {isResetBackgroundQueue ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Background queue reset successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Migrate Settings', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('Migrate all of your settings to Simply Static 3.0', 'simply-static')}</p>
                    <p>
                        <Button onClick={runMigrateSettings}
                                variant="primary">{__('Migrate settings', 'simply-static')}</Button>
                    </p>
                    {isMigrate ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Settings migration successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                </CardBody>
            </Card>
        </div>
    )
}

export default Utilities;