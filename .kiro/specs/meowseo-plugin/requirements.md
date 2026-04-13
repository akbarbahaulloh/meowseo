# Requirements Document

## Introduction

MeowSEO is a custom WordPress SEO plugin built to replace commercial plugins (Yoast Premium, RankMath Pro) with a lightweight, modular, bloat-free alternative. It is optimized for Google Discover, Google AI Overviews / SGE, and headless/decoupled WordPress via REST API and WPGraphQL. The plugin follows strict modular loading, database-level operations, and Gutenberg-native state management patterns to avoid the performance and bloat problems common in existing SEO plugins.

---

## Glossary

- **Plugin**: The MeowSEO WordPress plugin as a whole.
- **Module_Manager**: The PHP class responsible for conditionally loading and instantiating plugin modules based on enabled settings.
- **Module**: A self-contained feature unit (e.g., Meta, Schema, Sitemap, Redirects) that implements the Module interface and is only loaded when explicitly enabled.
- **Meta_Module**: The module responsible for managing per-post SEO meta fields (title, description, robots, canonical, etc.).
- **Schema_Module**: The module responsible for generating and outputting structured data (JSON-LD) for posts and pages.
- **Sitemap_Module**: The module responsible for generating and serving XML sitemaps.
- **Redirect_Module**: The module responsible for managing and executing URL redirects.
- **Monitor_404_Module**: The module responsible for logging 404 errors in a buffered, non-blocking manner.
- **Internal_Links_Module**: The module responsible for analyzing and reporting internal link structure.
- **GSC_Module**: The module responsible for integrating with Google Search Console via a rate-limited API queue.
- **Social_Module**: The module responsible for Open Graph and Twitter Card meta tag output.
- **WooCommerce_Module**: The module responsible for SEO enhancements specific to WooCommerce product pages.
- **Options**: The PHP class wrapping all plugin settings stored under the `meowseo_` option prefix.
- **Installer**: The PHP class handling DB table creation via `dbDelta()`, plugin activation, and deactivation.
- **Cache_Helper**: The PHP helper class abstracting WordPress Object Cache interactions.
- **DB_Helper**: The PHP helper class abstracting all `$wpdb` interactions with prepared statements.
- **Schema_Builder**: The PHP helper class constructing JSON-LD structured data arrays.
- **REST_API**: The plugin's custom REST endpoints registered under the `meowseo/v1` namespace.
- **Gutenberg_Store**: The custom Redux store registered under the `meowseo/data` JS namespace via `@wordpress/data`.
- **GSC_Queue**: The `{wp_prefix}meowseo_gsc_queue` database table used to throttle Google API calls.
- **404_Log**: The `{wp_prefix}meowseo_404_log` database table storing buffered 404 hit records.
- **Redirect_Table**: The `{wp_prefix}meowseo_redirects` database table storing redirect rules with indexed `source_url`.
- **Link_Check_Table**: The `{wp_prefix}meowseo_link_checks` database table storing internal link analysis results.
- **Object_Cache**: The WordPress object cache layer used for transient buffering and path storage.
- **WP-Cron**: The WordPress pseudo-cron system used for scheduled background tasks.
- **Sitemap_File**: A physical XML file stored under `wp-content/uploads/meowseo-sitemaps/`.
- **Focus_Keyword**: The primary keyword assigned to a post for SEO analysis.
- **Readability_Score**: A computed score reflecting the readability of post content.
- **SEO_Score**: A computed score reflecting the on-page SEO quality of a post.
- **WPGraphQL**: A third-party WordPress plugin exposing a GraphQL API; MeowSEO extends it with SEO fields.
- **Headless_Mode**: A configuration where WordPress serves only as a data backend, with a decoupled frontend consuming the REST API or WPGraphQL.

---

## Requirements

### Requirement 1: Plugin Bootstrap and Modular Loading

**User Story:** As a site administrator, I want the plugin to load only the features I have enabled, so that my site does not carry the performance overhead of unused functionality.

#### Acceptance Criteria

