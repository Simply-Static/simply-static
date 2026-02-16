import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    __experimentalNumberControl as NumberControl,
    Notice,
    Animate,
    TextControl, SelectControl, TextareaControl, ToggleControl, Modal, Flex, FlexItem, ExternalLink
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";
import HelperVideo from "../components/HelperVideo";


const {__} = wp.i18n;

function SearchSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved, isPro} = useContext(SettingsContext);
    const [useSearch, setUseSearch] = useState(false);
    const [searchType, setSearchType] = useState('fuse');
    const [isMetaModalOpen, setMetaModalOpen] = useState(false);
    const openMetaModal = () => setMetaModalOpen(true);
    const closeMetaModal = () => setMetaModalOpen(false);

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {
        if (settings.use_search) {
            setUseSearch(settings.use_search);
        }

        if (settings.search_type) {
            setSearchType(settings.search_type);
        }

    }, [settings]);

    // Derived flag for showing submit-related controls
    // Default is now: submit button disabled by default (false)
    const showSubmit = (settings.search_show_submit ?? settings.search_fuse_show_submit ?? false);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <Flex>
                    <FlexItem>
                        <b>{__('Search', 'simply-static')}</b>
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
                <ToggleControl
                    label={__('Use search?', 'simply-static')}
                    __nextHasNoMarginBottom
                    help={
                        useSearch
                            ? __('Use search on your static website.', 'simply-static')
                            : __('Don\'t use search on your static website.', 'simply-static')
                    }
                    disabled={('free' === options.plan || !isPro())}
                    checked={useSearch}
                    onChange={(value) => {
                        setUseSearch(value);
                        updateSetting('use_search', value);
                    }}
                />
                <SelectControl
                    label={__('Search Type', 'simply-static')}
                    value={searchType}
                    help={__('Decide which search type you want to use. Fuse runs locally based on a file, and Algolia is an external API service.', 'simply-static')}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    options={[
                        {label: 'Fuse JS', value: 'fuse'},
                        {label: 'Algolia API', value: 'algolia'},
                    ]}
                    onChange={(type) => {
                        setSearchType(type);
                        updateSetting('search_type', type);
                    }}
                />

                {/* Search UI settings (generic keys) — visible when Use Search is enabled, for both Fuse & Algolia */}
                {useSearch && (
                    <>
                        <Spacer margin={3} />
                        <ToggleControl
                            label={__('Use Search Results Page', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Simply Static will create a static search results page and the export will include a special __qs directory.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={(settings.use_search_results_page ?? true)}
                            onChange={(value) => {
                                updateSetting('use_search_results_page', value);
                            }}
                        />
                        <Spacer margin={3} />
                        <ToggleControl
                            label={__('Use Submit Button', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Show or hide the submit button in the search UI.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={(settings.search_show_submit ?? settings.search_fuse_show_submit ?? false)}
                            onChange={(value) => {
                                updateSetting('search_show_submit', value);
                            }}
                        />

                        {showSubmit && (
                            <>
                                <TextControl
                                    label={__('Submit button text', 'simply-static')}
                                    type={"text"}
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    placeholder={__('Search', 'simply-static')}
                                    help={__('Customize the submit button text or HTML (e.g., add an icon).', 'simply-static')}
                                    disabled={('free' === options.plan || !isPro())}
                                    value={settings.search_submit_text ?? settings.search_fuse_submit_text}
                                    onChange={(val) => {
                                        updateSetting('search_submit_text', val);
                                    }}
                                />
                            </>
                        )}

                        <ToggleControl
                            label={__('Show excerpt in suggestions/results', 'simply-static')}
                            __nextHasNoMarginBottom
                            help={__('Enable to display the excerpt below each search suggestion and result item.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            checked={(settings.search_show_excerpt ?? false)}
                            onChange={(value) => {
                                updateSetting('search_show_excerpt', value);
                            }}
                        />

                        {/* Input placeholder should be independent of the submit button visibility */}
                        <TextControl
                            label={__('Input placeholder', 'simply-static')}
                            type={"text"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            placeholder={__('Search..', 'simply-static')}
                            help={__('Customize the placeholder text for the search input.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.search_placeholder ?? settings.search_fuse_placeholder}
                            onChange={(val) => {
                                updateSetting('search_placeholder', val);
                            }}
                        />
                    </>
                )}
            </CardBody>
        </Card>
        <>
            {isMetaModalOpen && (
                <Modal title={__('How to select data with meta tags', 'simply-static')} onRequestClose={closeMetaModal}>
                    <p>{__('Targeting for excerpt in the meta description tag.', 'simply-static')}</p>
                    <pre>
                            &lt;meta name="description" content="This content is what we want as excerpt" /&gt;
                        </pre>
                    <p>{__('Adding such meta in the excerpt field would be:', 'simply-static')}</p>
                    <pre>
                            description|content
                        </pre>
                    <p>{__('Targeting for title in the property meta tag.', 'simply-static')}</p>
                    <pre>
                            &lt;meta property="og:title" content="This content is what we want as excerpt" /&gt;
                        </pre>
                    <p>{__('Adding such meta in the excerpt field would be:', 'simply-static')}</p>
                    <pre>
                            property|og:title
                        </pre>
                    <p>{__('If the second item (after | ) is not <code>content</code>, we\'ll use it as value of that attribute (<code>property="og:title"</code> in this example) and use <code>content</code> for value.', 'simply-static')}</p>
                    <p><strong>{__('Caution: Use meta tags that exist everywhere for title.', 'simply-static')}</strong>
                    </p>
                </Modal>
            )}
            <Spacer margin={5}/>
            <Card>
                <CardHeader>
                    <Flex>
                        <FlexItem>
                            <b>{__('Indexing', 'simply-static')}</b>
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
                        label={__('CSS-Selector for Title', 'simply-static')}
                        type={"text"}
                        placeholder={'title'}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        help={
                            <>
                                {__('Add the CSS selector which contains the title of the page/post', 'simply-static')}{' '}
                                <Button variant={'link'} onClick={openMetaModal}>
                                    {__('Or meta tags. Click for more information.', 'simply-static')}
                                </Button>
                            </>
                        }
                        disabled={('free' === options.plan || !isPro())}
                        value={settings.search_index_title}
                        onChange={(title) => {
                            updateSetting('search_index_title', title);
                        }}
                    />
                    <TextControl
                        label={__('CSS-Selector for Content', 'simply-static')}
                        type={"text"}
                        placeholder={'body'}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        help={
                            <>
                                {__('Add the CSS selector which contains the content of the page/post.', 'simply-static')}{' '}
                                <Button variant={'link'} onClick={openMetaModal}>
                                    {__('Or meta tags. Click for more information.', 'simply-static')}
                                </Button>
                            </>
                        }
                        disabled={('free' === options.plan || !isPro())}
                        value={settings.search_index_content}
                        onChange={(content) => {
                            updateSetting('search_index_content', content);
                        }}
                    />

                    <TextControl
                        label={__('CSS-Selector for Excerpt', 'simply-static')}
                        type={"text"}
                        placeholder={'.entry-content'}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        help={
                            <>
                                {__('Add the CSS selector which contains the excerpt of the page/post.', 'simply-static')}{' '}
                                <Button variant={'link'} onClick={openMetaModal}>
                                    {__('Or meta tags. Click for more information.', 'simply-static')}
                                </Button>
                            </>
                        }
                        disabled={('free' === options.plan || !isPro())}
                        value={settings.search_index_excerpt}
                        onChange={(excerpt) => {
                            updateSetting('search_index_excerpt', excerpt);
                        }}
                    />

                    <TextareaControl
                        label={__('Exclude URLs', 'simply-static')}
                        placeholder={"author\narchive\ncategory"}
                        __nextHasNoMarginBottom
                        help={__('Exclude URLs from indexing (one per line). You can use full URLs, parts of an URL or plain words (like stop words).', 'simply-static')}
                        disabled={('free' === options.plan || !isPro())}
                        value={settings.search_excludable}
                        onChange={(excludes) => {
                            updateSetting('search_excludable', excludes);
                        }}
                    />
                </CardBody>
            </Card>
        </>
        {searchType === 'fuse' &&
            <>
                <Spacer margin={5}/>
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('Fuse.js', 'simply-static')}<HelperVideo
                                    title={__('How to add search with FuseJS', 'simply-static')}
                                    videoUrl={'https://youtu.be/K34l1DXjCHk'}/></b>
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
                            label={__('CSS-Selector', 'simply-static')}
                            type={"text"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add the CSS selector for the <form> element that contains your search input field.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.fuse_selector}
                            onChange={(selector) => {
                                updateSetting('fuse_selector', selector);
                            }}
                        />
                        <Notice status="warning" isDismissible={false}>
                            {__('If you have multiple search elements with different CSS selectors, separate them by a comma (,) such as: .search-form, .custom-search-form', 'simply-static')}
                        </Notice>
                        <NumberControl
                            label={__('Threshold', 'simply-static')}
                            isShiftStepEnabled={ true }
                            step={ 0.1 }
                            min={0.1}
                            max={1}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__(' A threshold of 0.0 requires a perfect match, a threshold of 1.0 would match anything.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.fuse_threshold}
                            placeholder={0.1}
                            onChange={(threshold) => {
                                updateSetting('fuse_threshold', threshold);
                            }}
                        />
                    </CardBody>
                </Card>
            </>
        }

        {searchType === 'algolia' &&
            <>
                <Spacer margin={5}/>
                <Card>
                    <CardHeader>
                        <Flex>
                            <FlexItem>
                                <b>{__('Algolia API', 'simply-static')}<HelperVideo
                                    title={__('How to add search with the Algolia API', 'simply-static')}
                                    videoUrl={'https://youtu.be/H9PNZSl0KnU'}/></b>
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
                            label={__('Application ID', 'simply-static')}
                            type={"password"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add your Algolia App ID.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.algolia_app_id}
                            onChange={(app_id) => {
                                updateSetting('algolia_app_id', app_id);
                            }}
                        />

                        <TextControl
                            label={__('Admin API Key', 'simply-static')}
                            type={"password"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add your Algolia Admin API Key.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.algolia_admin_api_key}
                            onChange={(api_key) => {
                                updateSetting('algolia_admin_api_key', api_key);
                            }}
                        />

                        <TextControl
                            label={__('Search-Only API Key', 'simply-static')}
                            type={"password"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add your Algolia Search-Only API Key here. This is the only key that will be visible on your static site.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.algolia_search_api_key}
                            onChange={(api_key) => {
                                updateSetting('algolia_search_api_key', api_key);
                            }}
                        />

                        <TextControl
                            label={__('Name for your index', 'simply-static')}
                            type={"text"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add your Algolia index name here.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.algolia_index}
                            onChange={(index) => {
                                updateSetting('algolia_index', index);
                            }}
                        />
                        <TextControl
                            label={__('CSS-Selector', 'simply-static')}
                            type={"text"}
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Add the CSS selector for the <form> element that contains your search input field.', 'simply-static')}
                            disabled={('free' === options.plan || !isPro())}
                            value={settings.algolia_selector}
                            onChange={(selector) => {
                                updateSetting('algolia_selector', selector);
                            }}
                        />
                        <Notice status="warning" isDismissible={false}>
                            {__('If you have multiple search elements with different CSS selectors, separate them by a comma (,) such as: .search-form, .custom-search-form', 'simply-static')}
                        </Notice>
                    </CardBody>
                </Card>
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
            {'pro' === options.plan && isPro() &&
                <Button onClick={setSavingSettings}
                        variant="primary">{__('Save Settings', 'simply-static')}</Button>
            }
        </div>
    </div>)
}

export default SearchSettings;