import {
    Button,
    ClipboardButton,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl, ToggleControl,
    FormTokenField,
} from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import HelperVideo from "../components/HelperVideo";

const {__} = wp.i18n;

function GeneralSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved, isPro} = useContext(SettingsContext);

    const [replaceType, setReplaceType] = useState('relative');
    const [useForms, setUseForms] = useState(false);
    const [scheme, setScheme] = useState('https://');
    const [host, setHost] = useState('');
    const [path, setPath] = useState('/');
    const [forceURLReplacement, setForceURLReplacement] = useState(false);
    const [hasCopied, setHasCopied] = useState(false);
    const [generate404, setGenerate404] = useState(false);
    const [enableEnhancedCrawl, setEnableEnhancedCrawl] = useState(false);
    const [addFeeds, setAddFeeds] = useState(false);
    const [addRestApi, setAddRestApi] = useState(false);
    const [crawlers, setCrawlers] = useState([]);
    const [selectedCrawlers, setSelectedCrawlers] = useState([]);
    const [apiError, setApiError] = useState(null);
    const [postTypes, setPostTypes] = useState([]);
    const [selectedPostTypes, setSelectedPostTypes] = useState([]);
    const [postTypesApiError, setPostTypesApiError] = useState(null);
    const [pages, setPages] = useState([]);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    // Function to fetch crawlers from API
    const fetchCrawlers = () => {
        // Reset API error
        setApiError(null);

        apiFetch({ 
            path: '/simplystatic/v1/crawlers',
            // Use raw: true to get the raw response
            parse: true
        })
            .then(response => {

                // Check if response is a string (JSON) and try to parse it
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        setApiError('Error parsing API response: ' + e.message);
                        return;
                    }
                }

                if (response && response.data && response.data.length > 0) {
                    // Only show crawlers that can currently run (dependencies satisfied)
                    const crawlersData = response.data.filter(crawler => crawler.can_run);
                    setCrawlers(crawlersData);

                    // If no crawlers are selected or settings.crawlers is not an array, select defaults by active flag
                    // This ensures that if active_crawlers is empty (like when it's enabled for the first time),
                    // only crawlers active by default are added by default
                    if (!settings.crawlers || !Array.isArray(settings.crawlers) || settings.crawlers.length === 0) {
                        const defaultCrawlerIds = crawlersData.filter(crawler => crawler.active).map(crawler => crawler.id);
                        setSelectedCrawlers(defaultCrawlerIds);
                        updateSetting('crawlers', defaultCrawlerIds);
                    } else if (Array.isArray(settings.crawlers)) {

                        // Ensure all selected crawlers exist in the allowed crawlers list
                        const validCrawlerIds = settings.crawlers.filter(id => 
                            crawlersData.some(crawler => crawler.id === id)
                        );

                        // If no valid crawlers are selected, select all allowed by default
                        if (validCrawlerIds.length === 0) {
                            const allCrawlerIds = crawlersData.map(crawler => crawler.id);
                            setSelectedCrawlers(allCrawlerIds);
                            updateSetting('crawlers', allCrawlerIds);
                        } else {
                            setSelectedCrawlers(validCrawlerIds);
                        }
                    }
                } else {
                    setApiError('Invalid API response structure or empty crawlers array');
                }
            })
            .catch(error => {
                setApiError('Error fetching crawlers: ' + (error.message || 'Unknown error'));
            });
    };

    // Function to fetch post types from API
    const fetchPostTypes = () => {
        // Reset API error
        setPostTypesApiError(null);

        apiFetch({ 
            path: '/simplystatic/v1/post-types',
            parse: true
        })
            .then(response => {
                // Check if response is a string (JSON) and try to parse it
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        setPostTypesApiError('Error parsing API response: ' + e.message);
                        return;
                    }
                }

                if (response && response.data && response.data.length > 0) {
                    setPostTypes(response.data);

                    // If settings.post_types is not an array, initialize it as an empty array
                    if (!settings.post_types || !Array.isArray(settings.post_types)) {
                        const allPostTypeIds = response.data.map(postType => postType.name);
                        setSelectedPostTypes(allPostTypeIds);
                        updateSetting('post_types', allPostTypeIds);
                    } else if (Array.isArray(settings.post_types)) {
                        // Ensure all selected post types exist in the post types list
                        const validPostTypeIds = settings.post_types.filter(name => 
                            response.data.some(postType => postType.name === name)
                        );

                        // If no valid post types are selected, select all by default
                        if (validPostTypeIds.length === 0) {
                            const allPostTypeIds = response.data.map(postType => postType.name);
                            setSelectedPostTypes(allPostTypeIds);
                            updateSetting('post_types', allPostTypeIds);
                        } else {
                            setSelectedPostTypes(validPostTypeIds);
                        }
                    }
                } else {
                    setPostTypesApiError('Invalid API response structure or empty post types array');
                }
            })
            .catch(error => {
                setPostTypesApiError('Error fetching post types: ' + (error.message || 'Unknown error'));
            });
    };

    // Fetch crawlers and post types when component mounts
    // We intentionally use an empty dependency array to ensure this only runs once
    // when the component mounts, not on every settings change
    useEffect(() => {
        fetchCrawlers();
        fetchPostTypes();
        // Fetch pages for optional 404 selection
        apiFetch({ path: '/simplystatic/v1/pages' }).then((fetched_pages) => {
            let pages = fetched_pages || [];
            // Prepend default option
            pages.unshift({ label: __('No page selected', 'simply-static'), value: 0 });
            setPages(pages);
        }).catch(() => {
            setPages([]);
        });
    }, []);

    useEffect(() => {
        if (settings.destination_url_type) {
            setReplaceType(settings.destination_url_type);
        }

        if (settings.destination_scheme) {
            setScheme(settings.destination_scheme);
        }

        if (settings.destination_host) {
            setHost(settings.destination_host);
        }

        if (settings.relative_path) {
            setPath(settings.relative_path);
        }

        if (settings.use_forms || settings.use_comments) {
            setUseForms(true);
        }

        if (settings.force_replace_url) {
            setForceURLReplacement(settings.force_replace_url);
        }

        if (settings.generate_404) {
            setGenerate404(settings.generate_404);
        }

        if (settings.add_feeds) {
            setAddFeeds(settings.add_feeds);
        }

        if (settings.add_rest_api) {
            setAddRestApi(settings.add_rest_api);
        }

        if (settings.smart_crawl) {
            setEnableEnhancedCrawl(settings.smart_crawl);
        }

        if (settings.crawlers) {
            setSelectedCrawlers(settings.crawlers);
        }

        if (settings.post_types !== undefined) {
            setSelectedPostTypes(Array.isArray(settings.post_types) ? settings.post_types : []);
        }

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Replacing URLs', 'simply-static')}<HelperVideo
                    title={__('How to replace URLs', 'simply-static')}
                    videoUrl={'https://youtu.be/cb8jAMJlfGI'}/></b>
            </CardHeader>
            <CardBody>
                <p>{__('When exporting your static site, any links to your WordPress site will be replaced by one of the following: absolute URLs, relative URLs, or URLs contructed for offline use.', 'simply-static')}</p>
                <SelectControl
                    label={__('Replacing URLs', 'simply-static')}
                    value={replaceType}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    options={[
                        {label: __('Absolute URLs', 'simply-static'), value: 'absolute'},
                        {label: __('Relative Path', 'simply-static'), value: 'relative'},
                        {label: __('Offline Usage', 'simply-static'), value: 'offline'},
                    ]}
                    onChange={(type) => {
                        setReplaceType(type);
                        updateSetting('destination_url_type', type);
                    }}
                />
                {replaceType === 'absolute' &&
                    <>
                        <Flex>
                            <FlexItem style={{minWidth: "15%", marginTop: "15px"}}>
                                <SelectControl
                                    label={__('Scheme', 'simply-static')}
                                    value={scheme}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    options={[
                                        {label: 'https://', value: 'https://'},
                                        {label: 'http://', value: 'http://'},
                                        {label: '//', value: '//'},
                                    ]}
                                    onChange={(scheme) => {
                                        setScheme(scheme);
                                        updateSetting('destination_scheme', scheme);
                                    }}
                                />
                            </FlexItem>
                            <FlexItem style={{minWidth: "85%"}}>
                                <TextControl
                                    label={__('Host', 'simply-static')}
                                    type={"text"}
                                    placeholder={"example.com"}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    value={host}
                                    onChange={(host) => {
                                        setHost(host);
                                        updateSetting('destination_host', host);
                                    }}
                                />
                            </FlexItem>
                        </Flex>
                        <p>{__('Convert all URLs for your WordPress site to absolute URLs at the domain specified above.', 'simply-static')}</p>
                    </>
                }
                {replaceType === 'relative' &&
                    <>
                        <TextControl
                            label={__('Path', 'simply-static')}
                            type={"text"}
                            placeholder={"/"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            value={path}
                            onChange={(path) => {
                                setPath(path);
                                updateSetting('relative_path', path);
                            }}
                        />
                        <p>
                            {__('Convert all URLs for your WordPress site to relative URLs that will work at any domain.', 'simply-static')}<br></br>
                            {__('Optionally specify a path above if you intend to place the files in a subdirectory.', 'simply-static')}
                        </p>
                        <Notice status={"warning"} isDismissible={false}>
                            <b>{__('Example', 'simply-static')}: </b>
                            {__('enter /path above if you wanted to serve your files at www.example.com/path/', 'simply-static')}
                        </Notice>
                    </>
                }
                {replaceType === 'offline' &&
                    <p>{__('Convert all URLs for your WordPress site so that you can browse the site locally on your own computer without hosting it on a web server.', 'simply-static')}</p>
                }
                {!useForms &&
                    <ToggleControl
                        label={__('Force URL replacements', 'simply-static')}
                        __nextHasNoMarginBottom
                        help={
                            forceURLReplacement
                                ? __('Replace all occurrences of the WordPress URL with the static URL (includes inline CSS and JS).', 'simply-static')
                                : __('Replace only occurrences of the WordPress URL that match our tag list.', 'simply-static')
                        }
                        checked={forceURLReplacement}
                        onChange={(value) => {
                            setForceURLReplacement(value);
                            updateSetting('force_replace_url', value);
                        }}
                    />
                }
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Enhanced Crawl', 'simply-static')}<HelperVideo
                    title={__('How Enhanced Crawl improves your static exports', 'simply-static')}
                    videoUrl={'https://youtu.be/QfKxeQ1w7tU'}/></b>
            </CardHeader>
            <CardBody>
                <p>{__('Enhanced Crawl uses native WordPress functions to find all pages and files when running a static export.', 'simply-static')}</p>
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={
                        <>
                            {__('Enable Enhanced Crawl', 'simply-static')}
                        </>
                    }
                    help={
                        enableEnhancedCrawl
                            ? __('Find pages and files via Enhanced Crawl.', 'simply-static')
                            : __('Don\'t find pages and files via Enhanced Crawl.', 'simply-static')
                    }
                    checked={enableEnhancedCrawl}
                    onChange={(value) => {
                        setEnableEnhancedCrawl(value);
                        updateSetting('smart_crawl', value);
                    }}
                />

                {enableEnhancedCrawl && (
                    <>
                        <Spacer margin={2} />
                        {apiError && (
                            <>
                                <Notice status="error" isDismissible={false}>
                                    {__('Error loading crawlers: ', 'simply-static')} {apiError}
                                </Notice>
                                <Spacer margin={2} />
                            </>
                        )}
                        {crawlers.length > 0 ? (
                            <>
                                <FormTokenField
                                    label={__('Active Crawlers', 'simply-static')}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    value={selectedCrawlers.map(id => {
                                        const crawler = crawlers.find(c => c.id === id);
                                        return crawler ? crawler.name : id;
                                    })}
                                    suggestions={crawlers.map(crawler => crawler.name)}
                                    onChange={(value) => {
                                        // Convert names to IDs for storage, and only allow known/available crawlers
                                        const selectedIds = value.map(name => {
                                            // First try to find an exact match
                                            let crawler = crawlers.find(c => c.name === name);

                                            // If no exact match, try case-insensitive match
                                            if (!crawler) {
                                                crawler = crawlers.find(c => 
                                                    c.name.toLowerCase() === (name || '').toLowerCase()
                                                );
                                            }

                                            // If still no match, check if it's already an ID
                                            if (!crawler) {
                                                crawler = crawlers.find(c => c.id === name);
                                            }

                                            return crawler ? crawler.id : null;
                                        }).filter(id => !!id && crawlers.some(c => c.id === id));
                                        setSelectedCrawlers(selectedIds);
                                        updateSetting('crawlers', selectedIds);
                                    }}
                                    help={__('Select which crawlers to activate. If none selected, all crawlers will be active by default.', 'simply-static')}
                                    tokenizeOnSpace={false}
                                    __experimentalExpandOnFocus={true}
                                    __experimentalShowHowTo={false}
                                    maxSuggestions={100}
                                    className="horizontal-token-field"
                                />
                                <Spacer margin={2} />
                                {/* Show post types selection only when Post Type URLs crawler is active */}
                                {selectedCrawlers.includes('post_type') && (
                                    <>
                                        <Spacer margin={2} />
                                        {postTypesApiError && (
                                            <>
                                                <Notice status="error" isDismissible={false}>
                                                    {__('Error loading post types: ', 'simply-static')} {postTypesApiError}
                                                </Notice>
                                                <Spacer margin={2} />
                                            </>
                                        )}
                                        {postTypes.length > 0 ? (
                                            <>
                                                <FormTokenField
                                                    label={__('Post Types to Include', 'simply-static')}
                                                    __next40pxDefaultSize
                                                    __nextHasNoMarginBottom
                                                    value={Array.isArray(selectedPostTypes) ? selectedPostTypes.map(name => {
                                                        const postType = postTypes.find(pt => pt.name === name);
                                                        return postType ? postType.label : name;
                                                    }) : []}
                                                    suggestions={postTypes.map(postType => postType.label)}
                                                    onChange={(value) => {
                                                        // Convert labels to names for storage
                                                        const selectedNames = value.map(label => {
                                                            // First try to find an exact match
                                                            let postType = postTypes.find(pt => pt.label === label);

                                                            // If no exact match, try case-insensitive match
                                                            if (!postType) {
                                                                postType = postTypes.find(pt => 
                                                                    pt.label.toLowerCase() === label.toLowerCase()
                                                                );
                                                            }

                                                            // If still no match, check if it's already a name
                                                            if (!postType) {
                                                                postType = postTypes.find(pt => pt.name === label);
                                                            }

                                                            return postType ? postType.name : label;
                                                        });
                                                        setSelectedPostTypes(selectedNames);
                                                        updateSetting('post_types', selectedNames);
                                                    }}
                                                    help={__('Select which post types to include in the static export. If you remove all selections, all post types will be included by default.', 'simply-static')}
                                                    tokenizeOnSpace={false}
                                                    __experimentalExpandOnFocus={true}
                                                    __experimentalShowHowTo={false}
                                                    maxSuggestions={100}
                                                    className="horizontal-token-field"
                                                />
                                                <Spacer margin={2} />
                                            </>
                                        ) : (
                                            <p>{__('Loading post types...', 'simply-static')}</p>
                                        )}
                                    </>
                                )}
                                <div className="crawler-descriptions">
                                    {crawlers.map(crawler => (
                                        <div key={crawler.id} className="crawler-description">
                                            <Flex>
                                                <FlexItem className={"crawler-name"}>
                                                    <strong>{crawler.name}:</strong>
                                                </FlexItem>
                                                <FlexItem>
                                                    {crawler.description}
                                                </FlexItem>
                                            </Flex>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <p>{__('Loading crawlers...', 'simply-static')}</p>
                        )}
                    </>
                )}
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Include', 'simply-static')}<HelperVideo
                    title={__('Include & Exclude files and pages', 'simply-static')}
                    videoUrl={'https://youtu.be/voAHfwVMLi8'}/></b>
            </CardHeader>
            <CardBody>
                <TextareaControl
                    label={__('Additional URLs', 'simply-static')}
                    placeholder={options.home + "/hidden-page/"}
                    __nextHasNoMarginBottom
                    help={__('If you want to create static copies of pages or files that aren\'t linked to, add the URLs here (one per line).', 'simply-static')}
                    value={settings.additional_urls}
                    onChange={(value) => {
                        updateSetting('additional_urls', value);
                    }}
                />
                <TextareaControl
                    label={__('Additional Files and Directories', 'simply-static')}
                    placeholder={options.home_path + "additional-directory/\n" + options.home_path + "additional-file.html"}
                    __nextHasNoMarginBottom
                    help={__('Sometimes you may want to include additional files (such as files referenced via AJAX) or directories. Add the paths to those files or directories here (one per line).', 'simply-static')}
                    value={settings.additional_files}
                    onChange={(value) => {
                        updateSetting('additional_files', value);
                    }}
                />
                <Button
                    variant="secondary"
                    onClick={() => {
                        try {
                            navigator.clipboard.writeText(options.home_path);
                            setHasCopied(true);
                            setTimeout(() => setHasCopied(false), 1500);
                        } catch (e) {
                            console.error('Clipboard copy failed', e);
                        }
                    }}
                >
                    {hasCopied ? __('Copied home path', 'simply-static') : __('Copy home path', 'simply-static')}
                </Button>
                <Spacer margin={5}/>

                <ToggleControl
                    __nextHasNoMarginBottom
                    label={
                        <>
                            {__('Generate 404 Page?', 'simply-static')}
                            <HelperVideo title={__('How to manage 404 pages?', 'simply-static')}
                                         videoUrl={'https://youtu.be/dnRtuQrXG-k'}/>
                        </>
                    }
                    help={
                        generate404
                            ? __('Generate a 404 page based on your theme template.', 'simply-static')
                            : __('Don\'t generate a 404 page.', 'simply-static')
                    }
                    checked={generate404}
                    onChange={(value) => {
                        setGenerate404(value);
                        updateSetting('generate_404', value);
                    }}
                />

                {generate404 && (
                    <SelectControl
                        label={__('Custom 404 page (optional)', 'simply-static')}
                        value={settings.custom_404_page ?? 0}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        options={pages}
                        onChange={(pageId) => {
                            updateSetting('custom_404_page', pageId);
                        }}
                        help={__('If selected, Simply Static will use the content of this page for the 404 page instead of the theme default.', 'simply-static')}
                    />
                )}
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={
                        <>
                            {__('Include RSS Feeds?', 'simply-static')}
                        </>
                    }
                    help={
                        addFeeds
                            ? __('Include feed URLs of all your posts.', 'simply-static')
                            : __('Don\'t include feed URLs for all your posts.', 'simply-static')
                    }
                    checked={addFeeds}
                    onChange={(value) => {
                        setAddFeeds(value);
                        updateSetting('add_feeds', value);
                    }}
                />
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={
                        <>
                            {__('Include Rest API?', 'simply-static')}
                        </>
                    }
                    help={
                        addRestApi
                            ? __('Include all Rest API endpoints as JSON files.', 'simply-static')
                            : __('Don\'t include Rest API endpoints as JSON files.', 'simply-static')
                    }
                    checked={addRestApi}
                    onChange={(value) => {
                        setAddRestApi(value);
                        updateSetting('add_rest_api', value);
                    }}
                />
            </CardBody>
        </Card>
        <Spacer margin={5}/>
        <Card>
            <CardHeader>
                <b>{__('Exclude', 'simply-static')}<HelperVideo
                    title={__('Include & Exclude files and pages', 'simply-static')}
                    videoUrl={'https://youtu.be/voAHfwVMLi8'}/></b>
            </CardHeader>
            <CardBody>
                <TextareaControl
                    label={__('Urls to exclude', 'simply-static')}
                    placeholder={"some-directory\nsome-file.json\n.jpg"}
                    __nextHasNoMarginBottom
                    help={__('Specify URLs (or parts of URLs) you want to exclude from the processing (one per line).', 'simply-static')}
                    value={settings.urls_to_exclude}
                    onChange={(value) => {
                        updateSetting('urls_to_exclude', value);
                    }}
                />
            </CardBody>
        </Card>
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

export default GeneralSettings;
