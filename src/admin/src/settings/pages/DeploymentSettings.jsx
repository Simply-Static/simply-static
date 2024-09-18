import {
    Button,
    ClipboardButton,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, ToggleControl, FlexItem, Flex, ExternalLink, TextareaControl
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import apiFetch from "@wordpress/api-fetch";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;


function DeploymentSettings() {
    const {
        settings,
        updateSetting,
        saveSettings,
        settingsSaved,
        setSettingsSaved,
        isRunning,
        isPro
    } = useContext(SettingsContext);
    const [deliveryMethod, setDeliveryMethod] = useState('zip');
    const [clearDirectory, setClearDirectory] = useState(false);
    const [githubAccountType, setGithubAccountType] = useState('personal');
    const [githubVisibility, setGithubVisibility] = useState('private');
    const [emptyBucketBeforeExport, setEmptyBucketBeforeExport] = useState(false);
    const [throttleGitHubRequests, setThrottleGitHubRequests] = useState(false);
    const [region, setRegion] = useState('us-east-2');
    const [hasCopied, setHasCopied] = useState(false);
    const [pages, setPages] = useState(false);
    const [testDisabled, setTestDisabled] = useState(false);
    const [testRunning, setTestRunning] = useState(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);
        setTestDisabled(false);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.delivery_method) {
            setDeliveryMethod(settings.delivery_method);
        }

        if (settings.clear_directory_before_export) {
            setClearDirectory(settings.clear_directory_before_export);
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

        if (settings.github_throttle_requests) {
            setThrottleGitHubRequests(settings.github_throttle_requests);
        }

        if (settings.aws_empty) {
            setEmptyBucketBeforeExport(settings.aws_empty);
        }

        if (settings.aws_region) {
            setRegion(settings.aws_region);
        }

        // Get global page selection
        apiFetch({path: '/simplystatic/v1/pages'}).then((fetched_pages) => {
            let pages = fetched_pages;

            pages.unshift({label: __('No page selected', 'simply-static'), value: 0});
            setPages(pages);
        });

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Deployment Settings', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <p>{__('Choose from a variety of deployment methods. Depending on your selection we either provide a ZIP file, export to a local directory or send your files to a remote destination.', 'simply-static')}</p>
                <SelectControl
                    label={__('Deployment method', 'simply-static')}
                    value={deliveryMethod}
                    options={[
                        {label: __('ZIP Archive', 'simply-static'), value: 'zip'},
                        {label: __('Local Directory', 'simply-static'), value: 'local'},
                        {label: __('SFTP', 'simply-static'), value: 'sftp'},
                        {label: __('GitHub', 'simply-static'), value: 'github'},
                        {label: __('AWS S3', 'simply-static'), value: 'aws-s3'},
                        //{label: __('S3 Storage', 'simply-static'), value: 's3-storage'},
                        {label: __('Bunny CDN', 'simply-static'), value: 'cdn'},
                        {label: __('Tiiny.host', 'simply-static'), value: 'tiiny'}
                    ]}
                    onChange={(method) => {
                        setDeliveryMethod(method);
                        updateSetting('delivery_method', method);
                        setTestDisabled(true);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        {deliveryMethod === 'zip' &&
            <Card>
                <CardHeader>
                    <b>{__('ZIP', 'simply-static')}<HelperVideo
                        title={__('How to export a ZIP file', 'simply-static')}
                        videoUrl={'https://youtu.be/WHaFjDte6zI'}/></b>
                </CardHeader>
                <CardBody>
                    <p>
                        {__('Get a download link in the activity log once the static export has finished.', 'simply-static')}
                    </p>
                </CardBody>
            </Card>
        }
        <Spacer margin={5}/>
        {deliveryMethod === 'local' &&
            <Card>
                <CardHeader>
                    <b>{__('Local Directory', 'simply-static')}<HelperVideo
                        title={__('How to deploy to a local directory', 'simply-static')}
                        videoUrl={'https://youtu.be/ZRdXQB5slnY'}/></b>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={__('Path', 'simply-static')}
                        type={"text"}
                        help={__('This is the directory where your static files will be saved. The directory must exist and be writeable by the webserver', 'simply-static')}
                        placeholder={options.home_path + "public_static/"}
                        value={settings.local_dir}
                        onChange={(path) => {
                            updateSetting('local_dir', path);
                        }}
                    />
                    <p>
                        <ClipboardButton
                            variant="secondary"
                            text={options.home_path}
                            onCopy={() => setHasCopied(true)}
                            onFinishCopy={() => setHasCopied(false)}
                        >
                            {hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')}
                        </ClipboardButton>
                    </p>
                    <p>
                        <ToggleControl
                            label={__('Clear Local Directory', 'simply-static')}
                            help={
                                clearDirectory
                                    ? __('Clear local directory before running an export.', 'simply-static')
                                    : __('Don\'t clear local directory before running an export.', 'simply-static')
                            }
                            checked={clearDirectory}
                            onChange={(value) => {
                                setClearDirectory(value);
                                updateSetting('clear_directory_before_export', value);
                            }}
                        />
                    </p>
                </CardBody>
            </Card>
        }
        <>
            {deliveryMethod === 'github' &&
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('GitHub', 'simply-static')} <HelperVideo
                                    title={__('How to deploy to a GitHub (2/2)', 'simply-static')}
                                    videoUrl={'https://youtu.be/HqyTKwZuUAM'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <p>{__('GitHub enables you to export your static website to one of the common static hosting providers like Netlify, Cloudflare Pages or GitHub Pages.', 'simply-static')}</p>

                        <SelectControl
                            label={__('Account Type', 'simply-static')}
                            value={githubAccountType}
                            help={__('Depending on the account type the settings fields will change.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
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
                                disabled={('free' === options.plan || !isPro())}
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
                                disabled={('free' === options.plan || !isPro())}
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
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_email}
                            onChange={(email) => {
                                updateSetting('github_email', email);
                            }}
                        />

                        <TextControl
                            label={
                                <>
                                    {__('Personal Access Token', 'simply-static')}
                                    <HelperVideo
                                        title={__('How to prepare your GitHub account', 'simply-static')}
                                        videoUrl={'https://youtu.be/fjsJJmPeKuc'}/>
                                </>
                            }
                            type={"password"}
                            help={
                                <>
                                    {__('You need a personal access token from GitHub. Learn how to get one ', 'simply-static')}
                                    <a href={"https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-personal-access-token-classic"}
                                       target={"_blank"}>{__('here', 'simply-static')}</a>
                                </>
                            }
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_personal_access_token}
                            onChange={(token) => {
                                updateSetting('github_personal_access_token', token);
                            }}
                        />

                        <TextControl
                            label={__('Repository', 'simply-static')}
                            type={"text"}
                            help={__('Enter a name for your repository (lowercase without spaces or special characters).', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_repository}
                            onChange={(repository) => {
                                updateSetting('github_repository', repository);
                            }}
                        />
                        <Notice status="warning" isDismissible={false}>
                            <p>
                                {__('Ensure to create the repository and add a readme file to it before running an export as shown in the docs ', 'simply-static')}
                                <a href={"https://docs.simplystatic.com/article/33-set-up-the-github-integration/"}
                                   target={"_blank"}>{__('here', 'simply-static')}</a>
                            </p>
                        </Notice>
                        <Spacer margin={5}/>
                        <TextControl
                            label={__('Folder', 'simply-static')}
                            type={"text"}
                            help={__('Enter a relative path to a folder if you want to push files under it. Example: for github.com/USER/REPOSITORY/folder1, enter folder1', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_folder_path}
                            onChange={(repository) => {
                                updateSetting('github_folder_path', repository);
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
                            disabled={('free' === options.plan || !isPro())}
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
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_branch}
                            onChange={(branch) => {
                                updateSetting('github_branch', branch);
                            }}
                        />

                        <TextControl
                            label={__('Webhook URL', 'simply-static')}
                            type={"url"}
                            help={__('Enter your Webhook URL here and Simply Static will send a POST request after all files are commited to GitHub.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.github_webhook_url}
                            onChange={(webhook) => {
                                updateSetting('github_webhook_url', webhook);
                            }}
                        />
                        <ToggleControl
                            label={__('Throttle Requests', 'simply-static')}
                            help={__('Enable this option if you are experiencing issues with the GitHub API rate limit.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={throttleGitHubRequests}
                            onChange={(value) => {
                                setThrottleGitHubRequests(value);
                                updateSetting('github_throttle_requests', value);
                            }}
                        />
                    </CardBody>
                </Card>
            }
            <Spacer margin={5}/>
            {deliveryMethod === 'tiiny' &&
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('Tiiny.host', 'simply-static')} <HelperVideo
                                    title={__('How to deploy to Tiiny.host', 'simply-static')}
                                    videoUrl={'https://youtu.be/Y9EDaQkGl1Y'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>

                        <p>{__('Deploying to Tiiny.host is the easiest and fastest deployment option available in Simply Static Pro.', 'simply-static')}</p>

                        <TextControl
                            disabled
                            label={__('E-Mail', 'simply-static')}
                            type={"text"}
                            help={
                                <>
                                    {__('This field is auto-filled with the e-mail address used for activating Simply Static Pro.', 'simply-static')}<br></br>
                                    <b>{__('An account will be created automatically on your first deployment.', 'simply-static')}</b>
                                </>
                            }
                            value={options.admin_email}
                        />

                        <TextControl
                            label={__('Subdomain', 'simply-static')}
                            type={"text"}
                            help={__('That\'s the part before your TLD. Your full URL is the combination of the subdomain plus the domain suffix.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.tiiny_subdomain}
                            onChange={(subdomain) => {
                                updateSetting('tiiny_subdomain', subdomain);
                            }}
                        />

                        <TextControl
                            label={__('Domain Suffix', 'simply-static')}
                            type={"text"}
                            help={__('This defaults to tiiny.site. If you have a custom domain configured in Tiiny.host, you can also use  that one.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.tiiny_domain_suffix}
                            onChange={(suffix) => {
                                updateSetting('tiiny_domain_suffix', suffix);
                            }}
                        />

                        <TextControl
                            label={__('Password Protection', 'simply-static')}
                            type={"password"}
                            help={__('Adding a password will activate password protection on your static site. The website is only visible with the password.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
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
                        <Flex>
                            <FlexItem>
                                <b>{__('Bunny CDN', 'simply-static')}<HelperVideo
                                    title={__('How to deploy to Bunny CDN', 'simply-static')}
                                    videoUrl={'https://youtu.be/FBRg1BI41VY'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>

                        <p>{__('Bunny CDN is a fast and reliable CDN provider that you can run your static website on.', 'simply-static')}</p>
                        <TextControl
                            label={__('Bunny CDN API Key', 'simply-static')}
                            type={"password"}
                            help={
                                <>
                                    {__('Enter your API Key from Bunny CDN. You can find your API-Key as described ', 'simply-static')}
                                    <a href={"https://support.bunny.net/hc/en-us/articles/360012168840-Where-do-I-find-my-API-key"}
                                       target={"_blank"}>{__('here', 'simply-static')}</a>
                                </>
                            }
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.cdn_api_key}
                            onChange={(api_key) => {
                                updateSetting('cdn_api_key', api_key);
                            }}
                        />

                        <TextControl
                            label={__('Storage Host', 'simply-static')}
                            type={"text"}
                            help={
                                <>
                                    {__('Depending on your location, you have a different storage host. You find out which URL to use ', 'simply-static')}
                                    <a href={"https://docs.bunny.net/reference/storage-api#storage-endpoints"}
                                       target={"_blank"}>{__('here', 'simply-static')}</a>
                                </>
                            }
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.cdn_storage_host}
                            onChange={(storage_host) => {
                                updateSetting('cdn_storage_host', storage_host);
                            }}
                        />

                        <TextControl
                            label={__('Bunny CDN Access Key', 'simply-static')}
                            type={"password"}
                            help={__('Enter your Acess Key from Bunny CDN. You will find it within your storage zone setttings within FTP & API Access -> Password.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.cdn_access_key}
                            onChange={(access_key) => {
                                updateSetting('cdn_access_key', access_key);
                            }}
                        />

                        <TextControl
                            label={__('Pull Zone', 'simply-static')}
                            type={"text"}
                            help={__('A pull zone is the connection of your CDN to the internet. Simply Static will try to find an existing pull zone with the provided name, if there is none it creates a new pull zone.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.cdn_pull_zone}
                            onChange={(pull_zone) => {
                                updateSetting('cdn_pull_zone', pull_zone);
                            }}
                        />

                        <TextControl
                            label={__('Storage Zone', 'simply-static')}
                            type={"text"}
                            help={__('A storage zone contains your static files. Simply Static will try to find an existing storage zone with the provided name, if there is none it creates a new storage zone.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
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
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.cdn_directory}
                            onChange={(directory) => {
                                updateSetting('cdn_directory', directory);
                            }}
                        />
                    </CardBody>
                </Card>
            }
            <Spacer margin={5}/>
            {deliveryMethod === 'aws-s3' &&
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('Amazon AWS S3', 'simply-static')}<HelperVideo
                                    title={__('How to deploy to Amazon AWS S3', 'simply-static')}
                                    videoUrl={'https://youtu.be/rtn21J86Upc'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={__('Access Key ID', 'simply-static')}
                            type={"text"}
                            help={
                                <>
                                    {__('Enter your Access Key from AWS. Learn how to get one ', 'simply-static')}
                                    <a href={"https://docs.aws.amazon.com/en_en/IAM/latest/UserGuide/id_credentials_access-keys.html"}
                                       target={"_blank"}>{__('here', 'simply-static')}</a>
                                </>
                            }
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.aws_access_key}
                            onChange={(access_key) => {
                                updateSetting('aws_access_key', access_key);
                            }}
                        />

                        <TextControl
                            label={__('Secret Access Key', 'simply-static')}
                            type={"password"}
                            help={
                                <>
                                    {__('Enter your Secret Key from AWS. Learn how to get one ', 'simply-static')}
                                    <a href={"https://docs.aws.amazon.com/en_en/IAM/latest/UserGuide/id_credentials_access-keys.html"}
                                       target={"_blank"}>{__('here', 'simply-static')}</a>
                                </>
                            }
                            disabled={('free' === options.plan || !isPro())}
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
                            disabled={('free' === options.plan || !isPro())}
                            onChange={(region) => {
                                setRegion(region);
                                updateSetting('aws_region', region);
                            }}
                        />
                        <TextControl
                            label={__('Bucket', 'simply-static')}
                            type={"text"}
                            help={__('Add the name of your bucket here.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.aws_bucket}
                            onChange={(bucket) => {
                                updateSetting('aws_bucket', bucket);
                            }}
                        />

                        <TextControl
                            label={__('Subdirectory', 'simply-static')}
                            type={"text"}
                            help={__('Add an optional subdirectory for your bucket', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.aws_subdirectory}
                            onChange={(subdirectory) => {
                                updateSetting('aws_subdirectory', subdirectory);
                            }}
                        />

                        <TextControl
                            label={__('Cloudfront Distribution ID', 'simply-static')}
                            type={"text"}
                            help={__('We automatically invalidate the cache after each export.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.aws_distribution_id}
                            onChange={(distribution_id) => {
                                updateSetting('aws_distribution_id', distribution_id);
                            }}
                        />

                        <ToggleControl
                            label={__('Empty bucket before new export?', 'simply-static')}
                            help={
                                emptyBucketBeforeExport
                                    ? __('Clear bucket before new export.', 'simply-static')
                                    : __('Don\'t clear bucket before new export.', 'simply-static')
                            }
                            disabled={('free' === options.plan || !isPro())}
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
            {deliveryMethod === 's3-storage' &&
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('S3-compatible Storage', 'simply-static')}<HelperVideo
                                    title={__('How to deploy to S3 compatible storages?', 'simply-static')}
                                    videoUrl={'https://youtu.be/rtn21J86Upc'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={__('Access Key ID', 'simply-static')}
                            type={"text"}
                            help={__('Enter your Access Key from your S3 provider.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.s3_access_key}
                            onChange={(access_key) => {
                                updateSetting('s3_access_key', access_key);
                            }}
                        />

                        <TextControl
                            label={__('Secret Access Key', 'simply-static')}
                            type={"password"}
                            help= {__('Enter your Secret Key from S3 provider.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.s3_access_secret}
                            onChange={(secret) => {
                                updateSetting('s3_access_secret', secret);
                            }}
                        />
                        <TextControl
                            label={__('Base URL', 'simply-static')}
                            type={"url"}
                            help={__('Add the base URL of the S3 service.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.s3_base_url}
                            onChange={(baseUrl) => {
                                updateSetting('s3_base_url', baseUrl);
                            }}
                        />
                        <TextControl
                            label={__('Bucket', 'simply-static')}
                            type={"text"}
                            help={__('Add the name of your bucket here.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.s3_bucket}
                            onChange={(bucket) => {
                                updateSetting('s3_bucket', bucket);
                            }}
                        />

                        <TextControl
                            label={__('Subdirectory', 'simply-static')}
                            type={"text"}
                            help={__('Add an optional subdirectory for your bucket', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.s3_subdirectory}
                            onChange={(subdirectory) => {
                                updateSetting('s3_subdirectory', subdirectory);
                            }}
                        />
                    </CardBody>
                </Card>
            }
            <Spacer margin={5}/>
            {deliveryMethod === 'sftp' &&
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('SFTP', 'simply-static')} <HelperVideo
                                    title={__('How to deploy via SFTP', 'simply-static')}
                                    videoUrl={'https://youtu.be/6-QR9wZA3VQ'}/></b>
                            </FlexItem>
                            {('free' === options.plan || !isPro()) &&
                                <FlexItem>
                                    <ExternalLink
                                        href="https://simplystatic.com"> {__('Requires Simply Static Pro', 'simply-static')}</ExternalLink>
                                </FlexItem>
                            }
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={__('Host', 'simply-static')}
                            type={"text"}
                            help={__('Enter your SFTP host.', 'simply-static')}
                            value={settings.sftp_host}
                            disabled={('free' === options.plan || !isPro())}
                            onChange={(host) => {
                                updateSetting('sftp_host', host);
                            }}
                        />

                        <TextControl
                            label={__('Port', 'simply-static')}
                            type={"number"}
                            disabled={('free' === options.plan || !isPro())}
                            help={__('Enter your SFTP port.', 'simply-static')}
                            value={settings.sftp_port}
                            onChange={(port) => {
                                updateSetting('sftp_port', port);
                            }}
                        />

                        <TextControl
                            label={__('SFTP username', 'simply-static')}
                            help={__('Enter your SFTP username.', 'simply-static')}
                            type={"text"}
                            disabled={('free' === options.plan || !isPro())}
                            placeholder={"username"}
                            value={settings.sftp_user}
                            onChange={(user) => {
                                updateSetting('sftp_user', user);
                            }}
                        />

                        <TextControl
                            label={__('SFTP password', 'simply-static')}
                            type={"password"}
                            disabled={('free' === options.plan || !isPro())}
                            help={__('Enter your SFTP password.', 'simply-static')}
                            value={settings.sftp_pass}
                            onChange={(pass) => {
                                updateSetting('sftp_pass', pass);
                            }}
                        />

                        <TextareaControl
                            label={__('SFTP private key', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            help={__('Enter your SFTP private key if you want password.less upload and the server is configured to allow it. You can set it as a constant in wp-config.php by using define(\'SSP_SFTP_KEY\', \'YOUR_KEY\')', 'simply-static')}
                            value={settings.sftp_private_key}
                            onChange={(pass) => {
                                updateSetting('sftp_private_key', pass);
                            }}
                        />

                        <TextControl
                            label={__('SFTP folder', 'simply-static')}
                            help={__('Leave empty to upload to the default SFTP folder. Enter a folder path where you want the static files to be uploaded to (example: "uploads" will upload to uploads folder. "uploads/new-folder" will upload files to "new-folder"). ', 'simply-static')}
                            type={"text"}
                            disabled={('free' === options.plan || !isPro())}
                            placeholder={""}
                            value={settings.sftp_folder}
                            onChange={(folder) => {
                                updateSetting('sftp_folder', folder);
                            }}
                        />

                    </CardBody>
                </Card>
            }
        </>
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
            {'free' === options.plan ?
                <>
                    {deliveryMethod === 'zip' &&
                        <Button onClick={setSavingSettings}
                                variant="primary">{__('Save Settings', 'simply-static')}</Button>
                    }
                    {deliveryMethod === 'local' &&
                        <Button onClick={setSavingSettings}
                                variant="primary">{__('Save Settings', 'simply-static')}</Button>
                    }
                </>
                :
                <Button onClick={setSavingSettings} variant="primary">{__('Save Settings', 'simply-static')}</Button>
            }
            {'pro' === options.plan && isPro() &&
                <Button
                    disabled={isRunning || testDisabled || testRunning}
                    variant={'secondary'}

                    isBusy={isRunning || testRunning}
                    onClick={() => {
                        setTestRunning(true);
                        apiFetch({
                            path: '/simplystatic/v1/apply-single',
                            method: 'POST',
                        }).then(resp => {
                            if (parseInt(resp.status) === 404) {
                                alert(resp.message);
                            } else {
                                window.location.reload();
                            }

                        });
                    }}>
                    {testDisabled && __('Save settings to test', 'simply-static')}
                    {!testDisabled && __('Test Deployment', 'simply-static')}
                </Button>
            }
        </div>
    </div>)
}

export default DeploymentSettings;