# MeowSEO — Gap Analysis vs Yoast SEO Premium & RankMath Pro

> Analysis based on reading the actual source code of:
> - **Yoast SEO Premium v27.3** (`reference-plugins/yoast-seo-premium`)
> - **RankMath Pro** (`reference-plugins/seo-by-rank-math-pro`)
> - **MeowSEO current** (`wp-content/plugins/meowseo`)

---

## Verdict

**MeowSEO cannot replace either plugin yet.** It covers the basics well but is missing
roughly half the features that define the premium tier. The gap is widest in
schema coverage, global settings, analytics, and import tooling.

---

## Current State of MeowSEO ✅

| Feature | Notes |
|---|---|
| SEO title / description / 1 focus keyword | Stored as `_meowseo_*` postmeta |
| Canonical URL, robots noindex/nofollow | Per-post |
| Open Graph + Twitter Card output | Full tag output via `Meta_Output` |
| XML sitemap (posts + terms + index) | Served directly, cached, pinged |
| JSON-LD schema — 6 types | Article, FAQ, HowTo, LocalBusiness, Product, Speakable |
| Google Search Console OAuth + indexing queue | Async via WP-Cron |
| Redirects | CRUD, regex, hit tracking, loop detection, async logging |
| 404 Monitor | Buffered logging, redirect suggestions |
| Internal link scanner + suggestions | DOMDocument extraction, REST suggestions |
| AI content generation | 5 providers: OpenAI, Anthropic, Gemini, DALL-E, Imagen |
| Gutenberg sidebar | 4 tabs: General, Advanced, Social, Schema |
| Classic editor meta box | Title, description, keyword, canonical, robots |
| SEO + readability analyzers | 11 SEO + 5 readability checks, runs in Web Worker |
| WooCommerce product schema + breadcrumbs | SEO score column, product JSON-LD |

---

## Gap Analysis

### 1. Multiple Focus Keywords

| Plugin | Max keywords | Storage |
|---|---|---|
| **Yoast Premium** | Unlimited | `_wpseo_focuskeywords` (JSON array) + `_wpseo_keywordsynonyms` |
| **RankMath Pro** | 5 (configurable filter) | `rank_math_focus_keyword` (comma-separated) |
| **MeowSEO** | **1** | `_meowseo_focus_keyword` |

**What's needed:** Add `_meowseo_secondary_keywords` (JSON array). Run every
analyzer (density, in-title, in-heading, in-slug, in-first-paragraph, in-description)
once per keyword and display per-keyword score rows in the sidebar.

---

### 2. Schema Types

| Plugin | Types supported |
|---|---|
| **Yoast Premium** | Article, Author, Breadcrumb, FAQ, HowTo, Image, Organization, Person, Webpage, Website |
| **RankMath Pro** | All of the above + Book, Course, Event, JobPosting, Movie, Music, Person, Recipe, Restaurant, Service, SoftwareApplication, VideoObject, ClaimReview, Dataset, Podcast + auto Video from YouTube/Vimeo |
| **MeowSEO** | **Article, FAQ, HowTo, LocalBusiness, Product, Speakable** |

**Missing types to add:**
- `Recipe` — critical for food/cooking sites
- `Event` — concerts, webinars, meetups
- `VideoObject` — auto-detect YouTube/Vimeo embeds
- `Course` — e-learning sites
- `JobPosting` — HR/careers
- `Book` — author/publisher sites
- `Person` — author bios, about pages
- `SoftwareApplication` — app landing pages
- `Organization` + `WebSite` — **global site identity schema** (see §9)

RankMath generates video schema by parsing YouTube/Vimeo embed URLs in content
(`includes/modules/schema/video/class-video-schema-generator.php`).

---

### 3. Global / Site-Wide Settings

**Yoast** has per-post-type noindex defaults + archive defaults.
**RankMath** has a "Global Meta" tab with robots for every archive type.
**MeowSEO has none** — robots can only be set post-by-post.

**Missing site-wide robots controls:**
- Author archive pages (`is_author()`)
- Date archives (`is_date()`)
- Tag archives vs. category archives
- Search results page (`is_search()`)
- Media attachment pages
- Custom post type archives

RankMath stores these in plugin options and resolves them in the robots filter.

---

### 4. Title & Description Patterns for Archives

**Yoast variables:** `%%title%%`, `%%sitename%%`, `%%category%%`, `%%tag%%`,
`%%term%%`, `%%date%%`, `%%name%%`, `%%searchphrase%%`, `%%posttype%%`, `%%sep%%`

