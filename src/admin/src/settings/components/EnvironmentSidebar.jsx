import {Button, Flex, FlexItem, SelectControl} from "@wordpress/components";
import {useEffect, useState} from "@wordpress/element";
import EnvironmentForm from "./Environments/EnvironmentForm";
import EnvironmentDropdown from "./Environments/EnvironmentsDropdown";
import apiFetch from "@wordpress/api-fetch";

const {__} = wp.i18n;

export default function EnvironmentSidebar({ getSettings, isRunning }) {
    const [selectedEnvironment, setSelectedEnvironment] = useState('');
    const [selectableEnvironments, setSelectableEnvironments] = useState([]);
    const [showingEnvironmentForm, setShowingEnvironmentForm] = useState(false);
    const [changingEnvironment, setChangingEnvironment] = useState(false);

    useEffect(() => {
        if (options.environments) {
            let environments = Object.keys(options.environments).map(function (version) {
                return {label: options.environments[version], value: version}
            });

            setSelectableEnvironments( environments );
            setSelectedEnvironment( options.current_environment );
        }
    }, [options]);

    const deleteCurrentVersion = () => {
        setChangingEnvironment(true);
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

    return (<>
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

    </>)
}