import {useState, createContext, useEffect} from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;

export const SettingsContext = createContext();

function SettingsContextProvider(props) {
    const [isRunning, setIsRunning] = useState(false);
    const [isDelayed, setIsDelayed] = useState(0);
    const [isPaused, setIsPaused] = useState(false);
    const [isResumed, setIsResumed] = useState(false);
    const [settingsSaved, setSettingsSaved] = useState(false);
    const [settings, setSettings] = useState({});
    const [configs, setConfigs] = useState({});
    const [passedChecks, setPassedChecks] = useState('yes');
    const [blogId, setBlogId] = useState(1);
    const [queuedIntegrations, setQueuedIntegrations] = useState([]);
    const [showMobileNav, setShowMobileNav] = useState(true);

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
        }).then(resp => {
            setQueuedIntegrations([]);
        });
    }

    const resetSettings = () => {
        apiFetch({
            path: '/simplystatic/v1/settings/reset',
            method: 'POST'
        }).then(resp => {
            // Parse the response to get the default settings
            const response = JSON.parse(resp);
            if (response.status === 200 && response.data) {
                // Update the settings state with the default settings from the server
                setSettings(response.data);
            }
        });
    }

    const resetDatabase = () => {
        apiFetch({
            path: '/simplystatic/v1/settings/reset-database',
            method: 'POST',
        });
    }

    const resetBackgroundQueue = () => {
        apiFetch({
            path: '/simplystatic/v1/settings/reset-background-queue',
            method: 'POST',
        });
    }

    const updateFromNetwork = (blogId) => {
        apiFetch({
            path: '/simplystatic/v1/update-from-network',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        });
    }

    const checkIfRunning = () => {
        apiFetch({
            path: '/simplystatic/v1/is-running',
            method: 'GET'
        }).then(resp => {
            var json = JSON.parse(resp);
            setIsRunning(json.running);
            setIsPaused(json.paused);
            if ( json.delayed ) {
              setIsDelayed(json.delayed_until);
            }
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

    const migrateSettings = () => {
        apiFetch({
            path: '/simplystatic/v1/migrate',
            method: 'POST',
            migrate: true,
        });
    }

    const updateSetting = (key, value) => {
        const updatedSettings = {...settings, [key]: value};
        setSettings(updatedSettings);
    };

    const getStatus = () => {
        apiFetch({path: '/simplystatic/v1/system-status'}).then((configs) => {
            setConfigs(configs);

            getStatusPassed();

        });
    }

    const getStatusPassed = () => {
        apiFetch({path: '/simplystatic/v1/system-status/passed'}).then((result) => {
            let test = JSON.parse(result);
            setPassedChecks(test.passed);
        });
    }

    const resetDiagnostics = () => {
        apiFetch({
            path: '/simplystatic/v1/reset-diagnostics',
            method: 'POST',
        });
    }

    const isPro = () => {
        if (options.is_multisite) {
            return true;
        }

        if (options.connect) {
            return !!options.connect.is_connected;
        }

        return false;
    }

    const isStudio = () => {
        if (options.home.includes('static.studio') || options.home.includes('static1.studio') || options.home.includes('static2.studio')) {
            return true;
        }

        return false;
    }

    const integrationRequiresSaving = (integration) => {
        /**
         * @todo make it defined inside integration classes when more come.
         * @type {string[]}
         */
        const integrations = [
            'environments'
        ]

        return integrations.indexOf(integration) >= 0;
    }

    const maybeQueueIntegration = (integration) => {
        if (!integrationRequiresSaving(integration)) {
            return;
        }

        // Already queued.
        if (isQueuedIntegration(integration)) {
            return;
        }

        queuedIntegrations.push(integration);
        setQueuedIntegrations(queuedIntegrations);
    }

    const maybeUnqueueIntegration = (integration) => {
        if (!integrationRequiresSaving(integration)) {
            return;
        }

        // Already queued.
        if (!isQueuedIntegration(integration)) {
            return;
        }

        const index = queuedIntegrations.indexOf(integration);
        if (index < 0) {
            return;
        }

        queuedIntegrations.splice(index, 1);
        setQueuedIntegrations(queuedIntegrations);
    }

    const canRunIntegration = (integration) => {
        if (!isIntegrationActive(integration)) {
            return false;
        }

        if (isQueuedIntegration(integration)) {
            return false;
        }

        return true;
    }

    const isQueuedIntegration = (integration) => {
        return queuedIntegrations.indexOf(integration) >= 0;
    }

    const isIntegrationActive = (integration) => {
        let integrations = settings.integrations;

        if (false === integrations || !integrations || !Array.isArray(integrations)) {
            return false;
        }

        if (integrations.indexOf(integration) >= 0) {
            return true;
        }

        return false;
    }

  useInterval(() => {
      setIsDelayed(isDelayed - 1);
    }, isDelayed > 0 ? 1000 : null);


  useInterval(() => {
        checkIfRunning();
    }, isRunning || isDelayed ? 5000 : null);

    useEffect(() => {
        // If current_settings is available in the options object, use it instead of fetching from the API
        if (options.current_settings) {
            setSettings(options.current_settings);
        } else {
            // Fallback to fetching from the API if current_settings is not available
            getSettings();
        }
        getStatus();
        checkIfRunning();
        setBlogId(options.blog_id)
    }, []);

    return (
        <SettingsContext.Provider
            value={{
                settings,
                configs,
                passedChecks,
                settingsSaved,
                setSettingsSaved,
                updateSetting,
                setSettings,
                saveSettings,
                resetSettings,
                resetDatabase,
                resetBackgroundQueue,
                getSettings,
                updateFromNetwork,
                importSettings,
                migrateSettings,
                resetDiagnostics,
                isRunning,
                setIsRunning,
                isPaused,
                setIsPaused,
                setIsResumed,
                isResumed,
                blogId,
                setBlogId,
                isPro,
                isStudio,
                isIntegrationActive,
                canRunIntegration,
                maybeQueueIntegration,
                maybeUnqueueIntegration,
                isQueuedIntegration,
                showMobileNav,
                setShowMobileNav,
                isDelayed
            }}
        >
            {props.children}
        </SettingsContext.Provider>
    );
}

export default SettingsContextProvider;
