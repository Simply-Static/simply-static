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
        // If there are queued integrations that require a reload, capture before save.
        const shouldReload = queuedIntegrations && queuedIntegrations.length > 0;

        return apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: settings,
        }).then(resp => {
            // Clear any queued integration markers and resolve with reload hint.
            setQueuedIntegrations([]);
            return { resp, shouldReload };
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
        // Use functional update to avoid race conditions when calling updateSetting multiple times in quick succession
        setSettings(prevSettings => ({...prevSettings, [key]: value}));
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

    // Robustly resolve an integration object by id across array/object shapes
    function getIntegrationById(id) {
        try {
            const list = options && options.integrations;
            if (!list) return null;
            // If it's an array, find by .id
            if (Array.isArray(list)) {
                for (let i = 0; i < list.length; i++) {
                    const it = list[i];
                    if (it && it.id === id) return it;
                }
                return null;
            }
            // Otherwise assume object keyed by id
            return list[id] || null;
        } catch (e) {
            return null;
        }
    }

    const integrationRequiresSaving = (integration) => {
        // Defined per integration in PHP via Integration::$requires_ui_reload
        const integ = getIntegrationById(integration);
        return !!(integ && integ.requires_ui_reload);
    }

    const maybeQueueIntegration = (integration) => {
        if (!integrationRequiresSaving(integration)) {
            return;
        }

        // Already queued.
        if (isQueuedIntegration(integration)) {
            return;
        }

        // Use functional update to avoid mutating state in place
        setQueuedIntegrations(prev => {
            if (prev && prev.indexOf(integration) >= 0) {
                return prev;
            }
            return [ ...(prev || []), integration ];
        });
    }

    const maybeUnqueueIntegration = (integration) => {
        if (!integrationRequiresSaving(integration)) {
            return;
        }

        // Already queued?
        if (!isQueuedIntegration(integration)) {
            return;
        }

        // Use functional update to create a new array without the integration
        setQueuedIntegrations(prev => {
            return (prev || []).filter(id => id !== integration);
        });
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

    // Removed legacy redirect marker handler; router uses 'ss-initial-page' on bootstrap

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
                getIntegrationById,
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
