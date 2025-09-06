import {Button, Flex, FlexBlock, FlexItem, SelectControl} from "@wordpress/components";
const {__} = wp.i18n;

export default function EnvironmentDropdown({ onChange, current, environments, disabled, onDelete }) {
    return (
        <Flex>
            <FlexItem style={{minWidth: "80%"}}>
                <SelectControl
                    disabled={disabled}
                    value={current}
                    options={environments}
                    help={__('Choose an environment or create a new one to configure settings.', 'simply-static')}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    onChange={onChange}
                />
            </FlexItem>
            <FlexItem>
                <Button
                    className={'environment-delete-button'}
                    variant={'tertiary'}
                    label={__('Delete selected environment', 'simply-static')}
                    size={'small'}
                    icon={'trash'}
                    disabled={disabled}
                    onClick={onDelete}
                ></Button>
            </FlexItem>
        </Flex>
    )
}