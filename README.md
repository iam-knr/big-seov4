=== Big SEO Programmatic ===
Contributors: kailasnathr
Tags: programmatic-seo, bulk-page-generator, csv, seo, location-pages
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 2.4.1
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate thousands of SEO pages from CSV. The #1 Programmatic SEO bulk page generator for WordPress — free, unlimited, no code needed.

== Description ==

**Big SEO Programmatic** is the fastest way to create thousands of location-based, service-based, or data-driven pages at scale — without touching code.

Whether you're an agency running multi-location SEO campaigns, an SEO professional building niche programmatic pages, or a marketer scaling content production, this plugin handles all your programmatic SEO needs from a single CSV file.

With a simple CSV upload you can generate unlimited SEO-optimised pages with unique titles, meta descriptions, canonical URLs, and JSON-LD schema — making it the most powerful free programmatic SEO plugin available for WordPress today.

= What is Programmatic SEO? =

Programmatic SEO is the strategy of generating large volumes of web pages automatically from a data source like CSV. Instead of writing each page manually, you define a template, connect your data, and publish thousands of unique, keyword-targeted pages in minutes. Sites like Tripadvisor, Zillow, and NerdWallet use programmatic SEO to dominate long-tail search at scale.

**Big SEO Programmatic** brings this enterprise-level strategy to any WordPress site — no coding, no limits, 100% free.

= Core Features =

* **Unlimited rows** — no artificial page limits; generate 1 to 100,000+ pages from a single CSV
* **Unlimited projects** — run multiple programmatic SEO campaigns simultaneously
* **2 CSV data sources** — CSV via URL or CSV file upload (server path)
* **Smart update engine** — existing pages are updated, not duplicated on re-run
* **Orphan detection** — auto-delete pages removed from the data source
* **Any post type** — Pages, Posts, or any registered custom post type

= Template Engine =

Build powerful bulk page templates with our intuitive placeholder system:

* `{{placeholder}}` — column value substitution (HTML-escaped)
* `{{raw:placeholder}}` — unescaped HTML column output for rich content
* `{Option A|Option B|Option C}` — spintax for natural content variation across generated pages
* `[if:column=value]...[/if]` — conditional blocks for dynamic, data-driven content
* Supports `=`, `!=`, `>`, `<`, `>=`, `<=` operators for precise conditionals

= Programmatic SEO Features =

* Custom title tag and meta description per page — unique SEO metadata for every generated page
* Robots meta control — set index/noindex per programmatic SEO project
* Canonical URL auto-injected on every generated page to prevent duplicate content
* **6 Schema types** — Article, LocalBusiness, Product, FAQPage, BreadcrumbList, JobPosting
* JSON-LD schema output injected in `<head>` for full structured data SEO coverage
* Custom XML sitemap at `/pseo-sitemap.xml` — submit to Google Search Console for fast indexing
* Fully compatible with **Yoast SEO** and **Rank Math** — no conflicts with existing SEO setup

= Automation & Bulk Page Generation =

* **Auto-sync** — run bulk page generation on hourly, daily, or weekly WP Cron schedules
* **WP-CLI support** — generate, delete, and list pages from terminal for large-scale bulk operations
* **Scheduled cron runs** independently of user action — set-and-forget programmatic SEO automation

= Programmatic SEO Use Cases =

* **Local SEO pages** — generate "Best [Service] in [City]" pages targeting every location in your CSV
* **Service combination pages** — "[Service] for [Industry]" bulk pages at unlimited scale
* **Product catalogue pages** — individual SEO pages for every SKU or product variation
* **Job listing pages** — bulk generate job postings with JobPosting structured data schema
* **Real estate listings** — property pages with LocalBusiness and Product schema
* **Niche affiliate sites** — data-driven, long-tail keyword pages from any CSV dataset
* **Multi-location agency SEO** — run separate programmatic SEO projects per client from one install

