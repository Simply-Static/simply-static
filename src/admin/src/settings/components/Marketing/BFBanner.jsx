import {Notice, Animate, __experimentalSpacer as Spacer, Button} from '@wordpress/components';
import {useEffect, useState} from '@wordpress/element';

const {__} = wp.i18n;

/**
 * Black Friday Banner
 *
 * Displays a dismissible promo banner between Nov 28 and Dec 3 (inclusive)
 * for Free-plan, single-site installs on the Generate page.
 *
 * Dismissal is persisted per year using localStorage key:
 *   ss_bf_banner_dismissed_YYYY
 */
export default function BFBanner() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        try {
            // Only on single site and free plan
            if (options.is_network || options.plan === 'pro') {
                setVisible(false);
                return;
            }

            const now = new Date();
            const year = now.getFullYear();

            // Date window: Nov 28 00:00:00 – Dec 5 23:59:59 local time
            const start = new Date(year, 10, 28, 0, 0, 0); // month index: 10 = November
            const end = new Date(year, 11, 5, 23, 59, 59); // 11 = December

            const dismissedKey = `ss_bf_banner_dismissed_${year}`;
            const dismissed = (typeof window !== 'undefined' && window.localStorage)
                ? window.localStorage.getItem(dismissedKey) === '1'
                : false;

            setVisible(!dismissed && now >= start && now <= end);
        } catch (e) {
            // Fail closed
            setVisible(false);
        }
    }, []);

    const dismiss = () => {
        try {
            const year = new Date().getFullYear();
            const dismissedKey = `ss_bf_banner_dismissed_${year}`;
            if (typeof window !== 'undefined' && window.localStorage) {
                window.localStorage.setItem(dismissedKey, '1');
            }
        } catch (e) { /* noop */
        }
        setVisible(false);
    };

    if (!visible) return null;

    return (
        <>
            <Animate type="slide-in" options={{origin: 'top'}}>
                {() => (
                    <Notice status="notice" isDismissible onRemove={dismiss} className={"bf-banner"}>
                        <p>
                            <strong>BLACK FRIDAY</strong>: Upgrade to <b>Simply Static Pro</b> and save <b>25%</b> by
                            using the code <b>SSBF25</b>
                            {' '}
                            <Button
                                isPrimary
                                href="https://simplystatic.com/pricing/?utm_source=plugin&utm_medium=banner&utm_campaign=bf"
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{marginLeft: 8}}
                            >
                                {__('Buy now', 'simply-static')}
                            </Button>
                        </p>
                    </Notice>
                )}
            </Animate>
            <Spacer margin={5}/>
        </>
    );
}