1. THE Plugin SHALL define a single entry point in `meowseo.php` that performs bootstrap only (autoloader registration, constant definition, and Module_Manager instantiation).
2. THE Module_Manager SHALL read the enabled module list from Options on each request and instantiate only the corresponding Module classes.
3. WHEN a Module is disabled in Options, THE Module_Manager SHALL not include or instantiate that Module's PHP files.
4. THE Plugin SHALL register an autoloader that resolves class files from the `includes/` directory following the WordPress class naming convention (`class-{name}.php`).
5. THE Installer SHALL run `dbDelta()` for all custom tables on plugin activation and SHALL remove all plugin data on uninstall when the "delete data on uninstall" option is enabled.
6. IF a required PHP version (8.0+) or WordPress version (6.0+) is not met, THEN THE Plugin SHALL deactivate itself and display an admin notice with the minimum version requirements.

---

### Requirement 2: Settings and Options Management

**User Story:** As a site administrator, I want a centralized settings interface, so that I can configure all plugin modules from one place without navigating multiple screens.

#### Acceptance Criteria

1. THE Options class SHALL store all settings as a single serialized array under the `meowseo_options` WordPress option key.
2. THE Options class SHALL expose typed getter and setter methods for each setting group (global, per-module).
3. WHEN a setting is saved via the admin UI, THE REST_API SHALL validate the nonce and verify `manage_options` capability before persisting the value.
4. THE Plugin SHALL provide a top-level admin menu page that renders a React-based settings UI loaded via the `meowseo-editor` asset handle.
5. WHERE the WooCommerce plugin is active, THE Options class SHALL expose WooCommerce-specific settings fields in the settings UI.

---

### Requirement 3: Per-Post SEO Meta Management

**User Story:** As a content editor, I want to set SEO title, meta description, robots directives, and canonical URL per post, so that I can control how each page appears in search results.

#### Acceptance Criteria

1. THE Meta_Module SHALL store all per-post SEO data in `wp_postmeta` using the `meowseo_` key prefix.
2. THE Meta_Module SHALL register a Gutenberg sidebar panel that reads from and writes to the Gutenberg_Store exclusively.
3. THE Gutenberg_Store SHALL expose a single content-sync selector that reads post content from `core/editor` and writes derived SEO signals to `meowseo/data`; no other component SHALL dispatch to `core/editor` from a `useEffect` that subscribes to `core/editor`.
4. WHEN a post is saved, THE Meta_Module SHALL persist Gutenberg_Store values to `wp_postmeta` via `useEntityProp` or a REST save hook.
5. THE Meta_Module SHALL output the SEO title tag, meta description, robots meta tag, and canonical link tag in the `<head>` on the frontend.
6. WHEN the SEO title field is empty, THE Meta_Module SHALL fall back to the post title formatted with the site title separator pattern defined in Options.
7. WHEN the meta description field is empty, THE Meta_Module SHALL fall back to the first 155 characters of the post excerpt or post content (stripped of HTML).
8. THE Meta_Module SHALL expose all per-post SEO meta fields via the `meowseo/v1` REST namespace for headless consumption.
9. WHERE WPGraphQL is active, THE Meta_Module SHALL register SEO fields on the WPGraphQL `Post` and `Page` types.

---

### Requirement 4: Focus Keyword Analysis

**User Story:** As a content editor, I want real-time SEO analysis of my content against a focus keyword, so that I can improve on-page optimization before publishing.

#### Acceptance Criteria

1. THE Meta_Module SHALL accept a Focus_Keyword input in the Gutenberg sidebar and store it in `wp_postmeta` under `meowseo_focus_keyword`.
2. WHEN the Focus_Keyword or post content changes, THE Gutenberg_Store SHALL recompute the SEO_Score without dispatching to `core/editor`.
3. THE SEO_Score computation SHALL check: Focus_Keyword presence in SEO title, meta description, first paragraph, at least one H2/H3 heading, and URL slug.
4. THE Meta_Module SHALL display the SEO_Score as a color-coded indicator (red / orange / green) in the Gutenberg sidebar based on the number of passing checks.
5. THE Meta_Module SHALL compute a Readability_Score based on average sentence length, paragraph length, and use of transition words, and SHALL display it alongside the SEO_Score.
6. WHEN the Focus_Keyword is changed, THE Gutenberg_Store SHALL reset and recompute all analysis checks.

