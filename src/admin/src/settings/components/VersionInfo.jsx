
import {SettingsContext} from "../context/SettingsContext";
import {useContext} from "@wordpress/element";

function VersionInfo(){
    const {
        isPro,
        isStudio
    } = useContext(SettingsContext);

    return (<>
        {'pro' === options.plan && isPro() ?
            <>
                {isStudio() ?
                    <p className={"version-number"}>
                        Free: <b>{options.version}</b><br></br>
                        Pro: <b>{options.version_pro}</b><br></br>
                        Studio: <b>{options.version_studio}</b>
                    </p>
                    :
                    <p className={"version-number"}>
                        Free: <b>{options.version}</b><br></br>
                        Pro: <b>{options.version_pro}</b>
                    </p>
                }
            </>
            :
            <p className={"version-number"}>Version: <b>{options.version}</b></p>
        }
        </>)
}

export default VersionInfo;