**RankMath variables:** `%title%`, `%sitename%`, `%category%`, `%tag%`,
`%author%`, `%archive_title%`, `%date%`, `%page%`, `%sep%`

**MeowSEO:** Has a `Title_Patterns` class for post/page but no pattern system for:
- Category archives
- Tag archives
- Custom taxonomy archives
- Author pages
- Search results
- Date archives
- 404 pages
- Homepage (static or blog index)

---

### 5. WebSite + Organization Schema (Global Identity)

Both competitors output global structured data on **every page**:
- `WebSite` schema → powers sitelinks search box in Google
- `Organization` or `Person` → brand knowledge panel

**Yoast:** `src/generators/schema/website.php`, `organization.php`
**RankMath:** `includes/modules/schema/snippets/class-website.php`, `class-publisher.php`
**MeowSEO:** ❌ Not implemented.

This is one of the highest-ROI schema additions — it directly affects branded
search appearance.

---

### 6. Webmaster Tools Verification

| Service | Yoast | RankMath | MeowSEO |
|---|---|---|---|
| Google (meta tag) | ✅ `src/presenters/webmaster/google-presenter.php` | ✅ | ❌ |
| Bing | ✅ `bing-presenter.php` | ✅ | ❌ |
| Baidu | ✅ `baidu-presenter.php` | ❌ | ❌ |
| Yandex | ✅ `yandex-presenter.php` | ✅ `yandex_verify` option | ❌ |
| Pinterest | ✅ `pinterest-presenter.php` | ❌ | ❌ |
| Ahrefs | ✅ `ahrefs-presenter.php` | ❌ | ❌ |

MeowSEO has GSC OAuth but no `<meta name="google-site-verification">` tag method
or any other service.

---

### 7. Import from Competitor Plugins

**Yoast Premium imports:**
- Redirection plugin, Safe Redirect Manager, Simple 301 Redirects (for redirects)
- CSV keyword/redirect export

**RankMath Pro imports:**
- Yoast SEO → `includes/admin/importers/class-yoast.php`
- All in One SEO → `class-aioseo.php`
- SEOPress → `class-seopress.php`
- AIO Rich Snippets, WP Schema Pro
- Imports: settings, postmeta, termmeta, usermeta, redirections, schema blocks

**MeowSEO:** ❌ Zero import capability. Users switching from Yoast or RankMath
lose all their SEO data — this is the #1 adoption blocker.

**Minimum needed:** Map `_yoast_wpseo_title` → `_meowseo_title`,
`_yoast_wpseo_metadesc` → `_meowseo_description`,
`rank_math_title` → `_meowseo_title`, etc.

---

### 8. SEO Score Column in Post / Page Lists

**Yoast:** Shows colored bullet (red/orange/green) in `WP_List_Table` for every post type.
**RankMath:** `includes/admin/class-post-filters.php` — sortable column on `rank_math_seo_score` postmeta.
**MeowSEO:** Only implemented for WooCommerce products, not for posts/pages.

---

### 9. Bulk SEO Editing

**RankMath bulk actions** (`includes/admin/class-bulk-actions.php`):
- `rank_math_bulk_robots_noindex` / `_index`
- `rank_math_bulk_robots_nofollow` / `_follow`
- `rank_math_bulk_stop_redirect`
- `rank_math_bulk_schema_none` / `_default`
- `rank_math_bulk_remove_canonical`
- `rank_math_bulk_determine_search_intent`

**Yoast Premium:** CSV export of all keyphrases, titles, descriptions, scores.

**MeowSEO:** ❌ No bulk editing.

---

### 10. SERP Preview (Real-Time)

Both competitors show a live preview of how the post appears in Google results with:
- Real-time character counting (title ~60 chars, description ~155 chars)
- Color thresholds (red/orange/green)
- Mobile vs. desktop toggle

**MeowSEO:** `GeneralTabContent.tsx` references a SERP Preview component but
it has no live character counters or visual truncation simulation.

---

### 11. News Sitemap

**Yoast:** Separate Yoast News add-on (bundled with Premium license).
**RankMath Pro:** `includes/modules/news-sitemap/class-news-sitemap.php`
— dedicated `news-sitemap.xml`, Google News `<news:news>` elements,
`Googlebot-News` noindex meta tag support.
**MeowSEO:** ❌ No Google News sitemap.

