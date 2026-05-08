# MeowSEO WordPress Plugin

A modular, enterprise-grade WordPress SEO plugin optimized for Google Discover, AI Overviews, and headless WordPress deployments. Built to replace commercial alternatives (Yoast Premium, RankMath Pro) with a lightweight, performance-first implementation.

## Requirements

- **PHP**: 8.0 or higher
- **WordPress**: 6.0 or higher
- **Optional**: WooCommerce (for product SEO features)
- **Optional**: WPGraphQL (for GraphQL API support)

## Core Features

- **Modular Architecture**: Only enabled features are loaded - zero bloat.
- **Enterprise-Grade Setup Wizard**: Modern onboarding experience for both fresh sites and migrations.
- **Yoast & Rank Math Migration**: Seamless, one-click data migration with real-time batch processing.
- **Performance First**: Database-level operations, Object Cache integration, and zero memory bloat.
- **Headless Ready**: Full REST API and WPGraphQL support for decoupled frontends.
- **AI-Powered Modules**: Content Refresh suggestions (🚧 Beta) and AI Bot management (🚧 Beta).
- **Security Hardened**: Nonce verification, capability checks, and encrypted credentials.

## Installation

### For Users (Production)

1. Download the latest release ZIP from [GitHub Releases](https://github.com/your/meowseo/releases)
2. Upload to `/wp-content/plugins/meowseo/` or install via WordPress admin
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The **Setup Wizard** will automatically launch to guide your configuration

### For Developers (Development)

⚠️ **Important**: `/build/` directory is not committed to Git. You must build assets before the plugin will work.

```bash
# 1. Clone repository
git clone https://github.com/your/meowseo.git
cd meowseo

# 2. Install dependencies
composer install
npm install

# 3. Build assets (REQUIRED!)
npm run build

# 4. Run tests to verify
composer test
npm test
```

See [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md) for detailed development instructions.

### Composer Installation

```bash
composer require meowseo/meowseo
```

## Setup Wizard & Onboarding

The MeowSEO Setup Wizard provides a premium onboarding experience, ensuring your site is perfectly configured from day one.

- **Compatibility Check**: Scans your system for PHP/WP versions and conflicting plugins.
- **Data Migration**: Automatically detects Yoast SEO or Rank Math data and offers to migrate titles, descriptions, and redirects via an AJAX-driven batch process.
- **Site Identity (E-E-A-T)**: Configures essential schema for Organization/Person identity, including logo and social profiles for Google Knowledge Graph.
- **SEO Defaults**: One-click setup for XML Sitemaps, indexing preferences, and title separators.
- **Easy vs. Advanced Mode**: Choose between smart defaults or granular control over every module.

## Modules

### Meta Module
Manage per-post SEO metadata with real-time analysis:
- **SEO Title & Description**: Custom templates with dynamic variables (e.g., `{title}`, `{site_name}`).
- **Focus Keyword Analysis**: Real-time content evaluation against your target keyword.
- **Robots Directive**: Granular control over index/noindex and follow/nofollow.
- **Canonical URL**: Set custom canonicals to manage duplicate content.
- **Gutenberg Integration**: Full sidebar panel in the block editor with live SEO scoring.

### Schema Module (E-E-A-T)
Automatic structured data generation for rich results:
- **Supported Types**: Article, WebPage, WebSite, BreadcrumbList, Organization, Person, Product, FAQPage.
- **JSON-LD Output**: Optimized, single-block output that passes Google's Rich Results Test.
- **Identity Profiles**: Detailed management of social profiles and business information.

### Sitemap Module
High-performance XML sitemap generation:
- **File-Based Caching**: Sitemaps are served as static files for maximum performance.
- **Auto-Invalidation**: Automatically regenerates when content is updated.
- **Image Support**: Includes featured images in sitemaps for better image search visibility.

### AI Content Refresh (🚧 Beta)
Revitalize your old content with AI-powered insights:
- **Stale Content Detection**: Identifies posts that haven't been updated and are losing traffic. (🚧 Planned)
- **AI Suggestions**: Provides automated suggestions for improving titles and descriptions. (🚧 Planned)

**Status**: Beta - Concept phase. Basic detection works, AI suggestions in development.

### AI Bot Manager (🚧 Beta)
Control how AI crawlers interact with your site:
- **Bot Blocking**: Block common AI bots (GPTBot, etc.) to protect your content. (🚧 In Progress)
- **Redirection**: Redirect AI crawlers to specific landing pages or legal notices. (🚧 In Progress)

**Status**: Beta - Basic bot detection works, advanced features in development.  
**Workaround**: Use `.htaccess` or server-level bot blocking for production use.

### Redirection & 404 Monitor
- **Redirection Manager**: Easy management of 301, 302, 307, and 410 redirects.
- **404 Monitor**: Track broken links and missing pages in real-time.

### Internal Links & GSC
- **Link Suggestions**: AI-driven internal link suggestions based on content relevance.
- **Google Search Console**: Integrated performance data directly in your WordPress dashboard.

### WooCommerce Module
- **Product SEO**: Automated product schema and enhanced metadata management for WooCommerce stores.
- **Sitemap Integration**: Automatically includes products, categories, and tags in XML sitemaps.

### Instant Indexing (MeowIndex)
- **Google Indexing API**: Submit your pages to Google for instant crawling and indexing.
- **Batch Submission**: Submit multiple URLs at once.
- **Auto-Submission**: Automatically submits posts when they are published or updated.

### Local SEO (Locations)
- **Multi-Location Support**: Manage multiple physical locations with dedicated Schema.org markup.
- **KML File Generation**: Automatic generation of KML files for Google Maps integration.
- **Store Locator**: Shortcodes for displaying location information and maps.

### Advanced Content Tools
- **Bulk Editor**: Quickly edit SEO titles and descriptions for hundreds of posts at once.
- **Image SEO**: Automatically generates ALT text and title attributes for images based on patterns.
- **Orphaned Content**: Identifies posts that have no internal links pointing to them.
- **Cornerstone Content**: Mark and prioritize your most important pillar pages.
- **Admin Bar Stats**: Quick SEO overview and actions directly from the WordPress admin bar.

### Analytics & Reporting
- **Google Analytics 4**: Integrated GA4 reporting directly in your WordPress dashboard.
- **Search Console Sync**: Comprehensive performance data (clicks, impressions) integrated with your content.

### Administration & Scale
- **Role Manager**: Control which user roles have access to specific MeowSEO features and settings.
- **Multilingual Support**: Fully compatible with WPML and Polylang for international SEO.
- **Multisite Ready**: Network-wide settings management and module control for WordPress Multisite.
- **Keyword Synonyms**: Optimize your content for related terms and synonyms to increase topical authority.
- **WP-CLI Support**: Manage sitemaps, redirects, and run health checks via the command line.
- **GitHub Automatic Updates**: Get the latest features and security patches directly from GitHub.

### Extra SEO Tools
- **Breadcrumbs**: Customizable breadcrumb navigation for both users and search engines (Schema.org).
- **SEO Health Check**: Built-in diagnostics to identify configuration issues and performance bottlenecks.

## REST API & Headless

All SEO data is accessible via REST API under the `meowseo/v1` namespace.

- **WPGraphQL**: Full schema extension for WPGraphQL, allowing headless apps to fetch SEO data in a single query.
- **Cache-Control**: API responses include optimized headers for edge caching.

## Performance Optimization

MeowSEO is built to be the fastest SEO plugin on the market:
- **Object Cache**: Native support for Redis and Memcached.
- **Static Sitemaps**: Bypasses the WordPress database for sitemap requests.
- **Zero Frontend Bloat**: No unnecessary CSS or JS is loaded on the frontend.

## Bring Your Own AI (BYOAI)

Unlike other SEO plugins that lock you into expensive monthly subscriptions for AI features, MeowSEO follows a **Bring Your Own AI** philosophy. You have full control over which AI provider you use and only pay for what you consume directly to the provider.

- **Multiple Providers**: Support for OpenAI (GPT-4o), Google Gemini, Anthropic (Claude 3.5), DeepSeek, GLM, and Qwen.
- **OpenAI Compatible**: Connect to any OpenAI-compatible API, including local LLMs (Ollama, LM Studio) or self-hosted models.
- **Image Generation**: Integrated support for DALL-E and Google Imagen.
- **Privacy First**: Your API keys are encrypted at rest and your data never passes through MeowSEO servers—it goes directly from your server to the AI provider.
- **Cost Effective**: No middleman markup. Use your own API keys and benefit from the competitive pricing of modern AI models.

## Support & Contributing

- **Developed by**: [Pusat Teknologi Nusantara](https://www.pustekno.id)
- **Issues**: [GitHub Issues](https://github.com/akbarbahaulloh/meowseo/issues)
- **License**: GPL v2 or later

---
Developed with performance and modern WordPress in mind. Inspired by the best, built for the future.
