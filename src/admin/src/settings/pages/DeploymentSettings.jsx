import {
    Button,
    ClipboardButton,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, ToggleControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function DeploymentSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [deliveryMethod, setDeliveryMethod] = useState('zip');
    const [githubAccountType, setGithubAccountType] = useState('personal');
    const [githubVisibility, setGithubVisibility] = useState('private');
    const [emptyBucketBeforeExport, setEmptyBucketBeforeExport] = useState(false);
    const [useForms, setUseForms] = useState(true);
    const [region, setRegion] = useState('us-east-2');
    const [hasCopied, setHasCopied] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.delivery_method) {
            setDeliveryMethod(settings.delivery_method);
        }

        if (settings.ssh_use_forms) {
            setUseForms(settings.ssh_use_forms);
        }

        if (settings.github_account_type) {
            setGithubAccountType(settings.github_account_type);
        }

        if (settings.github_repository_visibility) {
            setGithubVisibility(settings.github_repository_visibility);
        }

        if (settings.github_repository_visibility) {
            setGithubVisibility(settings.github_repository_visibility);
        }

        if (settings.aws_empty) {
            setEmptyBucketBeforeExport(settings.aws_empty);
        }

        if (settings.aws_region) {
            setRegion(settings.aws_region);
        }
    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Deployment Settings', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Choose from a variety of deployment methods. Depending on your selection we either provide a ZIP file, export to a local directory or send your files to a remote destination.', 'simply-static')}</p>
                {'pro' === options.plan ?
                    <SelectControl
                        label={__('Deployment method', 'simply-static')}
                        value={deliveryMethod}
                        options={[
                            {label: __('ZIP Archive', 'simply-static'), value: 'zip'},
                            {label: __('Local Directory', 'simply-static'), value: 'local'},
                            {label: __('Simply CDN', 'simply-static'), value: 'simply-cdn'},
                            {label: __('GitHub', 'simply-static'), value: 'github'},
                            {label: __('Tiiny.host', 'simply-static'), value: 'tiiny'},
                            {label: __('BunnyCDN', 'simply-static'), value: 'cdn'},
                            {label: __('Amazon AWS S3', 'simply-static'), value: 'aws-s3'},
                            {label: __('Digital Ocean Spaces', 'simply-static'), value: 'digitalocean'},
                        ]}
                        onChange={(method) => {
                            setDeliveryMethod(method);
                            updateSetting('delivery_method', method);
                        }}
                    />
                    :
                    <SelectControl
                        label={__('Deployment method', 'simply-static')}
                        value={deliveryMethod}
                        options={[
                            {label: __('ZIP Archive', 'simply-static'), value: 'zip'},
                            {label: __('Local Directory', 'simply-static'), value: 'local'},
                            {label: __('Simply CDN', 'simply-static'), value: 'simply-cdn'},
                        ]}
                        onChange={(method) => {
                            setDeliveryMethod(method);
                            updateSetting('delivery_method', method);
                        }}
                    />
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {deliveryMethod === 'local' &&
            <Card>
                <CardHeader>
                    <b>{__('Local Directory', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('This is the directory where your static files will be saved. The directory must exist and be writeable by the webserver', 'simply-static')}</p>

                    <TextControl
                        label={__('Path', 'simply-static')}
                        type={"text"}
                        placeholder={options.home_path + "public_static/"}
                        value={settings.local_dir}
                        onChange={(path) => {
                            updateSetting('local_dir', path);
                        }}
                    />
                    <ClipboardButton
                        variant="secondary"
                        text={options.home_path}
                        onCopy={() => setHasCopied(true)}
                        onFinishCopy={() => setHasCopied(false)}
                    >
                        {hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')}
                    </ClipboardButton>
                </CardBody>
            </Card>
        }
        {deliveryMethod === 'simply-cdn' &&
            <Card>
                <CardHeader>
                    <b>{__('Simply CDN', 'simply-static')}</b>
                </CardHeader>
                <CardBody>
                    <p>{__('The fast and easy way to bring your static website online. Simply CDN handles hosting, performance, security and form submissions for your static site.', 'simply-static')}</p>
                    <TextControl
                        label={__('Security Token', 'simply-static')}
                        type={"text"}
                        help={__('Copy and paste the Security Token from your project.', 'simply-static')}
                        value={settings.ssh_security_token}
                        onChange={(token) => {
                            updateSetting('ssh_security_token', token);
                        }}
                    />
                    {settings.ssh_security_token &&
                        <>
                            <TextControl
                                label={__('404 Path', 'simply-static')}
                                type={"text"}
                                help={__('Relative path to your custom 404 error page.', 'simply-static')}
                                value={settings.ssh_404_path}
                                onChange={(errorPath) => {
                                    updateSetting('ssh_404_path', errorPath);
                                }}
                            />
                            <ToggleControl
                                label={__('Use Forms?', 'simply-static')}
                                help={
                                    useForms
                                        ? __('Proxy form submissions through Simply CDN.', 'simply-static')
                                        : __('Don\'t proxy form submissions through Simply CDN.', 'simply-static')
                                }
                                checked={useForms}
                                onChange={(value) => {
                                    setUseForms(value);
                                    updateSetting('ssh_use_forms', value);
                                }}
                            />
                        </>
                    }
                </CardBody>
            </Card>
        }
        {'pro' === options.plan &&
            <>
                {deliveryMethod === 'github' &&
                    <Card>
                        <CardHeader>
                            <b>{__('GitHub', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <p>{__('GitHub enables you to export your static website to one of the common static hosting providers like Netlify, Cloudflare Pages or GitHub Pages.', 'simply-static')}</p>

                            <SelectControl
                                label={__('Account Type', 'simply-static')}
                                value={githubAccountType}
                                help={__('Depending on the account type the settings fields will change.', 'simply-static')}
                                options={[
                                    {label: __('Personal', 'simply-static'), value: 'personal'},
                                    {label: __('Organization', 'simply-static'), value: 'organization'},
                                ]}
                                onChange={(type) => {
                                    setGithubAccountType(type);
                                    updateSetting('github_account_type', type);
                                }}
                            />

                            {githubAccountType === 'organization' ?
                                <TextControl
                                    label={__('Organization', 'simply-static')}
                                    type={"text"}
                                    help={__('Enter the name of your organization.', 'simply-static')}
                                    value={settings.github_user}
                                    onChange={(organization) => {
                                        updateSetting('github_user', organization);
                                    }}
                                />
                                :
                                <TextControl
                                    label={__('Username', 'simply-static')}
                                    type={"text"}
                                    help={__('Enter your GitHub username.', 'simply-static')}
                                    value={settings.github_user}
                                    onChange={(name) => {
                                        updateSetting('github_user', name);
                                    }}
                                />
                            }
                            <TextControl
                                label={__('E-Mail', 'simply-static')}
                                type={"email"}
                                help={__('Enter your GitHub email address. This will be used to commit files to your repository.', 'simply-static')}
                                value={settings.github_email}
                                onChange={(email) => {
                                    updateSetting('github_email', email);
                                }}
                            />

                            <TextControl
                                label={__('Personal Access Token', 'simply-static')}
                                type={"password"}
                                help={__('You need a personal access token from GitHub.', 'simply-static')}
                                value={settings.github_personal_access_token}
                                onChange={(token) => {
                                    updateSetting('github_personal_access_token', token);
                                }}
                            />

                            <TextControl
                                label={__('Repository', 'simply-static')}
                                type={"text"}
                                help={__('Enter a name for your repository. This should be lowercase and without any spaces or special characters.', 'simply-static')}
                                value={settings.github_repository}
                                onChange={(repository) => {
                                    updateSetting('github_repository', repository);
                                }}
                            />
                            {githubAccountType === 'organization' &&
                                <>
                                    <Notice status="warning" isDismissible={false}>
                                        <p>{__('You need to create the repository manually within your organization before connecting it.', 'simply-static')}</p>
                                    </Notice>
                                    <Spacer margin={5}/>
                                </>
                            }
                            <SelectControl
                                label={__('Visiblity', 'simply-static')}
                                value={githubVisibility}
                                help={__('Decide if you want to make your repository public or private.', 'simply-static')}
                                options={[
                                    {label: __('Public', 'simply-static'), value: 'public'},
                                    {label: __('Private', 'simply-static'), value: 'private'},
                                ]}
                                onChange={(visibility) => {
                                    setGithubVisibility(visibility);
                                    updateSetting('github_repository_visibility', visibility);
                                }}
                            />

                            <TextControl
                                label={__('Branch', 'simply-static')}
                                type={settings.github_branch}
                                placeholder={"main"}
                                help={__('Simply Static automatically uses "main" as branch. You may want to modify that for example to gh-pages. for GitHub Pages.', 'simply-static')}
                                value={settings.github_branch}
                                onChange={(branch) => {
                                    updateSetting('github_branch', branch);
                                }}
                            />

                            <TextControl
                                label={__('Webhook URL', 'simply-static')}
                                type={"url"}
                                help={__('Enter your Webhook URL here and Simply Static will send a POST request after all files are commited to GitHub.', 'simply-static')}
                                value={settings.github_webhook_url}
                                onChange={(webhook) => {
                                    updateSetting('github_webhook_url', webhook);
                                }}
                            />
                        </CardBody>
                    </Card>
                }
                <Spacer margin={5}/>
                {deliveryMethod === 'tiiny' &&
                    <Card>
                        <CardHeader>
                            <b>{__('Tiiny.host', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>

                            <p>{__('Deploying to Tiiny.host is the easiest and fastest deployment option available in Simply Static Pro.', 'simply-static')}</p>

                            <TextControl
                                disabled
                                label={__('E-Mail', 'simply-static')}
                                type={"text"}
                                help={__('This field is auto-filled with the e-mail address used for activating Simply Static Pro. An account will be created automatically on your first deployment.', 'simply-static')}
                                value={options.admin_email}
                            />

                            <TextControl
                                label={__('Subdomain', 'simply-static')}
                                type={"text"}
                                help={__('That\'s the part before your TLD. Your full URL is the combination of the subdomain plus the domain suffix.', 'simply-static')}
                                value={settings.tiiny_subdomain}
                                onChange={(subdomain) => {
                                    updateSetting('tiiny_subdomain', subdomain);
                                }}
                            />

                            <TextControl
                                label={__('Domain Suffix', 'simply-static')}
                                type={"text"}
                                help={__('This defaults to tiiny.site. If you have a custom domain configured in Tiiny.host, you can also use  that one.', 'simply-static')}
                                value={settings.tiiny_domain_suffix}
                                onChange={(suffix) => {
                                    updateSetting('tiiny_domain_suffix', suffix);
                                }}
                            />

                            <TextControl
                                label={__('Password Protection', 'simply-static')}
                                type={"password"}
                                help={__('Adding a password will activate password protection on your static site. The website is only visible with the password.', 'simply-static')}
                                value={settings.tiiny_password}
                                onChange={(password) => {
                                    updateSetting('tiiny_password', password);
                                }}
                            />
                        </CardBody>
                    </Card>
                }
                <Spacer margin={5}/>
                {deliveryMethod === 'cdn' &&
                    <Card>
                        <CardHeader>
                            <b>{__('Bunny CDN', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>

                            <p>{__('Bunny CDN is a fast and reliable CDN provider that you can run your static website on.', 'simply-static')}</p>

                            <TextControl
                                label={__('Bunny CDN API Key', 'simply-static')}
                                type={"password"}
                                help={__('Enter your API Key from Bunny CDN. You can find your API-Key as described here.', 'simply-static')}
                                value={settings.cdn_api_key}
                                onChange={(api_key) => {
                                    updateSetting('cdn_api_key', api_key);
                                }}
                            />

                            <TextControl
                                label={__('Storage Host', 'simply-static')}
                                type={"text"}
                                help={__('Depending on your location, you have a different storage host. You find out which URL to use here.', 'simply-static')}
                                value={settings.cdn_storage_host}
                                onChange={(storage_host) => {
                                    updateSetting('cdn_storage_host', storage_host);
                                }}
                            />

                            <TextControl
                                label={__('Bunny CDN Access Key', 'simply-static')}
                                type={"password"}
                                help={__('Enter your Acess Key from Bunny CDN. You will find it within your storage zone setttings within FTP & API Access -> Password.', 'simply-static')}
                                value={settings.cdn_access_key}
                                onChange={(access_key) => {
                                    updateSetting('cdn_access_key', access_key);
                                }}
                            />

                            <TextControl
                                label={__('Pull Zone', 'simply-static')}
                                type={"text"}
                                help={__('A pull zone is the connection of your CDN to the internet. Simply Static will try to find an existing pull zone with the provided name, if there is none it creates a new pull zone.', 'simply-static')}
                                value={settings.cdn_pull_zone}
                                onChange={(pull_zone) => {
                                    updateSetting('cdn_pull_zone', pull_zone);
                                }}
                            />

                            <TextControl
                                label={__('Storage Zone', 'simply-static')}
                                type={"text"}
                                help={__('A storage zone contains your static files. Simply Static will try to find an existing storage zone with the provided name, if there is none it creates a new storage zone.', 'simply-static')}
                                value={settings.cdn_storage_zone}
                                onChange={(storage_zone) => {
                                    updateSetting('cdn_storage_zone', storage_zone);
                                }}
                            />

                            <TextControl
                                label={__('Subdirectory', 'simply-static')}
                                type={"text"}
                                placeholder={'/subdirectory/'}
                                help={__('If you want to transfer the files to a specific subdirectory on your storage zone add the name of that directory here.', 'simply-static')}
                                value={settings.cdn_directory}
                                onChange={(directory) => {
                                    updateSetting('cdn_directory', directory);
                                }}
                            />

                            <TextControl
                                label={__('404 Page', 'simply-static')}
                                type={"text"}
                                placeholder={'/custom-404/'}
                                help={__('Relative path to your custom 404 page.', 'simply-static')}
                                value={settings.cdn_404}
                                onChange={(error_page) => {
                                    updateSetting('cdn_404', error_page);
                                }}
                            />
                        </CardBody>
                    </Card>
                }
                <Spacer margin={5}/>
                {deliveryMethod === 'aws-s3' &&
                    <Card>
                        <CardHeader>
                            <b>{__('Amazon AWS S3', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <TextControl
                                label={__('Access Key ID', 'simply-static')}
                                type={"text"}
                                help={__('Enter your Access Key from AWS.', 'simply-static')}
                                value={settings.aws_access_key}
                                onChange={(access_key) => {
                                    updateSetting('aws_access_key', access_key);
                                }}
                            />

                            <TextControl
                                label={__('Secret Access Key', 'simply-static')}
                                type={"password"}
                                help={__('Enter your Secret Key from AWS.', 'simply-static')}
                                value={settings.aws_access_secret}
                                onChange={(secret) => {
                                    updateSetting('aws_access_secret', secret);
                                }}
                            />

                            <SelectControl
                                label={__('Region', 'simply-static')}
                                value={region}
                                options={[
                                    {label: __('US East (Ohio)', 'simply-static'), value: 'us-east-2'},
                                    {label: __('US East (N. Virginia)', 'simply-static'), value: 'us-east-1'},
                                    {label: __('US West (N. California)', 'simply-static'), value: 'us-west-1'},
                                    {label: __('US West (Oregon)', 'simply-static'), value: 'us-west-2'},
                                    {label: __('Africa (Cape Town)', 'simply-static'), value: 'af-south-1'},
                                    {label: __('Asia Pacific (Hong Kong)', 'simply-static'), value: 'ap-east-1'},
                                    {label: __('Asia Pacific (Hyderabad)', 'simply-static'), value: 'ap-south-2'},
                                    {label: __('Asia Pacific (Jakarta)', 'simply-static'), value: 'ap-southeast-3'},
                                    {label: __('Asia Pacific (Melbourne)', 'simply-static'), value: 'ap-southeast-4'},
                                    {label: __('Asia Pacific (Mumbai)', 'simply-static'), value: 'ap-south-1'},
                                    {label: __('Asia Pacific (Osaka)', 'simply-static'), value: 'ap-northeast-3'},
                                    {label: __('Asia Pacific (Seoul)', 'simply-static'), value: 'ap-northeast-2'},
                                    {label: __('Asia Pacific (Singapore)', 'simply-static'), value: 'ap-southeast-1'},
                                    {label: __('Asia Pacific (Sydney)', 'simply-static'), value: 'ap-southeast-2'},
                                    {label: __('Asia Pacific (Tokyo)', 'simply-static'), value: 'ap-northeast-1'},
                                    {label: __('Canada (Central)', 'simply-static'), value: 'ca-central-1'},
                                    {label: __('Europe (Frankfurt)', 'simply-static'), value: 'eu-central-1'},
                                    {label: __('Europe (Ireland)', 'simply-static'), value: 'eu-west-1'},
                                    {label: __('Europe (London)', 'simply-static'), value: 'eu-west-2'},
                                    {label: __('Europe (Milan)', 'simply-static'), value: 'eu-south-1'},
                                    {label: __('Europe (Paris)', 'simply-static'), value: 'eu-west-3'},
                                    {label: __('Europe (Spain)', 'simply-static'), value: 'eu-south-2'},
                                    {label: __('Europe (Stockholm)', 'simply-static'), value: 'eu-north-1'},
                                    {label: __('Europe (Zurich)', 'simply-static'), value: 'eu-central-2'},
                                    {label: __('Middle East (Bahrain)', 'simply-static'), value: 'me-south-1'},
                                    {label: __('Middle East (UAE)', 'simply-static'), value: 'me-central-1'},
                                    {label: __('South America (São Paulo)', 'simply-static'), value: 'sa-east-1'},
                                    {label: __('AWS GovCloud (US-East)', 'simply-static'), value: 'us-gov-east-1'},
                                    {label: __('AWS GovCloud (US-West)', 'simply-static'), value: 'us-gov-west-1'}
                                ]}
                                onChange={(region) => {
                                    setRegion(region);
                                    updateSetting('aws_region', region);
                                }}
                            />
                            <TextControl
                                label={__('Bucket', 'simply-static')}
                                type={"text"}
                                help={__('Add the name of your bucket here.', 'simply-static')}
                                value={settings.aws_bucket}
                                onChange={(bucket) => {
                                    updateSetting('aws_bucket', bucket);
                                }}
                            />

                            <ToggleControl
                                label={__('Empty bucket before new export?', 'simply-static')}
                                help={
                                    emptyBucketBeforeExport
                                        ? __('Clear bucket before new export.', 'simply-static')
                                        : __('Don\'t clear bucket before new export.', 'simply-static')
                                }
                                checked={emptyBucketBeforeExport}
                                onChange={(value) => {
                                    setEmptyBucketBeforeExport(value);
                                    updateSetting('aws_empty', value);
                                }}
                            />
                        </CardBody>
                    </Card>
                }
                <Spacer margin={5}/>
                {deliveryMethod === 'digitalocean' &&
                    <Card>
                        <CardHeader>
                            <b>{__('Digital Ocean Spaces', 'simply-static')}</b>
                        </CardHeader>
                        <CardBody>
                            <TextControl
                                label={__('Spaces Key', 'simply-static')}
                                type={"text"}
                                help={__('Enter your Spaces Key from Digital Ocean.', 'simply-static')}
                                value={settings.digitalocean_key}
                                onChange={(api_key) => {
                                    updateSetting('digitalocean_key', api_key);
                                }}
                            />

                            <TextControl
                                label={__('Secret', 'simply-static')}
                                type={"password"}
                                help={__('Enter your Spaces Secret from Digital Ocean.', 'simply-static')}
                                value={settings.digitalocean_secret}
                                onChange={(secret) => {
                                    updateSetting('digitalocean_secret', secret);
                                }}
                            />

                            <TextControl
                                label={__('Bucket Name', 'simply-static')}
                                help={__('The bucket name for your space.', 'simply-static')}
                                type={"text"}
                                placeholder={"my-bucket"}
                                value={settings.digitalocean_bucket}
                                onChange={(bucket) => {
                                    updateSetting('digitalocean_bucket', bucket);
                                }}
                            />

                            <TextControl
                                label={__('Region', 'simply-static')}
                                type={"text"}
                                help={__('The region for your space.', 'simply-static')}
                                placeholder={"ams3"}
                                value={settings.digitalocean_region}
                                onChange={(region) => {
                                    updateSetting('digitalocean_region', region);
                                }}
                            />
                            <p>
                                {settings.digitalocean_bucket && settings.digitalocean_region &&
                                    <Notice status="warning" isDismissible={false}>
                                        {__('Your endpoint will be', 'simply-static')} :
                                        {'https://' + settings.digitalocean_bucket + '.' + settings.digitalocean_region + '.digitaloceanspaces.com'}
                                    </Notice>
                                }
                            </p>
                        </CardBody>
                    </Card>
                }
            </>
        }
        <Spacer margin={5}/>
        {settingsSaved &&
            <>
                <Animate type="slide-in" options={{origin: 'top'}}>
                    {() => (
                        <Notice status="success" isDismissible={false}>
                            <p>
                                {__('Settings saved successfully.', 'simply-static')}
                            </p>
                        </Notice>
                    )}
                </Animate>
                <Spacer margin={5}/>
            </>
        }
        <div className={"save-settings"}>
            <Button onClick={setSavingSettings}
                    variant="primary">{__('Save Settings', 'simply-static')}</Button>
        </div>
    </div>)
}

export default DeploymentSettings;