---

### Requirement 5: Structured Data (Schema) Output

**User Story:** As a site administrator, I want structured data automatically generated for posts, pages, and custom post types, so that search engines can better understand my content for rich results and AI Overviews.

#### Acceptance Criteria

1. THE Schema_Module SHALL output a single `<script type="application/ld+json">` block per page containing all applicable schema graphs.
2. THE Schema_Builder SHALL construct schema graphs for: `WebSite`, `WebPage`, `Article`, `BreadcrumbList`, `Organization`, and `Person` types.
3. WHEN a post type is `product` and WooCommerce is active, THE Schema_Module SHALL include a `Product` schema graph with price, availability, and review aggregate data.
4. THE Schema_Module SHALL allow per-post schema type override via a `meowseo_schema_type` postmeta field editable in the Gutenberg sidebar.
5. THE Schema_Builder SHALL produce valid JSON-LD that passes Google's Rich Results Test for each supported schema type.
6. WHERE WPGraphQL is active, THE Schema_Module SHALL expose the generated JSON-LD string as a `schemaJsonLd` field on WPGraphQL post types.
7. THE Schema_Module SHALL support `FAQPage` schema generated from a structured FAQ block or postmeta field.

---

### Requirement 6: XML Sitemap Generation

**User Story:** As a site administrator, I want an automatically generated XML sitemap, so that search engines can efficiently discover and index all my content.

#### Acceptance Criteria

1. THE Sitemap_Module SHALL generate one index sitemap and per-type child sitemaps (posts, pages, custom post types, taxonomies) as Sitemap_Files stored under `wp-content/uploads/meowseo-sitemaps/`.
2. THE Object_Cache SHALL store only the file paths of generated Sitemap_Files, not the XML content itself.
3. WHEN a post is published or updated, THE Sitemap_Module SHALL invalidate and schedule regeneration of the affected child sitemap via WP-Cron.
4. THE Sitemap_Module SHALL implement a lock pattern using the Object_Cache to prevent concurrent sitemap regeneration (cache stampede prevention).
5. WHEN the sitemap lock is held by another process, THE Sitemap_Module SHALL serve the existing Sitemap_File without regenerating.
6. THE Sitemap_Module SHALL respond to requests at `{site_url}/meowseo-sitemap.xml` by reading and serving the index Sitemap_File directly.
7. THE Sitemap_Module SHALL support `<image:image>` entries for posts with featured images.
8. WHEN a post's robots meta is set to `noindex`, THE Sitemap_Module SHALL exclude that post from all sitemaps.

---

### Requirement 7: URL Redirects

**User Story:** As a site administrator, I want to manage URL redirects from a central interface, so that I can preserve link equity and fix broken URLs without editing server configuration files.

#### Acceptance Criteria

1. THE Redirect_Module SHALL store all redirect rules in the Redirect_Table with an indexed `source_url` column.
2. WHEN an incoming request URL matches a redirect rule, THE Redirect_Module SHALL perform the match at the database level using an exact-match query on the indexed `source_url` column first.
3. IF no exact match is found, THEN THE Redirect_Module SHALL evaluate regex rules stored in the Redirect_Table as a fallback, without loading all rules into PHP memory simultaneously.
4. THE Redirect_Module SHALL never load the full redirect rule set into a PHP array for iteration.
5. THE Redirect_Module SHALL support redirect types: 301, 302, 307, and 410 (Gone).
6. WHEN a redirect is triggered, THE Redirect_Module SHALL log the hit count and last-accessed timestamp in the Redirect_Table row via an `ON DUPLICATE KEY UPDATE` query.
7. THE Redirect_Module SHALL provide a REST endpoint under `meowseo/v1/redirects` for CRUD operations, verifying nonce and `manage_options` capability on all write operations.

---

### Requirement 8: 404 Error Monitoring

**User Story:** As a site administrator, I want 404 errors logged automatically, so that I can identify broken links and redirect opportunities without impacting site performance.

