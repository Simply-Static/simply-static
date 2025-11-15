import {Button, Flex, FlexItem, SelectControl} from "@wordpress/components";
import {useContext, useEffect, useState} from "@wordpress/element";
import EnvironmentForm from "./Environments/EnvironmentForm";
import EnvironmentDropdown from "./Environments/EnvironmentsDropdown";
import apiFetch from "@wordpress/api-fetch";
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

export default function EnvironmentSidebar({ getSettings, isRunning }) {
    const { getIntegrationById } = useContext(SettingsContext);
    // Helper: determine if Environments feature is available (Pro + enabled)
    const isEnvironmentsAvailable = () => {
        try {
            if (typeof options === 'undefined') return false;
            if (options.plan !== 'pro') return false;
            const env = getIntegrationById ? getIntegrationById('environments') : null;
            if (!env) return false;
            // Prefer explicit active flag when present, otherwise fallback to can_run
            return !!(env.active || env.can_run);
        } catch (e) {
            return false;
        }
    }
    const [selectedEnvironment, setSelectedEnvironment] = useState('');
    const [selectableEnvironments, setSelectableEnvironments] = useState([]);
    const [showingEnvironmentForm, setShowingEnvironmentForm] = useState(false);
    const [changingEnvironment, setChangingEnvironment] = useState(false);

    useEffect(() => {
        if (!isEnvironmentsAvailable()) {
            return;
        }
        apiFetch({
            path: '/simplystatic/v1/environment',
            method: 'GET',
        })
            .then((resp) => {
                let environments = Object.keys(resp.environments).map(function (version) {
                    return {label: resp.environments[version], value: version}
                });

                setSelectableEnvironments( environments );
                setSelectedEnvironment(resp.current_environment);
            })
            .catch((err) => {
                // Swallow missing route errors gracefully when Pro is not active
                // or the endpoint is unavailable.
            });
    }, []);

    const deleteCurrentVersion = () => {
        setChangingEnvironment(true);
        if (!isEnvironmentsAvailable()) {
            setChangingEnvironment(false);
            return;
        }
        apiFetch({
            path: '/simplystatic/v1/environment',
            method: 'DELETE',
            data: { version: selectedEnvironment }
        }).then((resp) => {
            getSettings();
            let environments = Object.keys(resp.environments).map(function (version) {
                return {label: resp.environments[version], value: version}
            });

            setSelectableEnvironments( environments );
            setSelectedEnvironment(resp.current_environment);

        })
        .catch(resp => alert(resp.message))
        .finally(() => {
            setChangingEnvironment(false);
        });
    }

    const updateCurrentVersion = ( version ) => {
        setChangingEnvironment(true);
        if (!isEnvironmentsAvailable()) {
            setChangingEnvironment(false);
            return;
        }
        apiFetch({
            path: '/simplystatic/v1/environment',
            method: 'PUT',
            data: { version: version },
        }).then(() => {
            getSettings();
            setSelectedEnvironment( version );
        })
        .catch(resp => alert(resp.message))
        .finally(() => {
            setChangingEnvironment(false);
        });
    }

    const currentVersion = () => {
        if ( changingEnvironment ) {
            return __( 'Changing ...', 'simply-static' );
        }

        return selectableEnvironments.filter( (item) => {
            return item.value === selectedEnvironment;
        }).pop().label;
    }

    return (<div className={"environment-container"}>
        <h4 className={"settings-headline"}> {__('Environment', 'simply-static')}</h4>
        { !showingEnvironmentForm && selectedEnvironment &&
            <p>Current: <strong>{currentVersion()}</strong></p>
        }
        { !showingEnvironmentForm && selectableEnvironments.length > 0 &&
            <EnvironmentDropdown
                onChange={updateCurrentVersion}
                environments={selectableEnvironments}
                onDelete={deleteCurrentVersion}
                current={selectedEnvironment}
                disabled={isRunning || changingEnvironment}
            />
        }
        {
            !showingEnvironmentForm &&
            <Button disabled={isRunning || changingEnvironment} variant={"primary"} size={"large"} onClick={() => setShowingEnvironmentForm(true)}>
                Create an Environment
            </Button>
        }
        { showingEnvironmentForm && <EnvironmentForm onClose={() => setShowingEnvironmentForm(false)} setSelectedEnvironment={setSelectedEnvironment} setSelectableEnvironments={setSelectableEnvironments} />}

    </div>)
}