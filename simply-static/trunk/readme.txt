=== Simply Static ===
Contributors: codeofconduct
Tags: simply static, static, html, static site, performance, fast, optimization, availability, scaling, scalability, cdn, security, secure, zip
Requires at least: 3.0.1
Tested up to: 4.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create a static copy of your Wordpress site that you can serve on your favorite web server.

== Description ==

Do you love working with WordPress but wish you didn't have to be constantly vigilant about keeping it secure? What if, with a little bit of initial setup, you could make your WordPress installation virtually impervious to hackers and, as an added bonus, also make your site incredibly fast? Meet Simply Static.

Simply Static creates a static copy of your WordPress site. Keep your WordPress installation hidden from the public and publish your static site to your favorite web server. And since your static site is just a collection of files with no server-side code or database, it runs incredibly fast and prevents most forms of hacking.

== Installation ==

1. Log into your WordPress website.
2. On the left menu, hover over Plugins and then click on Add New.
3. In the Search Plugins box, type in "Simply Static" and press the Enter key.
4. You will see a list of search results which should include the Simply Static plugin. Click on the Install Now button to install the plugin.
5. After installing the plugin you will be prompted to activate it. Click on the Activate Plugin link.
6. The Simply Static plugin is now installed and can be found on the left menu.

or

1. Upload the entire `simply-static` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Who should use Simply Static? =

Simply Static is great for sites with no user interactivity, such as blogs (with comments disabled) or brochure-ware sites for small businesses (with no forms).

= What does Simply Static do? =

Simply Static generates static (HTML) copies of your WordPress pages. It works a bit like a web crawler, starting at the main page of your website and looking for links to other pages to create static copies of. It also includes any images, CSS & JS files, and any other files that it can find a link to.

As Simply Static is creating the static pages, it will automatically replace the URL for the WordPress installation with the URL for the site that you're going to have hosting the static files. So if WordPress is hosted at wordpress.example.com and your static site will be at www.example.com, all instances of wordpress.example.com will be replaced with www.example.com.

= How do I set up Simply Static? =

Let's assume you presently have WordPress hosting a site at www.example.com, and that's where you'd like to have your static site instead. Your first task is going to be to move your WordPress installation to a subdomain, such as wordpress.example.com. Once that is complete, you'll set up www.example.com to receive your static files.

If www.example.com is on the same server as your WordPress installation, you can have Simply Static copy the static files to the directory that www.example.com is serving files from. If www.example.com is on a different server, you can download a zip of your static files and then upload them to www.example.com.

In the future we'll add more ways to transfer your files to other servers, such as FTP, SFTP, and SCP, and add support for specialty destinations like Amazon S3.

= Will this plugin interfere with other plugins? =

Simply Static's output is just a collection of static files: HTML, CSS, JS, images, etc. Anything that is publicly accessible and would require PHP in order to function will not work. That includes, but is not limited to: blog post comments, contact forms, forums, membership areas, and eCommerce.

Note that you can achieve some of this functionality by using plugins that interact with third-party services. For example, for blog post comments you could use [Disqus](https://wordpress.org/plugins/disqus-comment-system/) and for forms you could use [Wufoo's plugin](https://wordpress.org/plugins/wufoo-shortcode/).

= How is Simply Static different from cache plugins? =

Cache plugins -- such as W3 Total Cache or WP Super Cache -- make your existing WordPress site faster by caching pages as they're visited. This makes your site much faster, but still leaves your WordPress installation accessible to the outside world. Unless you keep on top of updates, your WordPress installation can become vulnerable to hackers due to security vulnerabilities that are found over time.

Simply Static creates a copy of your WordPress site that is intended to be used completely separately from your WordPress installation. Your WordPress installation lives on one server and your static site is served on a different server. Or, they're both on the same server, but your WordPress installation is restricted to only allow access from certain ip addresses or with an additional username/password requirement. Your static site is just a collection of static files with no server-side code or database -- nothing for hackers to hack -- while your WordPress installation remains safe and secure.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0.0 =

* Initial release