#### Acceptance Criteria

1. THE Monitor_404_Module SHALL detect 404 responses and buffer hit data in the Object_Cache using per-minute bucket keys rather than writing synchronously to the database.
2. THE Monitor_404_Module SHALL register a WP-Cron event that runs every 60 seconds to flush buffered 404 data from the Object_Cache to the 404_Log table.
3. WHEN flushing, THE Monitor_404_Module SHALL use a bulk `INSERT ... ON DUPLICATE KEY UPDATE` query to increment hit counts for existing URLs.
4. THE Monitor_404_Module SHALL record: requested URL, referrer URL, user agent string, and hit count per URL per day.
5. THE Monitor_404_Module SHALL provide a REST endpoint under `meowseo/v1/404-log` returning paginated log entries, accessible only to users with `manage_options` capability.
6. IF the Object_Cache is unavailable, THEN THE Monitor_404_Module SHALL fall back to a transient-based buffer with a 60-second expiry.

---

### Requirement 9: Internal Link Analysis

**User Story:** As a content editor, I want to see internal linking suggestions and a link health report, so that I can improve site structure and distribute link equity effectively.

#### Acceptance Criteria

1. THE Internal_Links_Module SHALL scan post content for internal links and store results in the Link_Check_Table with columns for source post ID, target URL, anchor text, and HTTP status code.
2. THE Internal_Links_Module SHALL schedule link checks via WP-Cron and SHALL not perform HTTP requests synchronously during post save.
3. WHEN a link check is complete, THE Internal_Links_Module SHALL update the HTTP status code in the Link_Check_Table.
4. THE Internal_Links_Module SHALL surface link suggestions in the Gutenberg sidebar based on keyword overlap between the current post's Focus_Keyword and other published posts' titles and meta descriptions.
5. THE Internal_Links_Module SHALL provide a REST endpoint under `meowseo/v1/internal-links` returning link health data for a given post ID, accessible to users with `edit_posts` capability.

---

### Requirement 10: Google Search Console Integration

**User Story:** As a site administrator, I want to view Google Search Console performance data inside WordPress, so that I can make SEO decisions without switching between tools.

#### Acceptance Criteria

1. THE GSC_Module SHALL authenticate with the Google Search Console API using OAuth 2.0 credentials stored encrypted in the WordPress options table.
2. ALL Google API calls from THE GSC_Module SHALL be enqueued in the GSC_Queue table rather than executed synchronously.
3. THE GSC_Module SHALL process a maximum of 10 GSC_Queue entries per WP-Cron execution.
4. WHEN a Google API response returns HTTP 429, THE GSC_Module SHALL apply exponential backoff by updating the `retry_after` timestamp in the GSC_Queue row.
5. THE GSC_Module SHALL store fetched GSC performance data (clicks, impressions, CTR, position) in a dedicated `{wp_prefix}meowseo_gsc_data` table indexed by URL and date.
6. THE GSC_Module SHALL provide a REST endpoint under `meowseo/v1/gsc` returning performance data for a given URL or date range, accessible to users with `manage_options` capability.
7. THE GSC_Module SHALL display a performance summary panel in the Gutenberg sidebar for the current post's URL.

---

### Requirement 11: Social Meta Tags (Open Graph & Twitter Cards)

**User Story:** As a content editor, I want to control how my content appears when shared on social media, so that I can maximize engagement with custom titles, descriptions, and images.

#### Acceptance Criteria

1. THE Social_Module SHALL output Open Graph meta tags (`og:title`, `og:description`, `og:image`, `og:type`, `og:url`) in the `<head>` for all public post types.
2. THE Social_Module SHALL output Twitter Card meta tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) in the `<head>` for all public post types.
3. THE Social_Module SHALL allow per-post override of social title, description, and image via postmeta fields editable in the Gutenberg sidebar.
4. WHEN a per-post social image is not set, THE Social_Module SHALL fall back to the post's featured image, then to the global default social image defined in Options.
5. THE Social_Module SHALL expose social meta fields via the `meowseo/v1` REST namespace for headless consumption.

---

### Requirement 12: WooCommerce SEO Enhancements

