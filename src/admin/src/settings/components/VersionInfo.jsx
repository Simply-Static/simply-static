
import {SettingsContext} from "../context/SettingsContext";
import {useContext} from "@wordpress/element";

const {__} = wp.i18n;

function VersionInfo(){
    const {
        isPro,
        isStudio
    } = useContext(SettingsContext);

    return (<>
        <h4 className={"settings-headline"}>{__('Version', 'simply-static')}</h4>
        {'pro' === options.plan && isPro() ?
            <>
                {isStudio() ?
                    <p className={"version-number"}>
                        {__('Free', 'simply-static')}: <b>{options.version}</b><br></br>
                        {__('Pro', 'simply-static')}: <b>{options.version_pro}</b><br></br>
                        {__('Studio', 'simply-static')}: <b>{options.version_studio}</b>
                    </p>
                    :
                    <p className={"version-number"}>
                        {__('Free', 'simply-static')}: <b>{options.version}</b><br></br>
                        {__('Pro', 'simply-static')}: <b>{options.version_pro}</b>
                    </p>
                }
            </>
            :
            <p className={"version-number"}>{__('Version', 'simply-static')}: <b>{options.version}</b></p>
        }
        </>)
}

export default VersionInfo;
