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
                        {__('The all-in-one WordPress operations platform for static sites, including Site Health, reports, performance checks, uptime monitoring, and hosting.', 'simply-static')}
                    </p>
                    <ul className={"promo-features"}>
                        <li>{__('14-day trial with 3 sites included', 'simply-static')}</li>
                        <li>{__('Site Health and reports', 'simply-static')}</li>
                        <li>{__('Performance checks and uptime monitoring', 'simply-static')}</li>
                        <li>{__('All Pro features included', 'simply-static')}</li>
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
