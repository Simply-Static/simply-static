import {__experimentalSpacer as Spacer, Button, Card, Dashicon} from "@wordpress/components";
import {useContext} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import VersionInfo from "./VersionInfo";

const {__} = wp.i18n;
function SidebarMultisite( props = null ) {
    const { isPro } = useContext(SettingsContext);

    return (<Card className={"plugin-nav"}>
        <div className={"plugin-logo"}>
            <img alt="Logo"
                 src={options.logo}/>
        </div>
        <VersionInfo/>

        <Spacer margin={5}/>
        <Spacer margin={5}/>
        <Button href="https://simplystatic.com/changelogs/" target="_blank">
            <Dashicon icon="editor-ul"/> {__('Changelog', 'simply-static')}
        </Button>
        <Button href="https://docs.simplystatic.com" target="_blank">
            <Dashicon icon="admin-links"/> {__('Documentation', 'simply-static')}
        </Button>
        {'free' === options.plan &&
            <Button href="https://simplystatic.com" target="_blank">
                <Dashicon
                    icon="admin-site-alt3"/>Simply Static Pro
            </Button>
        }
    </Card>);
}

export default SidebarMultisite;