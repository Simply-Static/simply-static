import {useState, createContext, useEffect} from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';

const {__} = wp.i18n;

export const SettingsContext = createContext();

function SettingsContextProvider(props) {

    const defaultSettings = {};

    const [settingsSaved, setSettingsSaved] = useState(false);
    const [settings, setSettings] = useState(defaultSettings);
    const [configs, setConfigs] = useState({});

    const getSettings = () => {
        apiFetch({path: '/simplystatic/v1/settings'}).then((options) => {
            setSettings(options);
        });
    }

    const saveSettings = () => {
        apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: settings,
        });
    }

    const resetSettings = () => {
        setSettings(defaultSettings);

        apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: defaultSettings,
        });
    }

    const importSettings = (newSettings) => {
        setSettings(newSettings);

        apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: newSettings,
        });
    }

    const updateSetting = (key, value) => {
        setSettings({...settings, [key]: value});
    };

    const getStatus = () => {
        apiFetch({path: '/simplystatic/v1/system-status'}).then((configs) => {
            setConfigs(configs);
        });
    }

    useEffect(() => {
        getSettings();
        getStatus();
    }, []);

    return (
        <SettingsContext.Provider
            value={{
                settings,
                configs,
                settingsSaved,
                setSettingsSaved,
                updateSetting,
                setSettings,
                saveSettings,
                resetSettings,
                importSettings,
            }}
        >
            {props.children}
        </SettingsContext.Provider>
    );
}

export default SettingsContextProvider;
