import {Button, Flex, FlexItem, SelectControl} from "@wordpress/components";

export default function EnvironmentDropdown({ onChange, selectedEnvironment, environments, disabled, onDelete }) {
    return (
        <Flex align={'flex-start'}>
            <FlexItem>
                <SelectControl
                    disabled={disabled}
                    value={selectedEnvironment}
                    options={environments}
                    help={__('Choose an environment or create a new one to configure settings.', 'simply-static')}
                    onChange={(version) => {
                        onChange(version);
                    }}
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