= Developer Features =

* PSR-4 style class autoloader for clean, maintainable architecture
* `pseo_schema` filter hook — extend or override schema output per generated page
* Clean database with 3 custom tables; no post meta bloat
* Nonce-protected AJAX endpoints on all admin actions
* Full `manage_options` capability checks throughout
* All inputs sanitized, all outputs escaped — security-first bulk page generation
* **WordPress Plugin Check (PCP) compliant** — 0 errors, 0 warnings

== Installation ==

= Method 1 — Upload ZIP (Recommended) =
1. Download the plugin ZIP file
2. Go to **WP Admin > Plugins > Add New > Upload Plugin**
3. Select the ZIP > **Install Now** > **Activate**
4. Go to **Settings > Permalinks > Save Changes** (required to activate custom URL patterns)

= Method 2 — Manual FTP =
1. Upload `knr-pseo-generator/` to `/wp-content/plugins/`
2. Go to **Plugins > Installed Plugins** > **Activate**
3. Go to **Settings > Permalinks > Save Changes**

= Method 3 — WP-CLI (Large-Scale Bulk Installs) =

    wp plugin install /path/to/knr-pseo-generator.zip --activate
    wp rewrite flush

= Quick Start: Your First Programmatic SEO Campaign =
1. After activation, navigate to **Big SEO Programmatic** in your WP Admin menu
2. Click **Add New Project**
3. Upload your CSV file or paste a CSV URL
4. Set your URL pattern using column placeholders — e.g., `/services/{{service}}/{{city}}/`
5. Configure your page title template, meta description template, and schema type
6. Click **Generate Pages** — all bulk pages go live instantly

== Frequently Asked Questions ==

= What is Programmatic SEO and how does this plugin help? =
Programmatic SEO is the process of auto-generating hundreds or thousands of SEO-optimised pages from structured data (like CSV). This plugin is a dedicated programmatic SEO tool for WordPress — you provide the CSV and a page template, and it handles bulk page generation, SEO metadata, schema injection, and automatic sitemap updates.

= How is this different from other bulk page generator plugins? =
Most bulk page generators create pages but ignore SEO entirely. Big SEO Programmatic is built with SEO-first architecture — every generated page gets a unique title tag, meta description, canonical URL, robots directive, and JSON-LD schema. It also includes smart orphan detection, WP-CLI support, unlimited rows, and a custom XML sitemap — all for free.

= Can I use this for local SEO page generation at scale? =
Absolutely. Local SEO is one of the primary use cases for this programmatic SEO plugin. Create a CSV with city, service, and any other location data columns, build a template page, and generate hundreds of "[Service] in [City]" pages in minutes. Each page gets its own unique title, meta, and LocalBusiness schema.

= Does it work with Elementor or Divi page builders? =
Yes. Set an Elementor or Divi template as the Content Template for your project — all `{{placeholders}}` inside the page builder content will be substituted automatically during bulk page generation.

= Will it conflict with Yoast SEO or Rank Math? =
No. Big SEO Programmatic injects meta tags at priority 1, ensuring no conflicts with Yoast SEO or Rank Math. Both SEO plugins can coexist on the same WordPress install running programmatic SEO campaigns.

= Is there a page limit for bulk page generation? =
No plugin-imposed limit. Generate as many pages as your CSV has rows. For very large programmatic SEO campaigns (10,000+ pages), use WP-CLI to avoid PHP timeouts.

= Can I use custom post types for generated pages? =
Yes. Any public custom post type registered in WordPress appears in the post type dropdown — ideal for programmatic WooCommerce products, portfolio entries, staff directories, or custom listing types.

= Can I schedule automatic bulk page updates from CSV? =
Yes. Configure your project to auto-sync hourly, daily, or weekly via WP Cron. The bulk page generator will re-process your CSV automatically, updating existing pages and deleting orphaned ones — fully automated programmatic SEO.