---

### 12. Video SEO + Video Sitemap

**RankMath Pro:**
- `includes/modules/video-sitemap/class-video-sitemap.php`
- `includes/modules/schema/video/class-video-schema-generator.php`
- Parses YouTube, Vimeo, WordPress video blocks, Dailymotion, VideoPress, TED
- Outputs `VideoObject` JSON-LD + separate video sitemap

**Yoast:** Separate Video SEO add-on (bundled).
**MeowSEO:** ❌ No video detection, no VideoObject schema, no video sitemap.

---

### 13. IndexNow (Instant Indexing)

**RankMath Pro:** `includes/modules/instant-indexing/class-instant-indexing.php`
- API key management
- Auto-submit on publish/update
- Batch submission with 5-second throttle
- Submission history/logs

**Yoast Premium:** Not found in v27.3 codebase.
**MeowSEO:** Has GSC OAuth submission but no IndexNow protocol.

IndexNow instantly notifies Bing, Yandex, and Seznam — no OAuth required.

---

### 14. Image SEO

**RankMath Pro:** `includes/modules/image-seo/class-image-seo-pro.php`
- Auto alt-text from image title via `%imagetitle%` pattern
- Auto captions and descriptions
- Avatar alt-text injection
- Pattern replacement variables: `%imagealt%`, `%imagetitle%`
- Settings: `add_avatar_alt`, `add_img_caption`, `add_img_description`

**Yoast:** No dedicated image SEO (part of content analysis only).
**MeowSEO:** ❌ No automatic alt-text, no image attribute automation.

---

### 15. Analytics Dashboard (GA4 + GSC)

**RankMath Pro:** `includes/modules/analytics/class-analytics.php`
- Full Google Analytics 4 integration
- Combined GA4 + GSC dashboard
- Winning/losing keywords and posts
- Email reports with keyword/traffic summaries
- AdSense integration
- PageSpeed scores via Google API
- URL inspection tool

**Yoast Premium:** No GA4 — relies on GSC only.
**MeowSEO:** Has GSC OAuth but no GA4 integration and no combined analytics dashboard.

---

### 16. Keyword Rank Tracking

**RankMath:** Uses GSC data to show keyword position trends
(`includes/modules/analytics/class-keywords.php`).
**Yoast Premium:** Integrates with **Wincher** for rank tracking
(`src/integrations/third-party/wincher-keyphrases.php`).
**MeowSEO:** ❌ No keyword position tracking.

---

### 17. Local SEO

**RankMath Pro:** `includes/modules/local-seo/class-local-seo.php`
- Custom post type `rank_math_locations` for multi-location businesses
- LocalBusiness schema with coordinates (`rank_math_local_business_latitude/longitude`)
- KML file export for Google Maps
- Shortcodes: `[rank_math_address]`, `[rank_math_map]`, `[rank_math_opening_hours]`,
  `[rank_math_store_locator]`

**Yoast:** Separate Local SEO add-on (bundled).
**MeowSEO:** Has basic `LocalBusiness` schema form only — no multi-location CPT,
no map integration, no shortcodes.

---

### 18. Role Manager

**RankMath Pro capabilities** (`includes/modules/role-manager/class-capability-manager.php`):
`rank_math_titles`, `rank_math_general`, `rank_math_sitemap`,
`rank_math_404_monitor`, `rank_math_link_builder`, `rank_math_redirections`,
`rank_math_role_manager`, `rank_math_analytics`, `rank_math_site_analysis`,
`rank_math_onpage_analysis`, `rank_math_onpage_general`, `rank_math_onpage_advanced`,
`rank_math_onpage_snippet`, `rank_math_onpage_social`, `rank_math_content_ai`,
`rank_math_admin_bar`

**Yoast Premium:** `classes/premium-register-capabilities.php`
— `wpseo_manage_redirects` for editors/SEO managers.

**MeowSEO:** ❌ All features open to anyone who can `edit_posts`.

---

### 19. Multilingual / Hreflang

**Yoast:** `src/integrations/third-party/wpml.php`
— hreflang tag output, per-language SEO metadata.
**RankMath:** `includes/3rdparty/wpml/class-wpml.php`
— schema translation, redirects per language, meta sync.
**MeowSEO:** ❌ No WPML, no Polylang integration, no hreflang tags.

---

### 20. Multisite Support

