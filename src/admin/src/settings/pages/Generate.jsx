import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import ActivityLog from "../components/ActivityLog";
import ExportLog from "../components/ExportLog";
import LogButtons from "../components/LogButtons";

const {__} = wp.i18n;

function Generate() {
    const {settings} = useContext(SettingsContext);


    return (<div className={"inner-settings settings-wide"}>
        <ActivityLog />
        <Spacer margin={5}/>
        <Flex>
            {'pro' === options.plan && options.is_network &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Multisite', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <p>The options for the site selection are going here..</p>
                        </CardBody>
                    </Card>
                </FlexItem>
            }
            {settings.debugging_mode && options.log_file &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Debugging', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <LogButtons />
                        </CardBody>
                    </Card>
                </FlexItem>
            }
        </Flex>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Export Log', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ExportLog />
            </CardBody>
        </Card>
    </div>)
}

export default Generate;