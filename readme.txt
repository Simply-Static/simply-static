=== Simply Static - The Static Site Generator ===
Contributors: patrickposner
Tags: static site generator, performance, security, jamstack
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag:  3.5.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create a static website directly from your WordPress website with Simply Static.

== Description ==

Simply Static is a static site generator that converts your existing WordPress website to a static website that you can host on your server, a static hosting provider, or a CDN.

It drastically improves the performance of your website and minimizes security risks as it removes the connection between your traditional server and your static website.

Simply Static can also be used by local development tools like LocalWP or Lando to develop your website offline and push the static site online.

This reduces your hosting costs to zero (depending on your requirements and the used service/provider).

= Performance =

Converting your website to a static website can drastically improve your performance. Simply Static eliminates all requests to your database and by that reducing the first time to byte.

This is especially impactful for websites that use long pages with a lot of animations, graphs, and other load-heavy tools like sliders.

= Security =

Removing the connection to your database and traditional server reduces the risk of getting hacked. 

This reduces the stress to keep your WordPress website up to date and keeps your data secure (locally or in a protected environment).

= Reduce hosting costs =

Using Simply Static to deploy your website to one of the static hosting providers, a CDN or an S3 bucket reduces the amount of money required for reliable web hosting.

With the right setup, you can even host your website completely for free (Cloudflare Pages and GitHub Pages for example).

= Local development =

Develop your site locally and only push the static exported website on a server. To ensure everything works as expected I created a list of development tools that are completely supported:

