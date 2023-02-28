=== Simply Static ===
Contributors: patrickposner
Tags: HTML, static website generator, static site, secure, fast
Requires at least: 5.4
Tested up to: 6.1
Requires PHP: 7.4
Stable tag: 2.2.7
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

== Simply CDN ==

Simply CDN is a hosting platform for static sites generated with Simply Static.

Create an account, copy & paste the Security Token into Simply Static, and immediately push your static website live.

[youtube https://youtu.be/7ejlvDxXMXM]

Simply CDN also ships with a bunch of features to simplify the process of working with your static website:

* handles caching and security
* temporary domains for setup and testing
* forward form submissions to your e-mail address
* handles 404 pages with a custom template
* automatically export changes in the background

It's built on BunnyCDN and combines affordable static site hosting with everything learned while developing Simply Static.

Start your 7-day free trial [here](https://simplycdn.io/#pricing).


== Simply Static Pro ==

The pro version of Simply Static enhances the plugin with various features.

[youtube https://www.youtube.com/watch?v=PwdO6B7Iet0]

= GitHub Integration =

With the GitHub integration, you can completely automate your entire development process. 

The only thing you need is a GitHub account, no knowledge of Git or GitHub itself is required, as Simply Static Pro handles the entire process for you and keeps your repository up to date.

This allows deploying your static website to:

* GitHub Pages
* Cloudflare Pages
* Netlify
* Vercel
* Amazon S3
* Digital Ocean Spaces
* BunnyCDN (and other CDN providers)
* SFTP to your server


= Builds and Single Exports =

Create builds to export only a subset of pages/posts. You can assign a build to every custom post type in your WordPress admin area and export them.

Never sit back and watch a full static export running, instead run a complete export once and then use builds to export the changes as fast as possible.

You can also export single pages/posts after changed something.


= Forms =

Simply Static Pro supports Contact Form 7 and Gravity Forms to use on your static website.

You can decide if you want to use an external service like Zapier and IFTTT or if your original WordPress website should handle the submission and send you an e-mail with all the details.


= Search =

Use a fully static search solution that does not rely on any Third-Party-Providers. It uses Fuse.js and creates an index of your whole website as a JSON file.

The search supports autosuggestion and fuzzy logic and is by default way more convenient than your default WordPress search.


= Comments =

Enable comments on your static website without the requirement of Third-Party-Providers. Use your original WordPress website to process the comment.

After a comment was submitted, Simply Static automatically exports a new static version of the post. You don't have to do anything.


= Multilingual =

Use WPML, Polylang or TranslatePress and run static exports per language or all at once.

Simply Static Pro also supports configuring settings per language, using the available language switchers and more to export a your multilingual website as easy as possible.


= Get the Pro version =

You can get the pro version [here](https://patrickposner.dev/plugins/simply-static/).

= Tutorials =

I publish new tutorials on how to work with Simply Static and other tools on my blog.

You can check the current tutorials [here](https://patrickposner.dev/tutorials/tag/simply-static/)


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

= 2.2.7 =

* SimplyCDN integration
* added hooks for multisite integration
* Elementor Pro support (Lottie files and videos)
* fixed log time with correct timezone
* removed WP-Cron option, now handled automatically

= 2.2.6 =

* fixed typo in CookieYes integration class

= 2.2.5 =

* integration for CookieYes GPDR plugin
* integration for Brizy Builder (image extraction)
* fixed 301 redirections with Redirection plugin
* fallback solution for existing pages not being crawled due to URL parameter

= 2.2.4 =

* reverted blackslash extraction producing invalid markup in CSS + JS
* remember configuration for destination (while switching options)
* unique nonce for Simply Static Ajax request to fix conflict with MiniOrange SSL On plugin
* removed simply_static_page query parameters from URL on static site
* automatically remove quotation marks on save additional URL/File

= 2.2.3 =

* min PHP version increased to 7.4
* ability to include files without filetype (_header)
* Elementor support for bundled JS files
* improved URL extraction in JS and HTML files

= 2.2.2 =

* XML Sitemap auto-include for Yoast
* XML Sitemap auto-include for RankMath
* XML Sitemap auto-include for AllInOneSEO
* XML Sitemap auto-include for SEOPress

= 2.2.1.1 =

* improved path conversion for Windows
* more fail-safe check for deleting local directory before export
* WPCS code style updates

= 2.2.1 =

* fixed path resolution on local path exports

= 2.2 =

* improved URL replacement in script tags
* fixed clear directory before export when using local path exports
* added action fired after ZIP file created
* fixed path replacement for Windows filesystems in Additional Files and Directories
* untrailingslash path on transfer files locally

= 2.1.9 =

* flag for cron execution with UNIX cron
* additional save button in advanced settings
* added filter to change the zip file name
* increased refresh time for Ajax ping

= 2.1.8 =

* new option to replace URLs with regex
* new option to clear local directory before running a new export
* decreased default batch size to prevent timeouts
* decreased ping timer for faster log views
* added filter to modify remote_get arguments
* add actions to send messages/notifications based on task progress (ss_completed)

= 2.1.7.1 =

* added exception handling for <style> and <script> tag extractions

= 2.1.7 =

* improved Basic Auth handling allowing access for external APIs
* parse content of script tags for URL replacements
* improved tags with HTML5 tags for header, footer, section and figure
* added style attributes to match_tags for a, div, section and more

= 2.1.6 =

* adding <link> and <meta> tags to extractor class
* validating URLs in meta tags and update them

= 2.1.5.9 =

* better decoding/encoding of URLs
* added filter to modify default file extension
* improved tag based extraction and removed catch all
* added <picture> tags to extractor
* increased default batch size
* file path check improvements

= 2.1.5.8 =

* added SSL to diagnostics
* added filter for origin URL
* increased general ping time
* Windows support: replacing paths

= 2.1.5.7 =

* http_request_args filter added for better performance with Basic Authentication
* Windows compatibility fix for path conversion
* reverted delete local directory for local dir exports (original merge request)
* added filter for options getter
* cleaned up WP Cron conditional

= 2.1.5.6 =

* fixed non-valid HTML character error
* UI improvement for running static exports
* better http_request_args filter usage
* ss_before_static_export action added
* udpdated dependencies

= 2.1.5.5 =

* cleaned up upgrade handler

= 2.1.5.4 =

* reverted meta tags in extraction

= 2.1.5.3 =

* webp support (source tag)
* imagify support
* action to trigger functions before static export
* added meta tags to extractor for twitter cards and og tags
* added filter to extend matched_tags
* added action to run before static export
* updated composer dependencies

= 2.1.5.2 =

* introduced ss_finished_fetching_pages hook
* introduced ss_finished_transferring_files_locally hook
* fixed DB migration process performance issue
* small CSS fixed for actions class

= 2.1.5.1 =

* fixed migration on reset for new post_id db field

= 2.1.5 =

* default export without cron
* fixed generation of RSS feeds
* better support for Yoast XML Sitemap in additional URLs
* added option to change cron on/off
* better URL validation
* Windows support for slash URLs
* actions for custom parsers (Blocksy theme compatibility)
* enhanced matched_tags list (added srcset)
* fixed upgrader class for DB migration
* smaller CSS admin improvements
* fixed Logo saving it without font requirements
* better additional URL validation checks
* removed send debug log mail
* modified method visibility for fetch URLs task
* added complete german translations

= 2.1.4.2 =

* removed more commas for apply_filters to prevent execution error on some environments

= 2.1.4.1 =

* removed comma for apply_filters to prevent execution error on some environments

= 2.1.4 =

* new admin UI
* check for WP cron
* check for PHP XML extension
* fallback if cron not available
* CSS improvements


= 2.1.3 =

* dispatch execution to cron for larger exports
* fixed download URL for zip files
* added support for custom head tags added in wp_head hook
* parsing Rankmath sitemap if added to additional files
* removing uploads folder from additional files setting

= 2.1.2.1, February 04, 2021 =

* increased min PHP version to 7.2.5
* wp_die() if lower PHP version is available
* increases min version in diagnostics

= 2.1.2, February 04, 2021 =

* added composer support
* updated dependencies
* more fail-safe bootup
* Added sorting by status code
* removed wp_mail_content_type filter
* Fixed PHP notices for PHP 7.4 support
* new composer package for simplehtmldom with Symfony Finder
+ improved doc blocks for models and tasks
* auto exclude wp-JSON and wp-login.php from export
* trailing slash / untrailingslash check pages

= 2.1.1, December 20, 2020 =

* Fixed PHP Dom parser for later PHP versions
* removed PHP faker due to end of development
* little color modifications in admin areas
* new plugin header and readme

= 2.1.0, May 12, 2017 =

* New: Added the ability to set a user/pass for HTTP Basic Authentication

= 2.0.7, April 7, 2017 =

* Fix: Non-Latin characters should no longer get converted to HTML entities (thanks xi80r6!)

= 2.0.6, April 4, 2017 =

* Fix: Simply Static should now display a useful error message when using PHP <5.3

= 2.0.5, March 25, 2017 =

* Fix: Fix for downloading 0-byte ZIPs (no Content-Length header)

= 2.0.4, March 25, 2017 =

* Fix: Moving the default temp files directory back to within the plugin

= 2.0.3, March 16, 2017 =

* Fix: Protocol-relative URLs now work again

= 2.0.2, January 18, 2017 =

* Fix: Individual additional files are now properly included in static file generation
* New: Added a diagnostics check to ensure that WordPress can make calls to itself

= 2.0.1, January 15, 2017 =

* Fix: Removed Simply Static's new error catching; was catching errors in other plugins

= 2.0.0, January 14, 2017 =

* New: New logo/icon (thanks Hajo!)
* New: Generate static files without having to stay on the Generate page (a job runs in the background)
* New: Added the ability to specify URLs (or parts of URLs) to exclude from static file generation
* New: Added a debugging mode with the ability to create/email a debug log
* New: Added the ability to reset the plugin settings back to their defaults
* New: Future versions will be able to downgrade to 2.0.0 (by overwriting files) without errors
* New: Friendly error message if trying to use Simply Static with PHP < 5.3
* New: Added support for Accelerated Mobile Pages (AMP) HTML attributes
* Fix: URLs containing the WP URL as a query param will rewrite the param with the new URL
* Fix: Non-WP URLs in the Additional URLs section no longer cause Simply Static to hang
* Fix: Zip creation will no longer throw errors when trying to process 0-byte files
* Fix: Fixed an issue that could cause Simply Static to not remove all files when uninstalling
* Fix: Local directories are now always chmod'd to 0755 so that they're readable
* Fix: Additional errors will be caught and displayed when generating static files

= 1.8.0, November 01, 2016 =

* Fix: XML pages (e.g. /feed/) now generate index.xml files
* Fix: XML pages now replace URLs properly
* Fix: No longer adding extra backslashes (\) to directories with backslashes
* Fix: No longer adding extra slashes (/) on user-specified additional directories

= 1.7.1, October 21, 2016 =

* New: Auto-adding wp-content/uploads as a directory to include files from
* New: Usage of the mbstring PHP extension is now optional
* New: Added links for support, rating, and changelog in the footer
* Fix: Added protection for cross-site request forgery (thanks pluginvulnerabilities.com!)
* Fix: Now sanitizing all user inputs
* Fix: Locking down generation of static files to users with proper permissions
* Fix: The check for trailing slash redirection now works with relative URLs
* Fix: "Cannot redeclare class PclZip" error should no longer occur

= 1.7.0, October 06, 2016 =

* New: Destination URLs can now begin with // (in addition to HTTP:// & HTTPS://)
* New: You can now use relative URLs (instead of absolute URLs) for the static site
* New: Now able to export a static site for use offline

= 1.6.3, September 23, 2016 =

* Fix: Eliminated a security vulnerability in relation to zip downloads (thanks Bas!)
* Fix: SQL diagnostic checks now work with wildcard permission grants (thanks Jon!)

= 1.6.2, July 14, 2016 =

* Fix: Made a slight modification to the prior fix

= 1.6.1, July 14, 2016 =

* Fix: No more "Call to a member function find() on a non-object" error. Thanks, jwatkins0101!

= 1.6.0, June 07, 2016 =

* Improvement: The DomDocument PHP extension is no longer required (replaced by SimpleHtmlDomParser)
* Fix: No longer creating empty html attributes
* Fix: No longer throwing the 'Function name must be a string' error in diagnostics. Thanks, Andrew-s!

= 1.5.1, June 05, 2016 =

* Fix: Removing usage of composer, which was causing issues for certain PHP versions

= 1.5.0, June 02, 2016 =

* Fix: The img srcset attribute now displays all urls (instead of only the last one)
* Improvement: Less memory usage; less likely to exhaust allowed memory size
* Improvement: Export log is now paginated / less likely to overwhelm your browser

= 1.4.1, April 28, 2016 =

* Fix: Displaying exception messages instead of a generic error message

= 1.4.0, April 26, 2016 =

* New: French translations. Thanks Pierre!
* New: Diagnostics page displays pass/fail for everything needed for SS to run correctly
* New: Auto-adding WP's emoji url to Additional URLs
* New: If a PHP error is encountered during processing it will be displayed
* Improvement: If you can edit posts, you can generate a static site
* Fix: Bad additional URLs could cause an infinite loop during static file generation
* Fix: Unprocessable pages are now properly removed on subsequent static file runs

= 1.3.5, April 14, 2016 =

* Fix: Simply Static was sometimes throwing an error on a certain WP action

= 1.3.4, April 14, 2016 =

* Fix: URLs were not getting properly replaced

= 1.3.3, April 13, 2016 =

* Fix Attempt #3 at fixing Simply Static's upgrading process :'(

= 1.3.2, April 12, 2016 =

* Fix Attempt #2 at fixing Simply Static's upgrading process :(

= 1.3.1, April 12, 2016 =

* Fix: Attempt #1 at fixing Simply Static's upgrading process

= 1.3.0, April 9, 2016 =

* New: Static archive creation handled via AJAX; PHP timeouts are a thing of the past
* New: Activity Log - see what Simply Static is doing while it's working

= 1.2.4, March 25, 2016 =

* Fix: Link hashes (e.g. href='#section-three') will no longer be rewritten as full URLs

= 1.2.3, March 8, 2016 =

* Fix: HTTP and HTTPS on the same domain are treated as the same site (no redirect files will be created)
* New: Added support for the img tag's srcset attribute, used for responsive images

= 1.2.2, January 26, 2016 =

* Fix: Additional Files/Dirs now allows for usage of WP_PLUGIN_DIR and WP_CONTENT_DIR
* Fix: Able to use WordPress' Export feature with Simply Static installed

= 1.2.1, January 18, 2016 =

* Fix: index.html was not getting added to the static archive

= 1.2.0, January 16, 2016 =

* New: The export log now lists off all URLs, their http status codes, and the first page it was linked from
* New: You can specify additional files/directories to include in your static archive
* Fix: Switched libraries for ZIP generation, which should reduce extraction errors

= 1.1.3, December 7, 2015 =

* Fix: Able to fetch protocol-less URLs, e.g. href='//example.com/image.png'
* Fix: Static files won't be put in a subdirectory if your WP install is in a subdirectory
* Fix: Able to download ZIP regardless of whether files are on an accessible path on the server
* Fix: Escaped URLs, such as those used by WP's concatemoji, are now properly replaced with Destination URLs

= 1.1.2, October 22, 2015 =

* Fix: Now handling 302, 303, 307 HTTP codes for redirection in addition to 301
* Fix: Redirection now properly handles relative URLs

= 1.1.1, October 9, 2015 =

* Fix: No longer checking for valid SSL certificates on local requests (allows for self-signed certs to work)
* Fix: No longer creating redirect HTML pages for WP's 301 redirects from /path to /path/
* Fix: Improved URL extraction from CSS files, particularly from larger files

= 1.1.0, October 6, 2015 =

* New: Relative URLs in HTML and CSS files are now identified for static file creation
* New: 301 redirects are now identified and an HTML page is created to handle the redirection
* Fix: Repeatedly generating static files in a local directory should no longer throw errors
* Fix: System requirements issues should no longer continue to display an error for one pageview past when the error is resolved

= 1.0.2, October 4, 2015 =

* Fix: PHP 5.4 array initialization syntax caused errors in PHP <= 5.3

= 1.0.1, September 29, 2015 =

* Fix: Settings/options are now deleted upon uninstallation

= 1.0.0, September 21, 2015 =

* New: Initial release

== Upgrade Notice ==

= 1.7.1 =
This version fixes several minor security bugs. We recommend upgrading as soon as possible.

= 1.6.3 =
This version fixes a major security vulnerability. Please upgrade immediately.
