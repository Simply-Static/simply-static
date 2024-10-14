import {Button, Flex, FlexItem, TextControl} from "@wordpress/components";
import {useState} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
const {__} = wp.i18n;

export default function EnvironmentForm( { onClose, setSelectableEnvironments, setSelectedEnvironment } ) {
    const [name, setName] = useState('');
    const [creating, setCreating] = useState(false);

    const createNew = () => {
        setCreating(true);
        apiFetch({
            path: '/simplystatic/v1/environment',
            method: 'POST',
            data: { title: name },
        }).then(resp => {
            let environments = Object.keys(resp.environments).map(function (version) {
                return {label: resp.environments[version], value: version}
            });

            setSelectableEnvironments( environments );
            setSelectedEnvironment(resp.current_environment);

            onClose();
        }).catch(resp => {
            alert(resp.message);
        }).finally(() => setCreating(false));
    }

    return (
        <div className={'ss-environment-form'}>
            <TextControl
                label={"Name"}
                onChange={(val) => setName(val)}
                value={name}
            />
            <p>{__('A new environment will be created with the current configuration.', 'simply-static')}</p>

            <Flex justify={'flex-start'}>
                <FlexItem>
                    <Button variant={'primary'} onClick={createNew} isBusy={creating}>
                        { creating ? __( 'Creating...', 'simply-static' ) : __( 'Create', 'simply-static' ) }
                    </Button>
                </FlexItem>
                <FlexItem>
                    <Button variant={'link'} onClick={onClose}>{ __( 'Cancel', 'simply-static' )}</Button>
                </FlexItem>
            </Flex>

        </div>
    )
}