import {useContext, useEffect, useMemo, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import {Card, CardBody, CardHeader, SelectControl, Flex, FlexItem, Button, Notice, Animate, __experimentalSpacer as Spacer} from "@wordpress/components";

const {__} = wp.i18n;

function UAMSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [localMap, setLocalMap] = useState({});
    // Use server-injected current settings as an immediate source of truth on first paint
    const initialUam = (typeof options !== 'undefined' && options.current_settings && options.current_settings.ss_uam_access)
        ? options.current_settings.ss_uam_access
        : {};

    const roleOptions = useMemo(() => ([
        { label: __('Administrator', 'simply-static'), value: 'administrator' },
        { label: __('Editor', 'simply-static'), value: 'editor' },
        { label: __('Author', 'simply-static'), value: 'author' },
        { label: __('Contributor', 'simply-static'), value: 'contributor' },
        { label: __('Subscriber', 'simply-static'), value: 'subscriber' },
    ]), []);

    // Group pages by subsection for clearer UI
    const toolsPages = useMemo(() => ([
        { key: 'activity', label: __('Activity Log', 'simply-static'), route: '/' },
        { key: 'diagnostics', label: __('Diagnostics', 'simply-static'), route: '/diagnostics' },
    ]), []);

    const settingsPages = useMemo(() => ([
        { key: 'general', label: __('General', 'simply-static'), route: '/general' },
        { key: 'deployment', label: __('Deploy', 'simply-static'), route: '/deployment' },
        { key: 'forms', label: __('Forms', 'simply-static'), route: '/forms' },
        { key: 'search', label: __('Search', 'simply-static'), route: '/search' },
        { key: 'optimize', label: __('Optimize', 'simply-static'), route: '/optimize' },
        { key: 'workflow', label: __('Workflow', 'simply-static'), route: '/workflow' },
    ]), []);

    const advancedPages = useMemo(() => ([
        { key: 'integrations', label: __('Integrations', 'simply-static'), route: '/integrations' },
        { key: 'utilities', label: __('Utilities', 'simply-static'), route: '/utilities' },
        { key: 'debug', label: __('Debug', 'simply-static'), route: '/debug' },
        { key: 'uam', label: __('UAM', 'simply-static'), route: '/uam' },
    ]), []);

    const menus = useMemo(() => {
        const base = [
            { key: 'menu_generate', label: __('Generate', 'simply-static') },
            { key: 'menu_settings', label: __('Settings', 'simply-static') },
            { key: 'menu_diagnostics', label: __('Diagnostics', 'simply-static') },
            { key: 'menu_form_connections', label: __('Form Connections', 'simply-static') },
        ];
        if (settings?.ss_use_builds) {
            base.push({ key: 'menu_builds', label: __('Builds', 'simply-static') });
        }
        return base;
    }, [settings?.ss_use_builds]);

    useEffect(() => {
        const current = settings?.ss_uam_access || {};
        setLocalMap({ ...current });
    }, [settings]);

    const setRole = (key, value) => {
        const updated = { ...localMap, [key]: value };
        setLocalMap(updated);
        updateSetting('ss_uam_access', updated);
    }

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);
        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    // A simple row with one visible label and the select next to it
    const Row = ({ id, label, value, onChange }) => (
        <Flex align={"center"} gap={3}>
            <FlexItem style={{ minWidth: 260 }}>
                <b id={`${id}-label`}>{label}</b>
            </FlexItem>
            <FlexItem>
                <SelectControl
                    // Use aria-label to keep accessibility without rendering a second visual label
                    aria-label={label}
                    value={value}
                    options={roleOptions}
                    onChange={onChange}
                />
            </FlexItem>
        </Flex>
    );

    // Resolve the value to show with priority:
    // 1) locally edited map (unsaved changes)
    // 2) server-injected initial settings (options.current_settings)
    // 3) hardcoded fallback matching backend defaults
    const resolveRole = (key, fallback) => {
        if (localMap && Object.prototype.hasOwnProperty.call(localMap, key) && localMap[key]) {
            return localMap[key];
        }
        if (initialUam && Object.prototype.hasOwnProperty.call(initialUam, key) && initialUam[key]) {
            return initialUam[key];
        }
        return fallback;
    }

    return (
        <div className={"inner-settings"}>
            <Card>
                <CardHeader>
                    <b>{__('UAM (User Access Management)', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>
                        {__(
                            'Control who can access Simply Static pages, menu entries, and selected features by assigning a minimum WordPress role.',
                            'simply-static'
                        )}
                    </p>
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Tools', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <Flex direction="column" gap={3}>
                        {toolsPages.map(p => {
                            const fallback = p.key === 'activity' ? 'editor' : 'administrator';
                            return (
                                <Row
                                    key={p.key}
                                    id={`uam-${p.key}`}
                                    label={p.label}
                                    value={resolveRole(p.key, fallback)}
                                    onChange={(val) => setRole(p.key, val)}
                                />
                            );
                        })}
                    </Flex>
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Settings', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <Flex direction="column" gap={3}>
                        {settingsPages.map(p => (
                            <Row
                                key={p.key}
                                id={`uam-${p.key}`}
                                label={p.label}
                                value={resolveRole(p.key, 'administrator')}
                                onChange={(val) => setRole(p.key, val)}
                            />
                        ))}
                    </Flex>
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Advanced', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <Flex direction="column" gap={3}>
                        {advancedPages.map(p => (
                            <Row
                                key={p.key}
                                id={`uam-${p.key}`}
                                label={p.label}
                                value={resolveRole(p.key, 'administrator')}
                                onChange={(val) => setRole(p.key, val)}
                            />
                        ))}
                    </Flex>
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Menu', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <Flex direction="column" gap={3}>
                        {menus.map(m => {
                            const fallback = m.key === 'menu_generate' ? 'editor' : 'administrator';
                            return (
                                <Row
                                    key={m.key}
                                    id={`uam-${m.key}`}
                                    label={m.label}
                                    value={resolveRole(m.key, fallback)}
                                    onChange={(val) => setRole(m.key, val)}
                                />
                            );
                        })}
                    </Flex>
                </CardBody>
            </Card>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Features', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <Flex direction="column" gap={3}>
                        <Row
                            id="uam-adminbar"
                            label={__('Admin Bar', 'simply-static')}
                            value={resolveRole('adminbar', 'editor')}
                            onChange={(val) => setRole('adminbar', val)}
                        />
                        <Row
                            id="uam-single-export"
                            label={__('Single Export (Button)', 'simply-static')}
                            value={resolveRole('single_export_button', 'editor')}
                            onChange={(val) => setRole('single_export_button', val)}
                        />
                    </Flex>
                </CardBody>
            </Card>
            {settingsSaved && (
                <>
                    <Animate type="slide-in" options={{origin: 'top'}}>
                        {() => (
                            <Notice status="success" isDismissible={false}>
                                <p>
                                    {__('Settings saved successfully.', 'simply-static')}
                                </p>
                            </Notice>
                        )}
                    </Animate>
                    <Spacer margin={5}/>
                </>
            )}
            <div className={"save-settings"}>
                <Button onClick={setSavingSettings} variant="primary">{__('Save Settings', 'simply-static')}</Button>
            </div>
        </div>
    );
}

export default UAMSettings;
