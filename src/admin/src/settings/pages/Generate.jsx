import {
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Flex, FlexItem, SelectControl, Button,
} from "@wordpress/components";
import {useContext, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import ActivityLog from "../components/ActivityLog";
import ExportLog from "../components/ExportLog";
import LogButtons from "../components/LogButtons";
import Sites from "../components/Sites";

const {__} = wp.i18n;

function Generate() {
    const {settings, blogId, setBlogId} = useContext(SettingsContext);

    return (<div className={"inner-settings"}>
        {!options.is_network &&
            <>
                <ActivityLog/>
                <Spacer margin={5}/>
            </>
        }
        <Flex align={"top"}>
            {options.is_network &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardBody>
                            <Sites />
                        </CardBody>

                    </Card>
                </FlexItem>
            }
            {settings.debugging_mode && options.log_file && !options.is_network &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Debugging', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <LogButtons/>
                        </CardBody>
                    </Card>
                </FlexItem>
            }
        </Flex>
        {!options.is_network && <>
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <b>{__('Export Log', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <ExportLog/>
                </CardBody>
            </Card>
        </>
        }
    </div>)
}

export default Generate;