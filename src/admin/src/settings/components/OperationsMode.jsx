import {
    Button,
    Card,
    CardBody,
    CardHeader,
    Notice,
    TextControl,
} from '@wordpress/components';
import {useState} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const {__} = wp.i18n;

const getErrorMessage = (error) => {
    if (error && error.message) {
        return error.message;
    }

    return __('The operations mode request could not be completed.', 'simply-static');
};

function OperationsMode({locked = false}) {
    const mode = options.operations_mode || {};
    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [busy, setBusy] = useState('');
    const [error, setError] = useState('');
    const minimumPassword = mode.minimum_password || 8;

    const request = (path, data = {}) => {
        setBusy(path);
        setError('');

        return apiFetch({
            path: `/simplystatic-pro/v1/operations-mode/${path}`,
            method: 'POST',
            data,
        }).then(() => {
            window.location.reload();
        }).catch((requestError) => {
            setError(getErrorMessage(requestError));
        }).finally(() => {
            setBusy('');
        });
    };

    const enableOrUpdate = () => {
        if ((!mode.enabled || password) && password.length < minimumPassword) {
            setError(
                __('The operations mode password must contain at least 8 characters.', 'simply-static')
            );
            return;
        }

        if (password !== confirmation) {
            setError(__('The password confirmation does not match.', 'simply-static'));
            return;
        }

        request('configure', {enabled: true, password});
    };

    if (locked || (mode.enabled && !mode.unlocked)) {
        return (
            <Card>
                <CardHeader>
                    <b>{__('Operations mode', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('Publishing remains available. Enter the operations password to access deployment, account, and plugin settings.', 'simply-static')}</p>
                    {error && <Notice status="error" isDismissible={false}><p>{error}</p></Notice>}
                    <TextControl
                        label={__('Operations password', 'simply-static')}
                        type="password"
                        value={password}
                        autoComplete="current-password"
                        disabled={!!busy}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        onChange={setPassword}
                        onKeyDown={(event) => {
                            if ('Enter' === event.key && password) {
                                request('unlock', {password});
                            }
                        }}
                    />
                    <p>
                        <Button
                            variant="primary"
                            disabled={!!busy || !password}
                            isBusy={'unlock' === busy}
                            onClick={() => request('unlock', {password})}
                        >
                            {__('Unlock settings', 'simply-static')}
                        </Button>
                    </p>
                </CardBody>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <b>{__('Operations mode', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                {mode.enabled ?
                    <Notice status="success" isDismissible={false}>
                        <p>{__('Operations mode is enabled and settings are currently unlocked for support.', 'simply-static')}</p>
                    </Notice>
                    :
                    <p>{__('Protect deployment credentials and configuration from client changes while keeping publishing and diagnostics available.', 'simply-static')}</p>
                }

                <TextControl
                    label={mode.enabled ? __('New password (optional)', 'simply-static') : __('Operations password', 'simply-static')}
                    help={mode.enabled
                        ? __('Leave blank to keep the existing password.', 'simply-static')
                        : __('Use at least 8 characters. The password is stored as a one-way hash.', 'simply-static')}
                    type="password"
                    value={password}
                    autoComplete="new-password"
                    disabled={!!busy}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    onChange={setPassword}
                />
                <TextControl
                    label={__('Confirm password', 'simply-static')}
                    type="password"
                    value={confirmation}
                    autoComplete="new-password"
                    disabled={!!busy}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    onChange={setConfirmation}
                />

                {error && <Notice status="error" isDismissible={false}><p>{error}</p></Notice>}

                <p>
                    <Button
                        variant="primary"
                        disabled={!!busy || (!mode.enabled && !password)}
                        isBusy={'configure' === busy}
                        onClick={enableOrUpdate}
                    >
                        {mode.enabled ? __('Update operations mode', 'simply-static') : __('Enable operations mode', 'simply-static')}
                    </Button>
                    {mode.enabled &&
                        <>
                            {' '}
                            <Button
                                variant="secondary"
                                disabled={!!busy}
                                isBusy={'lock' === busy}
                                onClick={() => request('lock')}
                            >
                                {__('Lock settings now', 'simply-static')}
                            </Button>
                            {' '}
                            <Button
                                variant="tertiary"
                                isDestructive
                                disabled={!!busy}
                                onClick={() => request('configure', {enabled: false})}
                            >
                                {__('Disable operations mode', 'simply-static')}
                            </Button>
                        </>
                    }
                </p>
                {mode.enabled &&
                    <p>{__('Unlocked sessions expire automatically after one hour.', 'simply-static')}</p>
                }
            </CardBody>
        </Card>
    );
}

export default OperationsMode;