**User Story:** As a WooCommerce store owner, I want SEO features tailored to product pages, so that my products rank well and display rich results in search engines.

#### Acceptance Criteria

1. WHERE WooCommerce is active, THE WooCommerce_Module SHALL extend the Meta_Module to support SEO meta fields on `product` post type edit screens.
2. WHERE WooCommerce is active, THE Schema_Module SHALL output `Product` JSON-LD including `name`, `description`, `sku`, `offers` (price, currency, availability), and `aggregateRating` where reviews exist.
3. WHERE WooCommerce is active, THE Sitemap_Module SHALL include product URLs in the sitemap and SHALL exclude out-of-stock products when the "exclude out-of-stock" option is enabled in Options.
4. WHERE WooCommerce is active, THE WooCommerce_Module SHALL add SEO score columns to the WooCommerce product list table in the admin.

---

### Requirement 13: REST API and Headless / WPGraphQL Support

**User Story:** As a headless WordPress developer, I want all SEO data accessible via REST API and WPGraphQL, so that my decoupled frontend can render complete SEO metadata without a separate data source.

#### Acceptance Criteria

1. THE REST_API SHALL register all endpoints under the `meowseo/v1` namespace.
2. THE REST_API SHALL expose a `GET meowseo/v1/meta/{post_id}` endpoint returning all SEO meta fields for a given post, accessible to authenticated users with `read` capability.
3. THE REST_API SHALL expose a `POST meowseo/v1/meta/{post_id}` endpoint for updating SEO meta fields, verifying nonce and `edit_post` capability.
4. THE REST_API SHALL expose a `GET meowseo/v1/schema/{post_id}` endpoint returning the generated JSON-LD for a given post.
5. WHERE WPGraphQL is active, THE Plugin SHALL register a `seo` field on all WPGraphQL queryable post types returning an object with `title`, `description`, `robots`, `canonical`, `openGraph`, `twitterCard`, and `schemaJsonLd` sub-fields.
6. THE REST_API SHALL include `Cache-Control` headers on all GET responses to support CDN and edge caching in headless deployments.

---

### Requirement 14: Performance and Caching

**User Story:** As a site administrator, I want the plugin to have minimal impact on page load time and server resources, so that SEO tooling does not degrade the user experience.

#### Acceptance Criteria

1. THE Plugin SHALL not execute any database queries on the frontend for posts whose SEO meta is fully cached in the Object_Cache.
2. THE Cache_Helper SHALL use a consistent key prefix `meowseo_` for all Object_Cache entries and SHALL support cache group isolation.
3. WHEN the Object_Cache is unavailable, THE Cache_Helper SHALL fall back to WordPress transients without throwing errors.
4. THE Plugin SHALL enqueue frontend CSS or JavaScript assets only when a module that requires them is active and the current page requires them.
5. THE Sitemap_Module SHALL serve Sitemap_Files by reading from the filesystem, bypassing WordPress template loading entirely.
6. THE Plugin SHALL not register any `wp_loaded` or `init` hooks that load all redirect rules or all postmeta into memory.

---

### Requirement 15: Security

**User Story:** As a site administrator, I want the plugin to follow WordPress security best practices, so that it does not introduce vulnerabilities into my site.

#### Acceptance Criteria

1. ALL database queries in THE Plugin SHALL use `$wpdb->prepare()` with parameterized placeholders.
2. ALL REST endpoints that mutate data SHALL verify a valid WordPress nonce before processing the request.
3. ALL REST endpoints that mutate data SHALL verify the current user has the required capability (`manage_options` or `edit_post` as appropriate) before processing the request.
4. ALL user-supplied values output to HTML SHALL be escaped using the appropriate WordPress escaping function (`esc_html()`, `esc_attr()`, `esc_url()`).
5. THE Installer SHALL use `dbDelta()` for all schema changes and SHALL never execute raw `CREATE TABLE` or `ALTER TABLE` statements outside of `dbDelta()`.
6. THE GSC_Module SHALL store OAuth credentials encrypted using WordPress's built-in secret keys and SHALL never expose raw credentials via any REST endpoint or admin page source.