**Yoast & RankMath:** Both support WordPress multisite network activation,
per-site settings, and network admin menus.
**MeowSEO:** ❌ No `is_multisite()` handling anywhere in the codebase.

---

### 21. Cornerstone / Pillar Content

**Yoast Premium:**
- `_wpseo_is_cornerstone` postmeta flag
- Stale cornerstone detection (>6 months) via
  `classes/premium-stale-cornerstone-content-filter.php`
- Admin column + filter in post list
- "Cornerstone workout" guided task

**RankMath:** `rank_math_pillar_content` postmeta — filter in post list,
weights link suggestions toward pillar pages.

**MeowSEO:** ❌ No pillar/cornerstone marking.

---

### 22. Orphaned Content

**Yoast Premium:** `classes/premium-orphaned-content-support.php`
— queries for posts with zero internal links, "Orphaned workout" guided task.

**RankMath:** Not found in Pro codebase.
**MeowSEO:** ❌ Not implemented.

---

### 23. Redirect Import/Export Formats

**Yoast Premium exports:** CSV, `.htaccess`, Apache, Nginx
**Yoast imports:** Redirection plugin, Safe Redirect Manager, Simple 301 Redirects CSV

**RankMath:** CSV import/export (`class-csv-import-export-redirections.php`),
`.htaccess` sync, scheduled/delayed redirects, query-parameter matching.

**MeowSEO:** Redirect admin UI exists but no CSV/htaccess import or export.

---

### 24. Robots.txt Editor UI

**Yoast:** `src/integrations/front-end/robots-txt-integration.php` — manages virtual robots.txt.
**RankMath:** `includes/modules/robots-txt/` — full UI editor in settings.
**MeowSEO:** `Robots_Txt` class exists but there is **no admin UI** to edit it
without code.

---

### 25. Frontend Admin Bar SEO Indicator

**RankMath:** `includes/admin/class-admin-bar-menu.php` — shows SEO score
for the current page in the WordPress admin bar when browsing the frontend.
**Yoast:** Not found in Premium-specific code.
**MeowSEO:** ❌ Not implemented.

---

### 26. AI Features Comparison

| Feature | Yoast Premium | RankMath Pro | MeowSEO |
|---|---|---|---|
| AI meta description | ✅ Summarizer (`src/ai/summarize/`) | ✅ Content AI module | ✅ |
| AI title | ✅ | ✅ | ✅ |
| AI content optimizer | ✅ (`src/ai/optimize/`) | ✅ | ❌ |
| AI image generation | ❌ | ❌ | ✅ (DALL-E, Imagen) |
| Providers | Yoast API (proprietary) | RankMath API | OpenAI, Anthropic, Gemini, DALL-E, Imagen |

MeowSEO is **ahead** of both competitors on AI — 5 self-configured providers,
image generation, and bring-your-own API key. This is a genuine differentiator.

---

### 27. Gutenberg Content Blocks

**Yoast Premium** blocks (`src/integrations/blocks/`):
- `EstimatedReadingTimeBlock`
- `RelatedLinksBlock`
- `SiblingsBlock` (show sibling posts)
- `SubpagesBlock` (show child pages)

**MeowSEO:** ❌ No Gutenberg blocks beyond the sidebar.

---

### 28. Per-Analyzer Fix Suggestions

**Yoast & RankMath:** Each failing check shows a "How to fix" explanation
with actionable steps.
**MeowSEO:** Analyzers return red/orange/green with a label but no fix text.

---

## Feature Comparison Scorecard

