import {Card, CardBody, CardHeader, __experimentalSpacer as Spacer, Dashicon} from "@wordpress/components";
import {useContext} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";

function SystemStatus() {
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
                                    <div style={{maxWidth : "300px"}}>
                                        <table>
                                            <tbody className={"table-data"}>
                                            {
                                                Object.entries(items).map(item => {
                                                    if (typeof item[1] == "boolean")
                                                        return (<tr className={"table-row"} key={item[0]}>
                                                            <td><b>{item[0]}</b></td>
                                                            <td> {item[1] ? <Dashicon className={"icon-yes"} icon="yes"/> :
                                                                <Dashicon  className={"icon-no"} icon="no"/>} </td>
                                                        </tr>)
                                                    return (<tr className={"table-row"} key={item[0]}>
                                                        <td><b>{item[0]}</b></td>
                                                        <td> {item[1]} </td>
                                                    </tr>);
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

export default SystemStatus;