import {Button, Dashicon} from "@wordpress/components";
const {__} = wp.i18n;
function GenerateButtons(props) {
    const { children, canGenerate, isPaused, isDelayed, startExport, cancelExport, pauseExport, resumeExport } = props;
    const hasPauseFeature = typeof pauseExport === 'function';
    const hasCancelFeature = typeof cancelExport === 'function';
    const hasResumeFeature = typeof resumeExport === 'function';
    const hasExportFeature = typeof startExport === 'function';

    return <div className="generate-buttons-container">
        {canGenerate && hasExportFeature && <Button
            onClick={() => {
                startExport();
            }}
            disabled={!canGenerate || isDelayed}
            className={'generate'}
        >
            {canGenerate && <>
                <Dashicon icon="update"/>
                {__('Generate', 'simply-static')}
            </>}

            {canGenerate && isDelayed>0 && <> {isDelayed}s</>}

            {!canGenerate && <Dashicon icon="update spin"/>}
        </Button>}
        {!canGenerate && <>
            {!isPaused && hasPauseFeature && <Button
                label={__('Pause', 'simply-static')}
                className={"ss-generate-media-button"}
                showToolTip={true}
                onClick={() => pauseExport()}>
                <Dashicon icon={"controls-pause"}/>
            </Button>
            }
            {isPaused && hasResumeFeature && <Button
                label={__('Resume', 'simply-static')}
                className={"ss-generate-media-button"}
                showToolTip={true}
                onClick={() => resumeExport()}>
                <Dashicon icon={"controls-play"}/>
            </Button>
            }
            { hasCancelFeature && <Button
                onClick={() => cancelExport()}
                label={__('Cancel', 'simply-static')}
                className={"ss-generate-cancel-button"}
                showToolTip={true}
            >
                <Dashicon icon={'no'}/>
            </Button> }
        </>}
        {children}
    </div>
}

export default GenerateButtons;
