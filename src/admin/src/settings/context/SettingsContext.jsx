import {useState, createContext, useEffect} from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;

export const SettingsContext = createContext();

function SettingsContextProvider(props) {
    const defaultSettings = {
        'destination_scheme': 'https://',
        'destination_host': '',
        'temp_files_dir': '',
        'additional_urls': '',
        'additional_files': '',
        'urls_to_exclude': 'wp-json\n.php',
        'delivery_method': 'zip',
        'local_dir': '',
        'relative_path': '',
        'destination_url_type': 'relative',
        'debugging_mode': true,
        'server_cron': false,
        'whitelist_plugins': '',
        'http_basic_auth_username': '',
        'http_basic_auth_password': '',
        'http_basic_auth_on': false,
        'origin_url': '',
        'version': options.version,
        'force_replace_url': true,
        'clear_directory_before_export': false,
        'iframe_urls': '',
        'iframe_custom_css': '',
        'tiiny_email': options.admin_email,
        'tiiny_subdomain': '',
        'tiiny_domain_suffix': 'tiiny.site',
        'tiiny_password': '',
        'cdn_api_key': '',
        'cdn_storage_host': 'storage.bunnycdn.com',
        'cdn_access_key': '',
        'cdn_pull_zone': '',
        'cdn_storage_zone': '',
        'cdn_directory': '',
        'github_account_type': 'personal',
        'github_user': '',
        'github_email': '',
        'github_personal_access_token': '',
        'github_repository': '',
        'github_repository_visibility': 'public',
        'github_branch': 'main',
        'github_webhook_url': '',
        'github_folder_path': '',
        'github_throttle_requests': false,
        'aws_region': 'us-east-2',
        'aws_access_key': '',
        'aws_access_secret': '',
        'aws_bucket': '',
        'aws_subdirectory': '',
        'aws_distribution_id': '',
        'aws_empty': false,
        's3_access_key': '',
        's3_base_url': '',
        's3_access_secret': '',
        's3_bucket': '',
        's3_subdirectory': '',
        'fix_cors': 'allowed_http_origins',
        'static_url': '',
        'use_forms': false,
        'use_comments': false,
        'comment_redirect': '',
        'use_search': false,
        'search_type': 'fuse',
        'search_index_title': 'title',
        'search_index_content': 'body',
        'search_index_excerpt': '.entry-content',
        'search_excludable': '',
        'search_metadata': '',
        'fuse_selector': '.search-field',
        'algolia_app_id': '',
        'algolia_admin_api_key': '',
        'algolia_search_api_key': '',
        'algolia_index': 'simply_static',
        'algolia_selector': '.search-field',
        'use_minify': false,
        'minify_html': false,
        'minify_html_leave_quotes': false,
        'minify_css': false,
        'minify_inline_css': false,
        'minify_css_exclude': '',
        'minify_js_exclude': '',
        'minify_js': false,
        'minify_inline_js': false,
        'generate_404': true,
        'wp_content_folder': '',
        'wp_includes_folder': '',
        'wp_uploads_folder': '',
        'wp_plugins_folder': '',
        'wp_themes_folder': '',
        'theme_style_name': 'style',
        'rename_plugin_folders': false,
        'author_url': '',
        'hide_rest_api': false,
        'hide_style_id': false,
        'hide_comments': false,
        'hide_version': false,
        'hide_generator': false,
        'hide_prefetch': false,
        'hide_rsd': false,
        'hide_emotes': false,
        'disable_xmlrpc': false,
        'disable_embed': false,
        'disable_db_debug': false,
        'disable_wlw_manifest': false,
        'incremental_export': false,
        'sftp_host': '',
        'sftp_user': '',
        'sftp_pass': '',
        'sftp_private_key': '',
        'sftp_folder': '',
        'sftp_port': 22,
        'shortpixel_enabled': false,
        'shortpixel_api_key': '',
        'shortpixel_backup_enabled': false,
        'integrations': false // Will be array when saved.
    }
    const [isRunning, setIsRunning] = useState(false);
    const [settingsSaved, setSettingsSaved] = useState(false);
    const [settings, setSettings] = useState(defaultSettings);
    const [configs, setConfigs] = useState({});
    const [passedChecks, setPassedChecks] = useState('yes');
    const [blogId, setBlogId] = useState(1);

    const getSettings = () => {
        apiFetch({path: '/simplystatic/v1/settings'}).then((options) => {
            setSettings(options);
        });
    }

    const saveSettings = () => {
        apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: settings,
        });
    }

    const resetSettings = () => {
        setSettings(defaultSettings);

        apiFetch({
            path: '/simplystatic/v1/settings/reset',
            method: 'POST',
            data: defaultSettings,
        });
    }

    const updateFromNetwork = (blogId) => {
        apiFetch({
            path: '/simplystatic/v1/update-from-network',
            method: 'POST',
            data: {
                'blog_id': blogId,
            }
        });
    }

    const checkIfRunning = () => {
        apiFetch({
            path: '/simplystatic/v1/is-running',
            method: 'GET'
        }).then(resp => {
            var json = JSON.parse(resp);
            setIsRunning(json.running);
        });
    }

    const importSettings = (newSettings) => {
        setSettings(newSettings);

        apiFetch({
            path: '/simplystatic/v1/settings',
            method: 'POST',
            data: newSettings,
        });
    }

    const migrateSettings = () => {
        apiFetch({
            path: '/simplystatic/v1/migrate',
            method: 'POST',
            migrate: true,
        });
    }

    const updateSetting = (key, value) => {
        setSettings({...settings, [key]: value});
    };

    const getStatus = () => {
        apiFetch({path: '/simplystatic/v1/system-status'}).then((configs) => {
            setConfigs(configs);

            getStatusPassed();

        });
    }

    const getStatusPassed = () => {
        apiFetch({path: '/simplystatic/v1/system-status/passed'}).then((result) => {
            let test = JSON.parse(result);
            setPassedChecks(test.passed);
        });
    }

    const resetDiagnostics = () => {
        apiFetch({
            path: '/simplystatic/v1/reset-diagnostics',
            method: 'POST',
        });
    }

    const isPro = () => {
        if (options.is_multisite) {
            return true;
        }

        if (options.connect) {
            return !!options.connect.is_connected;
        }

        return false;
    }

    const isIntegrationActive = (integration) => {
        let integrations = settings.integrations;

        if ( false === integrations ) {
           return false;
        }

        if ( integrations.indexOf(integration) >= 0 ) {
            return true;
        }

        return false;
    }

    useInterval(() => {
        checkIfRunning()
    }, isRunning ? 5000 : null);

    useEffect(() => {
        getSettings();
        getStatus();
        checkIfRunning();
        setBlogId(options.blog_id)
    }, []);

    return (
        <SettingsContext.Provider
            value={{
                settings,
                configs,
                passedChecks,
                settingsSaved,
                setSettingsSaved,
                updateSetting,
                setSettings,
                saveSettings,
                resetSettings,
                getSettings,
                updateFromNetwork,
                importSettings,
                migrateSettings,
                resetDiagnostics,
                isRunning,
                setIsRunning,
                blogId,
                setBlogId,
                isPro,
                isIntegrationActive
            }}
        >
            {props.children}
        </SettingsContext.Provider>
    );
}

export default SettingsContextProvider;
