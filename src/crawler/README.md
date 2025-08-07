# Simply Static Crawler System

This directory contains the crawler system for Simply Static. The crawler system is responsible for discovering URLs on the WordPress site and adding them to the Simply Static page queue for processing.

## Overview

The crawler system is designed to be modular and extensible. It consists of:

1. A base `Crawler` class that defines the interface for all crawlers
2. Multiple crawler implementations that extend the base class
3. A `Crawlers` manager class that loads and runs all active crawlers

## How It Works

1. The `Discover_Urls_Task` task runs at the beginning of the static site generation process
2. It uses the `Crawlers` manager to load and run all active crawlers
3. Each crawler implements a `detect()` method that returns an array of URLs
4. The URLs are added to the Simply Static page queue for processing by the `Fetch_Urls_Task`

This approach is more efficient than the previous method which used `wp_remote_get()` to fetch each page, as it directly uses WordPress functions to get URLs without making HTTP requests.

## Creating a New Crawler

To create a new crawler:

1. Create a new file in the `crawler` directory with the naming convention `class-{type}-crawler.php`
2. Define a class that extends `Simply_Static\Crawler\Crawler`
3. Implement the required methods:
   - `__construct()` - Set the name and description
   - `detect()` - Return an array of URLs

Example:

```php
<?php

namespace Simply_Static\Crawler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Example Crawler class
 */
class Example_Crawler extends Crawler {

	/**
	 * Crawler ID.
	 * @var string
	 */
	protected $id = 'example';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Example URLs', 'simply-static' );
		$this->description = __( 'Detects example URLs.', 'simply-static' );
	}

	/**
	 * Detect example URLs.
	 *
	 * @return array List of example URLs
	 */
	public function detect() : array {
		$urls = [];

		// Add your URL detection logic here
		// Use WordPress functions to get URLs efficiently

		return $urls;
	}
}
```

## Available Crawlers

The following crawlers are included:

1. **Home_Crawler** - Detects the home page URL
2. **Post_Type_Crawler** - Detects URLs for all public post types (posts, pages, etc.)
3. **Taxonomy_Crawler** - Detects URLs for all public taxonomies (categories, tags, etc.)
4. **Archive_Crawler** - Detects yearly, monthly, and daily archive URLs
5. **Author_Crawler** - Detects author archive URLs
6. **Pagination_Crawler** - Detects pagination URLs for archives, posts, and other paginated content
7. **Plugin_Assets_Crawler** - Detects URLs for assets (CSS, JS, images) used by plugins
8. **Theme_Assets_Crawler** - Detects URLs for assets (CSS, JS, images) used by the active theme
9. **Sitemap_Crawler** - Detects URLs from XML sitemaps
10. **Vendor_Files_Crawler** - Detects URLs for vendor files (third-party libraries, frameworks, etc.)

## Adding Custom Crawlers

You can add custom crawlers from your theme or plugin using the `simply_static_crawlers` filter:

```php
add_filter( 'simply_static_crawlers', function( $crawlers ) {
	// Add your custom crawler
	$crawlers[] = new My_Custom_Crawler();
	return $crawlers;
} );
```
