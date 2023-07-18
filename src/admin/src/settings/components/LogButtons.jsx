import {Animate, Button, Notice} from "@wordpress/components";
import {useState} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
const {__} = wp.i18n;

function LogButtons() {
    const [logDeleted, setLogDeleted] = useState(false);

    const deleteLog = () => {
        apiFetch({
            path: '/simplystatic/v1/delete-log',
            method: 'POST',
        });

        setLogDeleted(true);

        setTimeout(function () {
            setLogDeleted(false);
        }, 2000);
    }

    return (
        <>
        <Button variant="primary" href={options.log_file} download={true}
                style={{marginRight: "10px"}}>{__('Download Log', 'simply-static')}</Button>
        <Button variant="secondary"
            onClick={deleteLog}>{__('Clear Log', 'simply-static')}</Button>
        {logDeleted &&
        <Animate type="slide-in" options={{origin: 'top'}}>
            {() => (
                <Notice status="success" isDismissible={false}>
                    <p>
                        {__('Log file successfully deleted.', 'simply-static')}
                    </p>
                </Notice>
            )}
        </Animate>
        }
        </>
    )
}

export default LogButtons;