* [LocalWP](https://localwp.com/)
* [lando](https://docs.lando.dev/)

I highly recommend not to use another tool for local development if you are not a programmer as in most cases manual package installation is required.

= Static Studio =

Static Studio is our all-in-one Static WordPress cloud hosting solution - here is why you want it:

* Secure WordPress hosting (locked-down WP, Firewall, NGINX-powered, 24/7 monitoring)
* Fast static site hosting via BunnyCDN (global distribution, low TTFB, caching, minification)
* Secure logins via SSO, 2FA protection and team management
* Automated (and free) migration for all your existing sites
* Backups, SSL, unlimited file storage and 1TB bandwidth per site
* WP-CLI, SSH, SFTP and hands-on code support

[Start your 7-day free trial](https://simplystatic.com/simply-static-studio/)

== Simply Static Pro ==

The pro version of Simply Static enhances the plugin with various features.

[youtube https://youtu.be/Vml537IIwVc]

= GitHub Integration =

With the GitHub integration, you can completely automate your entire development process.

The only thing you need is a GitHub account, no knowledge of Git or GitHub itself is required, as Simply Static Pro handles the entire process for you and keeps your repository up to date.

This allows deploying your static website to:

* GitHub Pages
* Cloudflare Pages
* Netlify
* Vercel

= AWS S3 Integration =

Export directly to Amazon AWS S3 from Simply Static Pro. Connect your bucket and run your export.

= SFTP =

Export directly to a remote SFTP server. Connect your server and run your export.

= BunnyCDN Integration =

Export directly to BunnyCDN and get all the benefits of their CDN - including caching, file optimization and DNS management.


= Incremental-, Builds and Single Exports =

Use Incremental Exports to update only changes to your static website. No configuration needed, just choose Update and run the export.

Use Build Exports to quickly roll out global changes to your static website. Want to update your style.css file? Or a new plugin? Use a build export.

Use Single Exports to quickly publish new pages and posts to your static site. This also supports scheduled posts.

= Forms =

Create and use forms created with your favorite form plugin on your static website with Simply Static Pro. Supported plugins are:

* Contact Form 7
* Gravity Forms
* WP Forms
* FluentForms
* WS Forms

You can decide if you want to use an external service like Zapier and IFTTT to offload the submission (webhook) or embed the form from your WordPress website via iFrame.


= Search =

Use a fully static search solution that does not rely on any Third-Party-Providers. It uses Fuse.js and creates an index of your whole website as a JSON file.

The search supports autosuggestion and fuzzy logic and is by default way more convenient than your default WordPress search.


= Comments =

Enable comments on your static website without the requirement of Third-Party-Providers. Use your original WordPress website to process the comment.

After a comment was submitted, Simply Static automatically exports a new static version of the post. You don't have to do anything.


= Multilingual =

Use WPML, Polylang or TranslatePress and run static exports per language or all at once.

Simply Static Pro also supports configuring settings per language, using the available language switchers and more to export a your multilingual website as easy as possible.


= WP-CLI =

Simply Static Pro has a detailed and comprehensive integration with WP-CLI.

Control every option, run different kinds of exports and more with the WP-CLI integration.

= Minification =

Automatically minfiy HTML, CSS and JavaScript files on your static site.

We can even minify inline CSS & JavaScript.

= Image Optimization =

Automatically optimize images on your static website with our ShortPixel API integration.

= Optimization =

Replace default WordPress paths and completely hide that you are using WordPress behind the scenes.

Replace:

* wp-content
* wp-includes
* wp-content/plugins/
* wp-content/themes/
* wp-content/uploads/


Hide & Disable:

Disabled unwanted features in WordPress before running an static export like:

* XML-RPC
* REST API URLs
* Emoji support
* Shortlink support
* WordPress version in HTML

and much more.

= Get the Pro version =

You can get the pro version [here](https://simplystatic.com/pricing/).

= Simply Static Course =

We created an entire video course about Static WordPress with Simply Static - check out the first video:

[youtube https://youtu.be/Ei_w-Jcq4uQ]

You can watch the entire course entirely for free on YouTube here: [Simply Static Course](https://www.youtube.com/playlist?list=PLcpe8_rNg8U5g1gCOa0Ge6T17f50nSvmg)

= Tutorials =

We also publish new tutorials on our blog every other week on how to work with Simply Static and other static site tools.

You can check the latest tutorials [here](https://simplystatic.com/tutorials/)

= Documentation =

We have a super extensive documentation that covers every aspect of Simply Static and Simply Static Pro.

You can check the documentation [here](https://docs.simplystatic.com)


== Installation ==

1. Log into your WordPress website.
2. On the left menu, hover over Plugins and then click on Add New.
3. In the Search Plugins box, type in "Simply Static" and press the Enter key.
4. You will see a list of search results that should include the Simply Static plugin. Click on the Install Now button to install the plugin.
5. After installing the plugin you will be prompted to activate it. Click on the Activate Plugin link.
6. The Simply Static plugin is now installed and can be found on the left menu.

or

1. Upload the entire `simply-static` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= What does Simply Static do? =

Simply Static generates static (HTML) copies of your WordPress pages. It works a bit like a web crawler, starting at the main page of your website and looking for links to other pages to create static copies of. It also includes any images, CSS & JS files, and any other files that it can find a link to.

As Simply Static is creating the static pages, it will automatically replace the URLs for the WordPress installation with either (a) absolute URLs, at a domain of your choice or (b) relative URLs, so you can host the static files on any domain or (c) URLs constructed for offline use, so you can browse the site locally on your computer.

= Who should use Simply Static? =

Pretty much everyone, besides e-commerce or membership sites. They require a serious amount of interaction and user accounts, which are not available on a static website.

= Are there any limitations? =

You can not use plugins that require a login as there is no account system on a static website. This applies to e-commerce (like WooCommerce) or membership sites.

Also, a website that relies heavily on ajax to update content in real-time is not the best project for Simply Static as a serious amount of custom development would be required.

= How do I set up Simply Static? =

Let's assume you presently have WordPress hosting a site at www.example.com, and that's where you'd like to have your static site instead. Your first task is going to be to move your WordPress installation to a subdomain, such as wordpress.example.com. Once that is complete, you'll set up www.example.com to receive your static files.

If www.example.com is on the same server as your WordPress installation, you can have Simply Static copy the static files to the directory that www.example.com is serving files from. If www.example.com is on a different server, you can download a zip of your static files and then upload them to www.example.com.

= Will this plugin interfere with other plugins? =

No, Simply Static will not interfere with other plugins. Simply Static works well alongside other plugins, such as plugins to improve your site's SEO.

Simply Static creates a static copy of your website, which is just a collection of files: HTML, CSS, JS, images, etc. Any functionality that requires PHP code will not work with that static copy. That includes, but is not limited to: blog post comments, contact forms, forums, membership areas, and eCommerce.

Note that you can achieve much of this functionality by using plugins that interact with third-party services or Simply Static Pro.

= How is Simply Static different from cache plugins? =

Cache plugins -- such as W3 Total Cache or WP Super Cache -- make your existing WordPress site faster by caching pages as they're visited. This makes your site much faster but still leaves your WordPress installation accessible to the outside world. Unless you keep on top of updates, your WordPress installation can become vulnerable to hackers due to security vulnerabilities that are found over time.

Simply Static creates a static copy of your WordPress site that is intended to be used completely separately from your WordPress installation. Your WordPress installation lives on one server and your static site is served on a different server. Or, they're both on the same server, but your WordPress installation is restricted to only allow access from certain IP addresses or with an additional username/password requirement. Your static site is just a collection of static files with no server-side code or database -- nothing for hackers to hack -- while your WordPress installation remains safe and secure.

== Screenshots ==

1. Generate
2. Settings
3. Diagnostics

== Changelog ==

= 3.5.2.2 =

* added ss_mime_type_map filter to extend/override manual MIME type map fallback + failsafe handling
* added filter to exclude robots.txt and llms.txt from export

= 3.5.2.1 =

* fail-safe upgrade mechanism (alongside pro)

= 3.5.2 =

* UI/UX improvements (search/workflow/general)
* refactored transfer files locally task (+ added filter to make it extendable)
* improved pagination crawler (smarter author pagination)
* introduced ss_before_finish_transferring_files_locally filter
* improved integration copy
* enforced naming convention across crawler and integration directory (class file naming)
* updated translation file (including german translation)
* improved integrations UX by adding a new requires_reload mechanism
* fixed SEOPress XSL file naming
* fixed JSON handler for Elementor (animations/menu rendering)

= 3.5.1.2 =

* abstracted encoding into new helper class
* fixed UTF-8 encoding for emojis
* fixed check-can-run endpoint (GET not POST)
* added MU-related notice (for auto-fixer in pro)
* added ss_multisite_get_sites_args to filter subsite selection
* fixed exception (PHP 8.3+) for MU class

= 3.5.1.1 =

* clear temp files + log + DB table on plugin deactivation
* avoid decoding inline-JSON (Elementor)
* ss_user_capability for admin bar integration
* MU: decode special chars before dropdown output
* Removed HTML5 auto-fixer (should be a filter)

= 3.5.1 =

* better HTML 5 markup support for non-closing tags (like <source>)
* added ZIPArchive support for larger ZIP exports (>4GB)
* MU: sort subsites ASC + added site ID
* added Clear Temporary Files button + auto-clear on uninstall + ss_clear_temp_dir_on_wrapup filter
* mb_encode_numericentity + mb_convert_encoding for better UTF-8 conversion support
* added ss_zip_filename filter to rename ZIP file
* added ss_crawlable_plugins and ss_crawlable_themes filter

= 3.5.0 =

* better UTF-8 and entity handling (content + URLs)
* added network-activated plugins to crawler support
* added ability to exclude specific plugins + themes in Enhanced Crawl (pro-only)
* added support for .avif, .tiff and .heic image files
* regex support for Include/Exclude settings
* URL-based export cancellation via cancel-export=true parameter
* llms.txt support for AIO SEO and Yoast SEO integration
* fixed logging loop for Elementor integration

= 3.4.8 =

* new text handler for replacing URLs in txt files
* RankMath: auto replace and copy llms.txt and robots.txt to your static site
* Utilities: new reset background queue feature
* improved admin bar integration with deeplinks and better performance
* Quick link to view your static site from the dashboard
* fallback strategy to pick up and export 0 byte files
* Admin UI improvements (fixing Chrome notices)

= 3.4.7.2 =

* reverted avoid error on invalid file path

= 3.4.7.1 =

* reverted fetch progress count improvements

= 3.4.7 =

* improved XML sitemap URL replacements
* improved invalid file path handling
* added dedicated Beaver Builder crawler
* improved fetch progress count (runtime)
* UI-changes related to multisite environments
* blocking accidental overwriting exports on MU sites

= 3.4.6.1 =

* Fixed DomXPath conversion for strings (RankMath and Yoast SEO)
* Added SEO Generator integration placeholder (pro-only integration)
* batch processing for Additional URLs and Files (improved memory usage)

= 3.4.6 =

* DIVI integration + custom crawler
* Improved Elementor integration
* Custom 404 page selection (instead of theme default)
* auto-include llms.txt on crawl
* improved third-party-crawler loading

= 3.4.5.1 =

* small admin UI fix (scheme positioning)
* exit early in URL extractor if html_string is empty

= 3.4.5 =

* improved HTML conversion with non-latin characters
* adapted our admin UI to latest React and WP components
* updated all core dependencies (JS)

= 3.4.4.1 =

* better path transformation for file transfer
* stream mechanism for Elementor_Crawler

= 3.4.4 =

* improved URL extraction for URLs in CSS files
* stream files for media, plugins and themes
* avoid memory issues with large log objects
* discover_task only on export
* improved local asset handling (include font files)
* better URL handling in export log
* added jQuery to WP_Includes_Crawler

= 3.4.3.3 =

* better default handling for Elementor crawling
* wait until page loaded before saving (fetcher)
* better auto-configuration for Enhanced Crawl

= 3.4.3.2 =

* dedicated crawler for Elementor
* force MIME type for JS files
* add comment.js in includes if comments enabled

= 3.4.3.1 =

* better XML sitemap handling with Enhanced Crawl
* easier path handling for XSL stylesheets
* merged Elementor + Elementor Pro into single integration
* removed Jetpack integration (now handled by Enhanced Crawl)
* upgrade handler specifically for Enhanced Crawl

= 3.4.3 =

* fixed export type selection based on deployment method
* added post type selector for Enhanced Crawl
* refactored Block_Theme_Crawler to WP_Includes_Crawler making it work for all kinds of themes
* refactored Pagination_Crawler to exclude pagination URLs based on parameter (&paged)
* single source of truth for fetchting settings + reset via PHP + no defaults stored in JS anymore
* improved file handler (copy instead of wp_remote_get request)
* better defaults for active_crawlers on new installations
* improved environment integration UI
* removed auto-switch for export types

= 3.4.2.1 =

* Smart Crawl -> Enhanced Crawl (thanks WPMUDEV for the copyright claim)

= 3.4.2 =

* fixed integration loading in admin settings
* modified readme to comply with .org trademark violation (...)

= 3.4.1 =

* improved sanitization for saving crawlers
* dedicated function to fetch crawlers to reduce impact on useEffect
* improved defaultState to avoid display default options via Settings

= 3.4.0 =

* introduced Smart Crawl
* preserve URL parameters on creating redirect files
* removed deprecated plugin scan task
* removed download attribute from ZIP download link (playground)
* introduced improved export log (search/filter/sort)

= 3.3.3.5 =

* fixed typo (placehodler vs. placeholder)
* better handling for import maps when using offline mode
* avoid error if empty DOM returned (Yoast SEO integration)
* improved ZIP URL handling with WP_CONTENT_DIR and WP_CONTENT_URL set
* use backticks when creating tables to avoid AWS Aurora MySQL
* exclude robots.txt from .txt exclusion
* added method to delete single record from DB table
* preserve URL parameters on export (UTM tags, generic URL parameters, version numbers for JS/CSS)

= 3.3.3.4 =

* preserve JSON inside attributes (Elementor, Cornerstone and others)

= 3.3.3.3 =

* adding a hidden delay integration to auto-pause between exports (disabled by default)
* refactored UTF-8 handling to properly encode Arabic characters and German Umlauts
* added support for conditional comments being extracted without modifying the structure
* improved "feed" and "debug" checks inside exclude handler with regex to avoid false-positives
* added custom Elementor widget for search

= 3.3.3.2 =

* preserve custom body classes

= 3.3.3.1 =

* Ported SEO extensions to use DomXPath

= 3.3.3 =

* WP HTML API -> DomDocument
* DomXPath for Dom interactions

= 3.3.2 =

* fixed typo in search settings
* fixed AIO SEO sitemap parsing
* improved srcset extraction
* HTMLDOMParser replaced with WP HTML API
* improved Basic Auth handling with Ubuntu 24+
* no more custom UTF-8 handler - PHP 7.4 requires mbstring anyway

= 3.3.1.2 =

* fixed multiline save fields (minify)
* fixed typo in Rest API options toggle
* reworked Update labels

= 3.3.1.1 =

* clear PHP_AUTH_USER and PHP_AUTH_PW on disabling/clearing basic auth options
* improved version output in admin UI
* fixed position for pro recommendation (top-right instead of center of the card header)

= 3.3.1 =

* lower min WP version to 6.2
* support for XSL file parsing (crawling, replacing, including)
* fixed settings handler (UI glitch in deployment settings)
* extracted force_replace_urls into it's own method

= 3.3.0 =

* avoid parsing Simply Static config files (JSON parser)

= 3.2.9 =

* allow parallel batch processing for tasks (opt-in)

= 3.2.8.3 =

* Elementor: only include bundle files if filter is set
* WPML: change loading priority of Basic Auth filter to avoid fatal error

= 3.2.8.2 =

* Fixed: _load_textdomain_just_in_time was called incorrectly
* floating action buttons

= 3.2.8.1 =

* avoid replacing URLs in JSON files generated by Simply Static

= 3.2.8 =

* removed deprecated option labels in WPML config file
* latest updates for WPBP package and moved to our own maintained version (fewer dependencies)
* data-bg for sections, divs and img tags (match_tags)
* WASM fix for download Urls of ZIP files
* added support for JSON files in extractor (WP Rest API support on static sites)
* added option to include wp-json files in export
* WP tested up to 6.8
* added <use> tag with href attribute (match_tags)
* added uploads directory handler for Elementor integrations
* added feature to allow full scans of theme and plugins directory on exports


= 3.2.7.2 =

* Bring back simply_static_fetch_urls_batch_size filter
* Extended img attribute list with "data-srcset" and "data-bg"
* added threshold option for Fuse.js
* allow subitems in admin bar integration

= 3.2.7.1 =

* Elementor integration: check for file size before including
* added filter to control wp_flush_cache execution
* reverted default batch size back to 50
* added srcset attributes for video and audio HTML5 tags
* added filter for menu position

= 3.2.7 =

* simplified batch size filtering:https://docs.simplystatic.com/article/135-simplystatictasknamebatchsize
* removed unused options from optimization page
* removed unused conditions in tasks checkups
* fixed exclude filter position to handle feed URLs that aren't RSS
* re-added ss_total_pages filter for total pages count
* added post_id checkup for processing calculation
* removed duplicated status messages in export log
* removed Brizy integration (no longer needed)

= 3.2.6 =

* clear WP object cache on start + cancel export
* simplified and improved transfer_files_locally task
* improved logging for transfer_files_locally task
* extended UI for AWS updates
* sort builds alphabetically in export type UI

=  3.2.5.4 =

* added option to optionally include feed URLs (default is off)
* set generate_404 page to off on default settings
* extended Elementor integration to auto-include missing CSS files

= 3.2.5.3 =

* downgraded WP Background Processing package to 1.3.1 again (issues with ajax)
* responsive design for admin UI
* improved Basic Auth Handling for different server types (NGINX, Apache, Windows IIS)
* fixed automated feed handling on running exports

= 3.2.5.2 =

* upgraded WP Background Processing package
* fixed typo in admin UI
* increased interval between batches to 2 seconds (from 1 second)

= 3.2.5.1 =

* XML sitemap inclusion optional for single exports
* fixed _load_textdomain_just_in_time error

= 3.2.5 =

* added filter to modify log file location
* small UI factor to fix weird WP default border styles in admin UI
* trait to make tasks skippable
* implemented cleanup method that can be extended in tasks
* decreased default job interval to 1 second instead of 5

= 3.2.4.2 =

* small UI changes related to GitHub
* improved filter handling to increase batch size
* allow tasks to define their own batch size

= 3.2.4.1 =

* fixed dbDelta migration for new JSON column

= 3.2.4 =

* automatically create the local directory if it doesn't exist
* Limit Basic Auth to URLs that pass is_local_url() check
* WordPress 6.7 compatibility

= 3.2.3 =

* removed processing_pages_message method
* added the ability to include files without a file extension (_headers, _redirects..)
* added UI to reset database table
* included diagnostics check for no-index setting
* included diagnostics check for Basic Auth without proper configuration in settings
* tweak to avoid duplicate notes in export log
* renamed Simply Static (Admin Bar) to Admin Bar (Core)
* renamed Environments to Environment (Core)
* simplified http_request_args filter implementation
* added notice to upgrade both (pro and free) because of removing deprecated methods

= 3.2.2 =

* officially deprecated delete_transients method

= 3.2.1 =

* improved file handling on local transfer task
* refactored transient handling for task handling
* renamed delete_transients to delete_total_pages for consistency

= 3.2.0 =

* improved URL replacement in Offline Mode
* automated temporary file path handling
* SQLite support for potential Playground support
* removed dynamic htaccess for debug log handling
* added version number in default settings in upgrade handler
* enhanced integrations implementation to allow deactivated integrations by default
* improved wording around SFTP integration
* improved default values for excludes


= 3.1.9 =

* simplified process_pages class abstraction
* fixed stripping quotes on HTML extraction
* added security.md file to repo for updates via CLI
* use TEXT instead of JSON in our DB table for SQLite (Playground) support
* added default value for per_page parameter
* removed deprecated SimplyCDN integration
* fixed path resolving for /feed/ URLs
* restricted redirects includes to full or update exports
* set a recognizable User Agent for wp_remote_get requests
* avoid checking against NULL for content_type (PHP 8.3 support)

= 3.1.8.1 =

* turn off notifications on MU network settings
* improved MU subsite checkups

= 3.1.8 =

* several admin UI improvements
* added tutorial videos for all major features
* added a new DB column to store JSON data (for future features)
* improved and fixed transient handling and expiration
* added support for redirects with Redirection, Yoast and RankMath
* auto-replace URLs in schema.org JSON-LD data from Yoast and RankMath
* fixed typos in admin UI
* removed SSL check from diagnostics (no longer needed)
* added test deploy function
* remove deprecated basic_auth_digest function
* extended match_tags list for SVGs and other tags
* more failsafe parsing with innerhtmlKeep instead of innerText (crawler)

= 3.1.7.4 =

* fixed PHP notice related to server_cron setting
* fixed basic auth validation in admin area

= 3.1.7.3 =

* added new integration settings page
* added admin bar as integration + toggle to enable/disable it
* improved performance for diagnostics check (caching)
* removed unused http_request_args filter
* MU network page UI fixes
* added quick links to plugins page
* added option to whitelist plugins in diagnostics
* restructed "Misc" settings and renamed to "Debug"
* added option to use server-side cron job
* adapted Jetpack integration to new integration class

= 3.1.7.2 =

* added Jetpack integration
* removed DO integration admin UI code
* added progress bar to admin bar
* Diagnostics sub menu page + notification center
* removed can_wp_make_requests_to_itself() check
* fixed PHP notices for PHP 8.3 compatibility
* simplified log file output for certain deployment options
* composer dependencies updated


= 3.1.7.1 =

* improved hash validation for record storage in DB
* smaller UI improvements
* auto-generate index.html for feed URLs

= 3.1.7 =

* Official PHP 8.2 and 8.3 support + fixes for various PHP notices
* fixed saving multiline settings savings process
* improved default settings on first installation + reset
* extended the match_tags list for better XML support
* improved URL handling when creating 404 pages
* automated 404 page handling for various deployment options
* added cache detection solution as part of diagnostics
* added incompatible plugin detection as part of diagnostics
* added notification logic if tests in diagnostics fail
* improved XML sitemap handling in all SEO integrations
* auto-include robots.txt file if exists

= 3.1.6.3 =

* no more filesize limits on wp_remote_get()
* removed empty settings page on network admin
* improved various descriptions + added links to the documentation
* added filter to set conditions before clearing local directory
* avoid clearing special characters from Basic Auth credentials
* auto-cancel export if Basic Auth is set and credentials don't match
* improved default settings handling
* extended plugin compatibility list up to 100 (from 30)
* unified 404 page option for CDN exports

= 3.1.6.2 =

* new filter for extended DOM manipulation
* fixed typos for optimization settings
* exclude builds and single exports from clear directory

= 3.1.6.1 =

* modified default parameters for ss_remote_args filter (file size based on uploads limit)

= 3.1.6 =

* new multisite integration (network, import/export subsites)
* improved 404 page handling
* improved secure debug log handling
* plugin compatibility database integration
* admin UI improvements (labels, helper texts..)
* updated translation files
* improved version output in admin UI

= 3.1.5 =

* refactored additional settings
* introduced setting for origin URL
* removed unused helper methods
* improved requests to itself check in diagnostics
* improved sanitization for multiline fields
* load textdomain in init hook instead of plugins_loaded
* NPM packages updated to latest releases


= 3.1.4 =

* added log for replacing 404 pages
* fix for 404 page in local directory exports
* clear log before running new export to avoid big file sizes
* extended ss_remote_args filter with async requests + max file size per request
* improved sanitization for import/export settings
* added filter for local URL check
* improved secure log handling (dynamic filename + .htaccess rule)

= 3.1.3 =

* JS dependencies updated
* refactored filter implementation for tasks
* added check for empty strings in excludes
* removed unused blog_id check
* changed location of debug log to uploads dir
* check for Rest API in diagnostics
* increased PHP version for diagnostics >= 8.0
* whitelist Freemius API calls if Basic Auth enabled

= 3.1.2 =

* WordPress 6.4 compatibility
* Improved clearing logs
* improved capability filter

= 3.1.1 =

* improved admin UI descriptions in general settings
* added support for 404 pages
* added error message to WP_Error when archive dir cannot be created
* added add_status_message method to Simply_Static\Options for WP CLI handling

= 3.1.0 =

* fix for scheme/host when resetting settings
* enhanced integration setups (GitHub objects)
* fixed example for relative path setups in settings


= 3.0.9 =

* PHP 8.2 support improved
* wp_parse_url instead of just parse_url for fetching URLs
* enhanced default options
* increased min WP version

= 3.0.8 =

* SimplyCDN auto include files
* min WP version increased to 6.2
* improved wording for progress in export log
* improved Basic Auth handling
* improved absolute URL replacement
* Elementor integration: prevent replacing non strings in HTML


= 3.0.7 =

* SimplyCDN setting now in React Admin UI
* filter for capabilities in Rest routes
* SimplyCDN improvements for webhook handling


= 3.0.6 =

* removed type hints in Rest API abstraction for PHP 7.4 support
* make sure we only trigger maybe_wp_die() if there is an error on export

= 3.0.5 =

* fixed incompatibility with EWWW image optimizer

= 3.0.4 =

* simplified url_exclude upgrade on update
* disable browser autocomplete on basic auth settings
* added additional notice with button in settings panel if all auto-migration fails

= 3.0.3 =

* specific version check for migration handler
* fixed schema option updates

= 3.0.2 =

* fixed iterator for "clear_local_directory" option
* reworked "force_replace_urls" to the new options patterns (boolean)

= 3.0.1 =

* prevent error on merge excludes if not an array
* bust object cache after migration

= 3.0 =

* new admin UI for settings
* new admin UI for diagnostics
* new admin UI for generate
* ported ajax requests to Rest API for better performance and maintainability
* improved diagnostics with better (and more meaningful) error messages
* conditional settings for certain setups
* improved support for Windows Azure (path conversion)
* auto-migration from old settings + manuall trigger
* import and export settings via JSON