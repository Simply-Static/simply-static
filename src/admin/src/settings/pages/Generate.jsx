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
    const [selectedSiteUrl, setSelectedSiteURL] = useState('');
    const [selectedSiteActivityUrl, setSelectedSiteActivityUrl] = useState('');

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
                    <Card>
                        <CardHeader>
                            <b>{__('Multisite', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <SelectControl
                                label={__('Choose a site to export', 'simply-static')}
                                value={blogId}
                                options={options.sites.map(function (site) {
                                    return {label: `${site.name} (${site.url})`, value: site.blog_id}
                                })}
                                onChange={(blog_id) => {
                                    setBlogId(blog_id);

                                    // Update admin edit URL:
                                    options.sites.some(item => {
                                        if (item.blog_id === blog_id) {
                                            setSelectedSiteURL(item.settings_url);
                                            setSelectedSiteActivityUrl(item.activity_log_url);
                                        }
                                    });
                                }}
                            />
                            {selectedSiteUrl &&
                                <p>
                                    <Button isPrimary href={selectedSiteUrl}>Switch to Site settings</Button>
                                    <Button style={{marginLeft: "5px"}} isSecondary href={selectedSiteActivityUrl}>Check
                                        progress</Button>
                                </p>
                            }
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
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Export Log', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ExportLog/>
            </CardBody>
        </Card>
    </div>)
}

export default Generate;