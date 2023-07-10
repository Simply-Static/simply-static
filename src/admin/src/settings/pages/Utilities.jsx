import {
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Button,
    Notice,
    Animate,
    ClipboardButton
} from "@wordpress/components";
import {useState, useContext} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function Utilities() {

    const {settings, importSettings, saveSettings, resetSettings, migrateSettings} = useContext(SettingsContext);
    const [isExport, setIsExport] = useState(false);
    const [isImport, setIsImport] = useState(false);
    const [isReset, setIsReset] = useState(false);
    const [isMigrate, setIsMigrate] = useState(false);
    const [hasCopied, setHasCopied] = useState(false);
    const [importData, setImportData] = useState(false);

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


    const runMigrateSettings = () => {
        migrateSettings();
        saveSettings();
        setIsMigrate(true);

        setTimeout(function () {
            setIsMigrate(false);
        }, 2000);
    }

    return (
        <div className={"inner-settings"}>
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
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Export', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    {!isExport ?
                        <p>
                            <Button onClick={setIsExport}
                                    variant="primary">{__('Export Settings', 'simply-static')}</Button>
                        </p>
                        :
                        <>
                            <p>
                                <code>{JSON.stringify(settings)}</code>
                            </p>
                            <p>
                                <ClipboardButton
                                    variant="secondary"
                                    text={JSON.stringify(settings)}
                                    onCopy={() => setHasCopied(true)}
                                    onFinishCopy={() => setHasCopied(false)}
                                >
                                    {hasCopied ? __('Copied!', 'simply-static') : __('Copy export data', 'simply-static')}
                                </ClipboardButton>
                            </p>
                        </>
                    }
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Import', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>
                        {__('Paste in the JSON string you got from your export to import all settings for the plugin.', 'simply-static')}
                    </p>
                    <p>
                        <textarea rows="8" cols="60" name="import-data" onChange={setImportDataValue}></textarea>
                    </p>
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
                    <b>{__('Reset Plugin Data', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('By clicking the following button, you will reset all plugin settings. This can be useful if you want to import a new set of settings or you want a fresh start.', 'simply-static')}</p>
                    <p>
                        <Button onClick={runResetSettings}
                                variant="secondary">{__('Reset Plugin Settings', 'simply-static')}</Button>
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
                </CardBody>
            </Card>
        </div>
    )
}

export default Utilities;