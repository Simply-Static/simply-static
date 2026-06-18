import {
    Button,
    Notice,
} from "@wordpress/components";

const {__} = wp.i18n;

function StudioNotice({
    heading,
    children,
    cta = __('Explore Pro →', 'simply-static'),
    ctaUrl = 'https://simplystatic.com/simply-static-pro/?utm_source=plugin&utm_medium=settings&utm_campaign=studio-notice',
}) {
    return (
        <Notice status="warning" isDismissible={false} className="studio-settings-notice">
            <h3>{heading}</h3>
            <div className="studio-settings-notice__body">
                {children}
            </div>
            {cta && ctaUrl &&
                <Button
                    variant="secondary"
                    href={ctaUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="studio-settings-notice__cta"
                >
                    {cta}
                </Button>
            }
        </Notice>
    );
}

export default StudioNotice;
