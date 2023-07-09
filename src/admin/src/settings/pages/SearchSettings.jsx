import {
    Button,
    Card,
    CardBody,
    CardHeader,
    __experimentalSpacer as Spacer,
    Notice,
    Animate,
    TextControl, SelectControl, Flex, FlexItem, TextareaControl, ToggleControl,
} from "@wordpress/components";
import {useContext, useEffect, useState} from '@wordpress/element';
import {SettingsContext} from "../context/SettingsContext";

const {__} = wp.i18n;

function SearchSettings() {
    const {settings, updateSetting, saveSettings, settingsSaved, setSettingsSaved} = useContext(SettingsContext);
    const [useSearch, setUseSearch] = useState(false);
    const [searchType, setSearchType] = useState('fuse');

    const setSavingSettings = () => {
        saveSettings();
        setSettingsSaved(true);

        setTimeout(function () {
            setSettingsSaved(false);
        }, 2000);
    }

    useEffect(() => {

    }, [settings]);

    return (<div className={"inner-settings"}>
        <Card>
            <CardHeader>
                <b>{__('Search', 'simply-static')}</b>
            </CardHeader>
            <CardBody>
                <ToggleControl
                    label={__('Use search?', 'simply-static')}
                    help={
                        useSearch
                            ? 'Use search on your static website.'
                            : 'Don\'t use search on your static website.'
                    }
                    checked={ useSearch }
                    onChange={ () => {
                        setUseSearch( ( state ) => ! state );
                    } }
                />

                {useSearch &&
                    <SelectControl
                        label={__('Search Type', 'simply-static')}
                        value={searchType}
                        help={__('Decide wich search type you want to use. Fuse runs locally based on file and Algolia is an external API service.', 'simply-static')}
                        options={[
                            {label: 'Fuse JS', value: 'fuse'},
                            {label: 'Algolia API', value: 'algolia'},
                        ]}
                        onChange={(value) => {
                            setSearchType(value);
                        }}
                    />
                }
            </CardBody>
        </Card>
        {useSearch &&
            <>
                <Spacer margin={5}/>
                <Card>
                    <CardHeader>
                        <b>{__('Indexing', 'simply-static')}</b>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={__('CSS-Selector for Title', 'simply-static')}
                            type={"text"}
                            placeholder={'title'}
                            help={__('Add the CSS selector which contains the title of the page/post', 'simply-static')}
                            value={'title'}
                            onChange={(value) => {

                            }}
                        />
                        <TextControl
                            label={__('CSS-Selector for Content', 'simply-static')}
                            type={"text"}
                            placeholder={'body'}
                            help={__('Add the CSS selector which contains the content of the page/post.', 'simply-static')}
                            value={'body'}
                            onChange={(value) => {

                            }}
                        />

                        <TextControl
                            label={__('CSS-Selector for Excerpt', 'simply-static')}
                            type={"text"}
                            placeholder={'.entry-content'}
                            help={__('Add the CSS selector which contains the excerpt of the page/post.', 'simply-static')}
                            value={'.entry-content'}
                            onChange={(value) => {

                            }}
                        />

                        <TextareaControl
                            label={__('Exclude URLs', 'simply-static')}
                            placeholder={"author\narchive\ncategory"}
                            help={__('Exclude URLs from indexing (one per line). You can use full URLs, parts of an URL or plain words (like stop words).', 'simply-static')}
                            value={''}
                            onChange={ ( value ) => {

                            } }
                        />
                    </CardBody>
                </Card>
            </>
        }
        {useSearch && searchType === 'algolia' &&
            <>
                <Spacer margin={5}/>
                <Card>
                    <CardHeader>
                        <b>{__('Algolia API', 'simply-static')}</b>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            label={__('Application ID', 'simply-static')}
                            type={"password"}
                            help={__('Add your Algolia App ID here.', 'simply-static')}
                            value={''}
                            onChange={(value) => {

                            }}
                        />

                        <TextControl
                            label={__('Admin API Key', 'simply-static')}
                            type={"password"}
                            help={__('Add your Algolia Admin API Key here.', 'simply-static')}
                            value={''}
                            onChange={(value) => {

                            }}
                        />

                        <TextControl
                            label={__('Search-Only API Key', 'simply-static')}
                            type={"password"}
                            help={__('Add your Algolia Search-Only API Key here. This is the only key that will be visible on your static site.', 'simply-static')}
                            value={''}
                            onChange={(value) => {

                            }}
                        />

                        <TextControl
                            label={__('Name for your index', 'simply-static')}
                            type={"text"}
                            help={__('Add your Algolia index name here.', 'simply-static')}
                            value={'simply-static'}
                            onChange={(value) => {

                            }}
                        />

                        <TextControl
                            label={__('CSS-Selector', 'simply-static')}
                            type={"text"}
                            help={__('Add the CSS selector of your search element here.', 'simply-static')}
                            value={'.search-field'}
                            onChange={(value) => {

                            }}
                        />
                        <p>
                            <Notice status="warning" isDismissible={false}>
                                {__('If you have multiple search elements with different CSS selectors, separate them by a comma (,) such as: .search-field, .search-field2', 'simply-static')}
                            </Notice>
                        </p>
                    </CardBody>
                </Card>
            </>
        }
        <Spacer margin={5}/>
        {settingsSaved &&
            <Animate type="slide-in" options={{origin: 'top'}}>
                {() => (
                    <Notice status="success" isDismissible={false}>
                        <p>
                            {__('Settings saved successfully.', 'simply-static')}
                        </p>
                    </Notice>
                )}
            </Animate>
        }
        <div className={"save-settings"}>
            <Button onClick={setSavingSettings}
                    variant="primary">{__('Save Settings', 'simply-static')}</Button>
        </div>
    </div>)
}

export default SearchSettings;