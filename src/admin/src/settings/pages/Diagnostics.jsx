import {Card, CardBody, CardHeader, __experimentalSpacer as Spacer, Dashicon} from "@wordpress/components";
import {useContext} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";

function Diagnostics() {
    const {configs} = useContext(SettingsContext);

    const statusData = () => (
        <div>
            {
                Object.keys(configs).map(key => {
                    const items = configs[key];

                    return (
                        <div key={key}>
                            <Card>
                                <CardHeader>
                                    <b>{key}</b>
                                </CardHeader>
                                <CardBody>
                                    <div>
                                        <table style={{width: "100%", tableLayout: "fixed"}}>
                                            <tbody className={"table-data"}>
                                            {
                                                Object.entries(items).map(item => {

                                                    return (
                                                        <tr className={"table-row"} key={item[0]}>
                                                            <td className={"diagnostics-icon"}> {item[1].test ?
                                                                <Dashicon className={"icon-yes"} icon="yes"/> :
                                                                <Dashicon className={"icon-no"} icon="no"/>}
                                                            </td>
                                                            <td className={"diagnostics-test"}><b>{item[0]}</b></td>
                                                            <td>{item[1].test}</td>
                                                            <td className={"diagnostics-result"}> {
                                                                item[1].test ?
                                                                    <p>{item[1].description}</p> :
                                                                    <p>{item[1].error}</p>
                                                            }
                                                            </td>
                                                        </tr>
                                                    )
                                                })
                                            }
                                            </tbody>
                                        </table>
                                    </div>
                                </CardBody>
                            </Card>
                            <Spacer margin={5}/>
                        </div>
                    )
                })
            }
        </div>
    );


    return (
        <div className={"inner-settings"}>
            {statusData()}
        </div>
    )
}

export default Diagnostics;