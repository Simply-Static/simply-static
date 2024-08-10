import {Button, Dashicon, Modal} from "@wordpress/components";
import {useState} from "@wordpress/element";
import ReactPlayer from 'react-player';

const {__} = wp.i18n;

function HelperVideo( {title, videoUrl}) {
    const [isVideoModalOpen, setVideoModalOpen] = useState(false);
    const openVideoModal = () => setVideoModalOpen(true);
    const closeVideoModal = () => setVideoModalOpen(false);

    return (
        <>
            {isVideoModalOpen &&
                <div class={"simply-static-video-modal-background"}>
                    <Modal title={title}
                           className={'simply-static-video-modal'}
                           onRequestClose={closeVideoModal}>
                        <ReactPlayer
                            url={videoUrl}
                            controls={true}
                            width={'920px'}
                            height={'560px'}
                        />
                    </Modal>
                </div>
            }
            <Button variant={'link'} className="simply-static-video-button" onClick={openVideoModal}><Dashicon icon={'format-video'}/></Button>
        </>
    )
}

export default HelperVideo;