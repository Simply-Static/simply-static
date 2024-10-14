import {Button, Flex, FlexItem, SelectControl} from "@wordpress/components";
const {__} = wp.i18n;

export default function EnvironmentDropdown({ onChange, current, environments, disabled, onDelete }) {
    return (
        <Flex align={'flex-start'}>
            <FlexItem>
                <SelectControl
                    disabled={disabled}
                    value={current}
                    options={environments}
                    help={__('Choose an environment or create a new one to configure settings.', 'simply-static')}
                    onChange={onChange}
                />
            </FlexItem>
            <FlexItem>
                <Button
                    className={'environment-delete-button'}
                    variant={'tertiary'}
                    label={__('Delete selected environment', 'simply-static')}
                    showToolTip={true}
                    size={'small'}
                    icon={'trash'}
                    disabled={disabled}
                    onClick={onDelete}
                ></Button>
            </FlexItem>
        </Flex>
    )
}