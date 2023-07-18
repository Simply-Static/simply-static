import {
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Flex, FlexItem, SelectControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import ActivityLog from "../components/ActivityLog";
import ExportLog from "../components/ExportLog";
import LogButtons from "../components/LogButtons";

const {__} = wp.i18n;

function Generate() {
    const {settings, blogId, setBlogId, settingsType, setSettingsType} = useContext(SettingsContext);

    return (<div className={"inner-settings settings-wide"}>
        <ActivityLog />
        <Spacer margin={5}/>
        <Flex align={"top"}>
            {'pro' === options.plan && options.is_network &&
                <FlexItem isBlock={true}>
                    <Card>
                        <CardHeader>
                            <b>{__('Multisite', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <SelectControl
                                label={__('Choose a site to export', 'simply-static')}
                                value={blogId}
                                options={options.sites.map(function (site) {
                                    return { label: `${site.name} (${site.url})`, value: site.blog_id}
                                })}
                                onChange={(blog_id) => {
                                    setBlogId(blog_id);
                                }}
                            />
                            <SelectControl
                                label={__('Settings to Use:', 'simply-static')}
                                value={settingsType}
                                options={[
                                    { label: __( 'Use Network\'s Settings', 'simply-static' ), value: 'network' },
                                    { label: __( 'Use Site\'s Settings', 'simply-static' ), value: 'site' }
                                ]}
                                onChange={(type) => {
                                    setSettingsType(type);
                                }}
                            />
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