| Feature | MeowSEO | Yoast Premium | RankMath Pro |
|---|---|---|---|
| Basic on-page SEO | ✅ | ✅ | ✅ |
| Multiple focus keywords | ❌ 1 only | ✅ unlimited | ✅ 5 |
| Keyword synonyms | ❌ | ✅ | ❌ |
| Schema — core types | ✅ 6 | ✅ 10 | ✅ 20+ |
| Schema — Video auto-detect | ❌ | ❌ | ✅ |
| WebSite + Organization schema | ❌ | ✅ | ✅ |
| XML sitemap | ✅ | ✅ | ✅ |
| News sitemap | ❌ | ✅ add-on | ✅ |
| Video sitemap | ❌ | ✅ add-on | ✅ |
| GSC integration | ✅ OAuth | ✅ | ✅ |
| IndexNow | ❌ | ❌ | ✅ |
| GA4 analytics | ❌ | ❌ | ✅ |
| Keyword rank tracking | ❌ | ✅ Wincher | ✅ GSC-based |
| Redirects (CRUD) | ✅ | ✅ | ✅ |
| Redirect import/export | ❌ | ✅ CSV+htaccess | ✅ CSV |
| 404 monitor | ✅ | ✅ | ✅ |
| AI generation | ✅ 5 providers | ✅ limited | ✅ limited |
| AI content optimizer | ❌ | ✅ | ✅ |
| Image SEO auto alt | ❌ | ❌ | ✅ |
| Global archive robots | ❌ | ✅ | ✅ |
| Title patterns for archives | ❌ | ✅ | ✅ |
| Webmaster tools verification | ❌ | ✅ 6 services | ✅ 3 services |
| Import from Yoast/RankMath | ❌ | ✅ partial | ✅ full |
| Bulk SEO editing | ❌ | ✅ CSV | ✅ bulk actions |
| SEO score in post list | ⚠️ WooCommerce only | ✅ | ✅ |
| SERP preview (real-time) | ⚠️ partial | ✅ | ✅ |
| Cornerstone content | ❌ | ✅ | ✅ |
| Orphaned content | ❌ | ✅ | ❌ |
| Internal link suggestions | ✅ | ✅ | ✅ |
| Local SEO (multi-location) | ⚠️ schema only | ✅ add-on | ✅ |
| Role manager | ❌ | ✅ | ✅ |
| Multilingual / hreflang | ❌ | ✅ WPML | ✅ WPML |
| Multisite | ❌ | ✅ | ✅ |
| News sitemaps | ❌ | ✅ | ✅ |
| Robots.txt editor UI | ❌ | ✅ | ✅ |
| Frontend admin bar score | ❌ | ❌ | ✅ |
| Gutenberg content blocks | ❌ | ✅ 4 blocks | ❌ |
| Per-check fix explanations | ❌ | ✅ | ✅ |
| **Overall parity** | **~55%** | **100%** | **100%** |

---

## Recommended Build Order

### Sprint 1 — Adoption Blockers (users won't switch without these)
1. **Import from Yoast + RankMath** — map competitor postmeta keys to `_meowseo_*`
2. **Multiple focus keywords** — up to 5, re-run all analyzers per keyword
3. **SEO score column** in all post type list tables
4. **Global archive/taxonomy robots settings** — settings page section
5. **Title/description patterns for archives** — extend `Title_Patterns` for `is_category()`, `is_author()`, etc.

### Sprint 2 — Feature Parity (core SEO expectations)
6. **WebSite + Organization schema** — output globally on every page
7. **Real-time SERP preview** with character counters and mobile/desktop toggle
8. **Webmaster tools verification** — Google meta tag + Bing + Yandex
9. **Robots.txt editor UI** — admin textarea backed by `Robots_Txt` class
10. **Redirect CSV import/export** — basic CSV round-trip
11. **Cornerstone/pillar content** — `_meowseo_is_cornerstone` postmeta + post list column + weighted link suggestions

### Sprint 3 — Schema + Content Coverage
12. **More schema types** — Recipe, Event, VideoObject, Course, JobPosting, Book, Person
13. **Video schema auto-detection** — parse YouTube/Vimeo URLs from content
14. **News sitemap** — `news-sitemap.xml` with `<news:news>` elements
15. **Image SEO** — auto alt-text from attachment title via `wp_get_attachment_image_attributes`
16. **IndexNow** — on `publish_post`, POST to `api.indexnow.org`
17. **Per-analyzer fix explanations** — add "how to fix" text per check

### Sprint 4 — Advanced & Ecosystem
18. **Role manager** — map WordPress roles to MeowSEO capability sets
19. **Multilingual/hreflang** — Polylang + WPML integration
20. **Multisite support** — network activation, per-site settings
21. **Local SEO (multi-location)** — `meowseo_location` CPT with coordinates + shortcodes
22. **Bulk SEO editing** — bulk actions on post list + CSV export
23. **GA4 analytics integration** — combined GA4 + GSC dashboard
24. **Frontend admin bar score** — `admin_bar_menu` hook, score for singular posts
25. **Orphaned content detection** — posts with zero inbound internal links
26. **Gutenberg content blocks** — Estimated Reading Time, Related Posts
27. **AI content optimizer** — per-check suggestions powered by configured AI provider
28. **Keyword synonyms** — extend keyword analysis to match synonym variations
