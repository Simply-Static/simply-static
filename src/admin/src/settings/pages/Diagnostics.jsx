import {
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Dashicon,
    Button,
    Animate, Notice
} from "@wordpress/components";
import {useContext, useState} from "@wordpress/element";
import {SettingsContext} from "../context/SettingsContext";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;

function Diagnostics() {
    const {configs, resetDiagnostics} = useContext(SettingsContext);
    const [isReset, setIsReset] = useState(false);

    const runResetDiagnostics = () => {
        resetDiagnostics();
        setIsReset(true);

        setTimeout(function () {
            window.location.reload();
        }, 2000);
    }

    const statusData = () => (
        <div>
            <Card>
                <CardHeader>
                    <b>{__('Diagnostics', 'simply-static')}<HelperVideo
                        title={__('How to use diagnostics', 'simply-static')}
                        videoUrl={'https://youtu.be/X59YMlz6F2s'}/></b>
                </CardHeader>
                <CardBody>
                    <p>
                        {__('Our diagnostics tool provides detailed insights into your WordPress installation and server configuration and tells you exactly what needs to be optimized to get the most out of Simply Static. Click the button below to get the latest results.', 'simply-static')}
                    </p>
                    <p>
                        <Button onClick={runResetDiagnostics} variant="secondary">{__('Reset Diagnostics', 'simply-static')}</Button>
                    </p>
                    {isReset ?
                        <Animate type="slide-in" options={{origin: 'top'}}>
                            {() => (
                                <Notice status="success" isDismissible={false}>
                                    <p>
                                        {__('Diagnostics resetted successfully.', 'simply-static')}
                                    </p>
                                </Notice>
                            )}
                        </Animate>
                        :
                        ''
                    }
                </CardBody>
            </Card>
            <Spacer margin={5}/>
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