= Does it auto-generate an XML sitemap for generated pages? =
Yes. A dedicated XML sitemap is automatically maintained at `/pseo-sitemap.xml` listing all your programmatic SEO pages. Submit this URL directly to Google Search Console for rapid indexing.

= What JSON-LD schema types are supported? =
Big SEO Programmatic supports 6 schema types: **Article**, **LocalBusiness**, **Product**, **FAQPage**, **BreadcrumbList**, and **JobPosting** — the most commonly needed structured data types for programmatic SEO page templates.

= Can I run multiple programmatic SEO campaigns from one install? =
Yes. Create unlimited projects, each with its own CSV source, URL pattern, template, schema type, and sync schedule. Manage all your bulk page generation campaigns from a single dashboard.

= Why were JSON URL and REST API sources removed? =
The plugin is now focused exclusively on CSV-based programmatic SEO workflows. CSV is the most universally supported data format for bulk page generation and keeps the configuration interface simple and reliable.

== Changelog ==

= 2.4.1 =
* Updated CSV via URL field placeholder to `https://yourdomain.com/yourdatasheet.csv` for clarity
* Fixed `esc_html` → `esc_attr` on CSV URL value output (security/correctness fix)
* Added helper description text below the CSV URL input field

= 2.4.0 =
* Minor bug fixes and stability improvements

= 2.2.0 =
* Renamed plugin to "Big SEO Programmatic" to better reflect its programmatic SEO and bulk page generator capabilities
* Simplified data sources to CSV only (URL + server path) for focused programmatic SEO workflows
* Fixed critical white screen bug on project save during bulk page generation
* Fixed source_config double-encoding via json_decode() in programmatic SEO save handler
* Fixed orphaned delete_orphans call in class-pseo-ajax.php
* Fixed AJAX JS not loading on admin pages for bulk page generator UI
* Fixed JS object name mismatch in wp_localize_script
* Fixed all text domain mismatches throughout admin class
* UI fix — project action buttons now display in 2x2 grid layout
* Security — added wp_unslash() to all $_POST reads in bulk page generation forms
* Security — all outputs escaped with esc_attr / esc_html / esc_url across programmatic SEO views
* WordPress Plugin Check (PCP) compliant — 0 errors, 0 warnings

= 2.0.1 =
* Fixed text domain mismatch in programmatic SEO admin views
* Fixed README missing WordPress.org required plugin headers
* Removed deprecated load_plugin_textdomain() call from bulk page generator bootstrap
* Fixed unescaped output variables in generated page view templates
* Added nonce verification to all AJAX handlers in the bulk page generator
* Prefixed global variables in view templates to prevent conflicts

= 1.0.0 =
* Initial release of the programmatic SEO plugin for WordPress
* Template engine with CSV-driven placeholders, spintax, and conditional blocks for bulk page generation
* 6 JSON-LD schema types injected on all programmatic SEO generated pages
* Custom XML sitemap auto-generated for all bulk pages — ready for Google Search Console
* WP-CLI integration for command-line bulk page generation at any scale
* Hourly WP Cron auto-sync for fully automated programmatic SEO campaigns
* Full AJAX admin interface for managing programmatic SEO projects
* Nonce-protected AJAX security on all bulk page generator endpoints

== Upgrade Notice ==

= 2.4.1 =
UI clarity update. CSV URL placeholder now shows `https://yourdomain.com/yourdatasheet.csv`. Includes minor security fix for attribute escaping.

= 2.4.0 =
Minor bug fixes. Recommended update for all users.

= 2.2.0 =
Major stability release for all programmatic SEO users. Resolves white screen bug on bulk page generation, AJAX save failure, JS loading issues, and UI overflow. Plugin renamed to Big SEO Programmatic. Strongly recommended for all users.

= 2.0.1 =
Security hardening and WordPress Plugin Check compliance update for the bulk page generator. Recommended for all users on 1.0.0.
