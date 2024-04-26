import {useState, createContext, useEffect} from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';
import useInterval from "../../hooks/useInterval";

const {__} = wp.i18n;

export const SettingsContext = createContext();

function SettingsContextProvider(props) {

    const defaultSettings = {
        'destination_scheme': 'https://',
        'destination_host': '',
        'temp_files_dir': options.temp_files_dir,
        'additional_urls': '',
        'additional_files': '',
        'urls_to_exclude': 'wp-json\nwp-login.php',
        'delivery_method': 'zip',
        'local_dir': '',
        'relative_path': '',
        'destination_url_type': 'relative',
        'debugging_mode': true,
        'http_basic_auth_username': '',
        'http_basic_auth_password': '',
        'origin_url': '',
        'version': options.version,
        'force_replace_url': false,
        'clear_directory_before_export': false,
        'ssh_security_token': '',
        'ssh_use_forms': true,
        'iframe_urls': '',
        'ssh_404_page_id': '',
        'ssh_thank_you_page_id': '',
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
        'aws_region': 'us-east-2',
        'aws_access_key': '',
        'aws_access_secret': '',
        'aws_bucket': '',
        'aws_subdirectory': '',
        'aws_distribution_id': '',
        'aws_empty': false,
        'digitalocean_key': '',
        'digitalocean_secret': '',
        'digitalocean_bucket': '',
        'digitalocean_region': '',
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
        'minify_css': false,
        'minify_inline_css': false,
        'minify_js': false,
        'minify_inline_js': false,
        'generate_404': false,
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
        'sftp_folder': '',
        'sftp_port': 22
    }
    const [isRunning, setIsRunning] = useState(false);
    const [settingsSaved, setSettingsSaved] = useState(false);
    const [settings, setSettings] = useState(defaultSettings);
    const [configs, setConfigs] = useState({});
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

    const updateFromNetwork = ( blogId ) => {
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
        });
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
                settingsSaved,
                setSettingsSaved,
                updateSetting,
                setSettings,
                saveSettings,
                resetSettings,
                updateFromNetwork,
                importSettings,
                migrateSettings,
                isRunning,
                setIsRunning,
                blogId,
                setBlogId,
            }}
        >
            {props.children}
        </SettingsContext.Provider>
    );
}

export default SettingsContextProvider;
