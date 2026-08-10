import {Button} from '@wordpress/components';

const {__} = wp.i18n;

export default function PromoSidebar() {
    return (
        <div className={"promo-sidebar"}>
            <div className={"promo-card promo-card-pro"}>
                <div className={"promo-card-inner"}>
                    <span className={"promo-badge"}>{__('Pro', 'simply-static')}</span>
                    <h3 className={"promo-headline"}>
                        {__('Simply Static Pro', 'simply-static')}
                    </h3>
                    <p className={"promo-description"}>
                        {__('Unlock single push, change detection, environments, search, forms, and much more.', 'simply-static')}
                    </p>
                    <ul className={"promo-features"}>
                        <li>{__('Push only changes', 'simply-static')}</li>
                        <li>{__('Search integration', 'simply-static')}</li>
                        <li>{__('Form handling', 'simply-static')}</li>
                        <li>{__('Multilingual support', 'simply-static')}</li>
                        <li>{__('Minification and image optimization', 'simply-static')}</li>
                    </ul>
                    <Button
                        isPrimary
                        href="https://simplystatic.com/simply-static-pro/?utm_source=plugin&utm_medium=promo-sidebar&utm_campaign=pro"
                        target="_blank"
                        rel="noopener noreferrer"
                        className={"promo-button"}
                    >
                        {__('Get Simply Static Pro →', 'simply-static')}
                    </Button>
                </div>
            </div>

            <div className={"promo-card promo-card-studio"}>
                <div className={"promo-card-inner"}>
                    <span className={"promo-badge promo-badge-studio"}>{__('Studio', 'simply-static')}</span>
                    <h3 className={"promo-headline"}>
                        {__('Static Studio', 'simply-static')}
                    </h3>
                    <p className={"promo-description"}>
                        {__('The complete Static WordPress operations platform. Keep WordPress for editing, publish fast static websites, and manage everything from one place.', 'simply-static')}
                    </p>
                    <ul className={"promo-features"}>
                        <li>{__('Start & Stop WordPress', 'simply-static')}</li>
                        <li>{__('Static publishing to CDN', 'simply-static')}</li>
                        <li>{__('Central dashboard for all sites', 'simply-static')}</li>
                        <li>{__('Automated WordPress migration', 'simply-static')}</li>
                        <li>{__('Site health, security, and performance checks', 'simply-static')}</li>
                    </ul>
                    <Button
                        isPrimary
                        href="https://simplystatic.com/simply-static-studio/?utm_source=plugin&utm_medium=promo-sidebar&utm_campaign=studio"
                        target="_blank"
                        rel="noopener noreferrer"
                        className={"promo-button promo-button-studio"}
                    >
                        {__('Start 14-Day Free Trial →', 'simply-static')}
                    </Button>
                </div>
            </div>
        </div>
    );
}
