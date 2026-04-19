# MeowSEO Analysis Engine - User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
   - [Setting Your Focus Keyword](#setting-your-focus-keyword)
   - [Working with Multiple Keywords](#working-with-multiple-keywords)
   - [Understanding the Interface](#understanding-the-interface)
3. [Migrating from Other SEO Plugins](#migrating-from-other-seo-plugins)
4. [SEO Score Column in Post Lists](#seo-score-column-in-post-lists)
5. [Archive Robots Settings](#archive-robots-settings)
6. [Understanding Your Scores](#understanding-your-scores)
7. [SEO Analysis Features](#seo-analysis-features)
8. [Readability Analysis Features](#readability-analysis-features)
9. [Content Optimization Guide](#content-optimization-guide)
10. [Indonesian Language Support](#indonesian-language-support)
11. [Tips and Best Practices](#tips-and-best-practices)
12. [Troubleshooting](#troubleshooting)

---

## Introduction

Welcome to MeowSEO! This powerful WordPress plugin helps you optimize your content for search engines and readers. The Analysis Engine provides real-time feedback as you write, with comprehensive SEO and readability metrics.

### What You'll Get

- **Real-time Analysis**: See your scores update as you type
- **SEO Optimization**: 12 specialized checks for search engine visibility
- **Readability Metrics**: 6 analyzers to improve content clarity
- **Indonesian Support**: Specialized features for Indonesian language content
- **Actionable Feedback**: Clear recommendations for improvement

---

## Getting Started

### Accessing the Analysis Panel

1. Open any post or page in the WordPress editor
2. Look for the **MeowSEO** panel in the right sidebar
3. You'll see two main scores: **SEO Score** and **Readability Score**

### Setting Your Focus Keyword

Before analysis can begin, set your focus keyword:

1. Find the **Focus Keyword** field in the General tab
2. Enter your main keyword or phrase (e.g., "SEO optimization tips")
3. The analysis will automatically update within 1-2 seconds

### Working with Multiple Keywords

MeowSEO lets you optimize your content for up to 5 focus keywords simultaneously. This powerful feature helps you target multiple search queries per page.

#### Adding Secondary Keywords

1. In the MeowSEO sidebar, find the **Keywords** section
2. Enter your primary keyword in the **Focus Keyword** field
3. Click **Add Secondary Keyword** button
4. Enter your additional keyword (e.g., "content optimization")
5. Click **Add** or press Enter
6. Repeat for up to 4 secondary keywords (5 total maximum)

**Keyword Limit**: You can have 1 primary keyword + 4 secondary keywords = 5 total keywords

#### Removing Secondary Keywords

To remove a secondary keyword:

1. Find the keyword in your keywords list
2. Click the **X** or **Remove** button next to the keyword
3. The keyword will be removed immediately
4. Analysis will update to reflect the remaining keywords

#### Reordering Keywords

You can change the order of your secondary keywords:

1. Hover over a secondary keyword in the list
2. Click and drag the keyword to a new position
3. Release to drop it in the new location
4. The order is saved automatically

**Note**: The primary keyword always stays first and cannot be reordered.

#### Understanding Per-Keyword Analysis

When you have multiple keywords, MeowSEO analyzes your content separately for each keyword:

**What you'll see**:
- Separate analysis results for each keyword
- Individual scores showing how well each keyword is optimized
- Color-coded indicators (green/orange/red) for each keyword
- Detailed feedback for each keyword's performance

**Analysis checks per keyword**:
- ✓ Keyword in Title
- ✓ Keyword in Description
- ✓ Keyword in First Paragraph
- ✓ Keyword Density
- ✓ Keyword in Headings
- ✓ Keyword in URL Slug

**Example display**:
```
Primary Keyword: "SEO optimization" - Score: 85 🟢
  ✓ In Title
  ✓ In Description
  ✓ In First Paragraph
  ✓ Density: 1.2% (optimal)
  ✓ In Headings
  ✓ In URL Slug

Secondary Keyword: "content marketing" - Score: 72 🟢
  ✓ In Title
  ✓ In Description
  ⚠ Not in First Paragraph
  ✓ Density: 0.8% (good)
  ✓ In Headings
  ✗ Not in URL Slug

Secondary Keyword: "SEO tips" - Score: 65 🟠
  ✓ In Title
  ⚠ Not in Description
  ✓ In First Paragraph
  ⚠ Density: 0.4% (low)
  ✗ Not in Headings
  ✗ Not in URL Slug
```

#### Best Practices for Multiple Keywords

**Choose Related Keywords**:
- Select keywords that are semantically related
- Avoid keywords that require completely different content
- Use variations and long-tail versions of your main topic

**Prioritize Your Primary Keyword**:
- Your primary keyword should be the most important
- Optimize title, URL, and first paragraph for primary keyword
- Secondary keywords can appear more naturally throughout

**Don't Force All Keywords Everywhere**:
- It's okay if secondary keywords don't appear in every location
- Focus on natural, readable content
- Aim for 70+ score on your primary keyword first

**Realistic Expectations**:
- Primary keyword: Target 75-85+ score
- Secondary keywords: Target 60-75+ score
- Not all keywords need to be in the URL slug
- Quality content matters more than perfect scores

**Example Strategy**:
```
Primary: "WordPress SEO plugin" (optimize heavily)
Secondary 1: "SEO optimization tool" (natural mentions)
Secondary 2: "content analysis" (supporting topic)
Secondary 3: "meta tags" (related feature)
Secondary 4: "search rankings" (outcome/benefit)
```

#### Validation and Limits

**Maximum Keywords**: 5 total (1 primary + 4 secondary)

**What happens when you reach the limit**:
- The "Add Secondary Keyword" button becomes disabled
- You'll see a message: "Maximum 5 keywords reached"
- Remove a keyword to add a different one

**Keyword Validation**:
- Keywords cannot be empty
- Duplicate keywords are not allowed
- Each keyword must be unique

**Error Messages**:
- "Maximum keyword limit reached (5)" - Remove a keyword first
- "This keyword already exists" - Choose a different keyword
- "Keyword cannot be empty" - Enter a valid keyword phrase

### Understanding the Interface

The MeowSEO panel has several sections:

- **Score Circles**: Visual indicators showing your SEO and Readability scores
- **SEO Analysis**: Expandable section with detailed SEO checks
- **Readability Analysis**: Expandable section with readability metrics
- **Readability Panel**: Separate panel with detailed readability information
- **Keywords Section**: Manage your primary and secondary keywords (up to 5 total)

---

## Migrating from Other SEO Plugins

### Overview

Switching SEO plugins doesn't mean losing your hard work. MeowSEO's Import System lets you migrate all your SEO data from Yoast SEO or RankMath in just a few clicks. Your titles, descriptions, keywords, social media settings, and even redirects will transfer seamlessly.

### Supported Plugins

**Yoast SEO** (Free and Premium)
- Post and page SEO data
- Category and tag metadata
- Plugin settings and title patterns
- Redirects (Premium feature)

**RankMath** (Free and Pro)
- Post and page SEO data
- Category and tag metadata
- Plugin settings and title patterns
- Redirects

### Before You Start

**Backup Your Site**
- Create a full database backup before importing
- This lets you restore if anything unexpected happens

**Keep the Old Plugin Active**
- Don't deactivate Yoast SEO or RankMath yet
- MeowSEO needs to read their data during import
- You can deactivate after verifying the import

**Review Your Content**
- Note any custom configurations you've made
- Check for any special settings you want to preserve

### Starting an Import

#### Step 1: Access the Import Wizard

1. Go to **WordPress Admin** → **MeowSEO** → **Settings**
2. Click the **Import** tab
3. The wizard will automatically detect installed SEO plugins

#### Step 2: Choose Your Plugin

You'll see detected plugins with a green checkmark:

- ✅ **Yoast SEO Detected** - Ready to import
- ✅ **RankMath Detected** - Ready to import

If no plugins are detected, you'll see a message explaining that no compatible plugins were found.

#### Step 3: Review What Will Be Imported

Before starting, you'll see a summary of what will be migrated:

**From Yoast SEO:**
- Post metadata (titles, descriptions, keywords, canonical URLs)
- Robots meta tags (noindex, nofollow)
- Social media metadata (Open Graph, Twitter Cards)
- Category and tag metadata
- Plugin settings (separator, homepage settings)
- Title patterns for different page types
- Redirects (if Yoast Premium is active)

**From RankMath:**
- Post metadata (titles, descriptions, keywords, canonical URLs)
- Robots meta tags (noindex, nofollow)
- Social media metadata (Facebook, Twitter)
- Category and tag metadata
- Plugin settings (separator, homepage settings)
- Title patterns for different page types
- Redirects

#### Step 4: Start the Import

1. Click the **Start Import** button for your plugin
2. Confirm you want to proceed
3. The import will begin processing in batches

### Understanding the Import Process

#### Batch Processing

MeowSEO processes your data in batches of 100 items to prevent timeouts:

**Why batches?**
- Prevents PHP timeout errors on large sites
- Keeps your site responsive during import
- Allows you to cancel if needed

**What you'll see:**
- Real-time progress bar
- Current phase (Posts, Terms, Settings, Redirects)
- Items processed / Total items
- Estimated time remaining

#### Import Phases

**Phase 1: Post Metadata**
- Processes all posts and pages
- Migrates SEO titles, descriptions, keywords
- Transfers robots settings and canonical URLs
- Imports social media metadata

**Phase 2: Term Metadata**
- Processes categories and tags
- Migrates term titles and descriptions
- Preserves taxonomy SEO settings

**Phase 3: Plugin Settings**
- Imports global plugin configuration
- Transfers title patterns
- Migrates separator settings
- Imports homepage metadata

**Phase 4: Redirects** (if applicable)
- Imports all redirect rules
- Preserves redirect types (301, 302, 307, 410)
- Maintains source and target URLs

### Data Mapping Tables

#### Yoast SEO to MeowSEO

**Post Metadata:**

| Yoast SEO Field | MeowSEO Field | Description |
|----------------|---------------|-------------|
| `_yoast_wpseo_title` | `_meowseo_title` | SEO title |
| `_yoast_wpseo_metadesc` | `_meowseo_description` | Meta description |
| `_yoast_wpseo_focuskw` | `_meowseo_focus_keyword` | Focus keyword |
| `_yoast_wpseo_canonical` | `_meowseo_canonical_url` | Canonical URL |
| `_yoast_wpseo_meta-robots-noindex` | `_meowseo_robots_noindex` | Noindex setting |
| `_yoast_wpseo_meta-robots-nofollow` | `_meowseo_robots_nofollow` | Nofollow setting |
| `_yoast_wpseo_opengraph-title` | `_meowseo_og_title` | Open Graph title |
| `_yoast_wpseo_opengraph-description` | `_meowseo_og_description` | Open Graph description |
| `_yoast_wpseo_twitter-title` | `_meowseo_twitter_title` | Twitter title |
| `_yoast_wpseo_twitter-description` | `_meowseo_twitter_description` | Twitter description |

**Term Metadata:**

| Yoast SEO Field | MeowSEO Field | Description |
|----------------|---------------|-------------|
| `_wpseo_title` | `_meowseo_title` | Category/tag title |
| `_wpseo_desc` | `_meowseo_description` | Category/tag description |

**Plugin Settings:**

| Yoast SEO Option | MeowSEO Option | Description |
|-----------------|----------------|-------------|
| `wpseo[separator]` | `separator` | Title separator character |
| `wpseo[title-home-wpseo]` | `homepage_title` | Homepage title |
| `wpseo[metadesc-home-wpseo]` | `homepage_description` | Homepage description |
| `wpseo_titles[title-post]` | `title_pattern_post` | Post title pattern |
| `wpseo_titles[title-page]` | `title_pattern_page` | Page title pattern |
| `wpseo_titles[title-category]` | `title_pattern_category` | Category title pattern |
| `wpseo_titles[title-post_tag]` | `title_pattern_tag` | Tag title pattern |

**Redirects:**
- Imported from `wpseo_redirect` custom post type
- Redirect type preserved (301/302/307/410)

#### RankMath to MeowSEO

**Post Metadata:**

| RankMath Field | MeowSEO Field | Description |
|---------------|---------------|-------------|
| `rank_math_title` | `_meowseo_title` | SEO title |
| `rank_math_description` | `_meowseo_description` | Meta description |
| `rank_math_focus_keyword` | `_meowseo_focus_keyword` | Focus keyword (primary) |
| `rank_math_canonical_url` | `_meowseo_canonical_url` | Canonical URL |
| `rank_math_robots` | `_meowseo_robots_noindex` + `_meowseo_robots_nofollow` | Robots array (split) |
| `rank_math_facebook_title` | `_meowseo_og_title` | Facebook/OG title |
| `rank_math_facebook_description` | `_meowseo_og_description` | Facebook/OG description |
| `rank_math_twitter_title` | `_meowseo_twitter_title` | Twitter title |
| `rank_math_twitter_description` | `_meowseo_twitter_description` | Twitter description |

**Special Handling:**
- **Multiple Keywords**: RankMath's comma-separated keywords are split into primary + secondary keywords
- **Robots Array**: RankMath's robots array is split into separate noindex and nofollow fields

**Term Metadata:**

| RankMath Field | MeowSEO Field | Description |
|---------------|---------------|-------------|
| `rank_math_title` | `_meowseo_title` | Category/tag title |
| `rank_math_description` | `_meowseo_description` | Category/tag description |

**Redirects:**
- Imported from `rank_math_redirections` database table
- Columns mapped: `url_to` → `source_url`, `url_from` → `target_url`, `header_code` → `redirect_type`

### Monitoring Import Progress

#### Progress Indicators

**Progress Bar**
- Shows overall completion percentage
- Updates in real-time as batches complete

**Current Phase**
- Displays which phase is currently running
- Shows phase-specific progress

**Item Counts**
- **Posts**: X / Y processed
- **Terms**: X / Y processed
- **Settings**: Completed / Pending
- **Redirects**: X / Y processed

**Status Messages**
- "Processing posts..." - Importing post metadata
- "Processing terms..." - Importing taxonomy metadata
- "Importing settings..." - Transferring plugin configuration
- "Importing redirects..." - Migrating redirect rules

#### Canceling an Import

If you need to stop the import:

1. Click the **Cancel Import** button
2. The current batch will finish processing
3. Import will stop after the current batch
4. Partial data will remain (already imported items)

**Note**: Canceling doesn't undo imported data. You'll need to restore from backup to revert.

### Import Completion

#### Success Summary

When the import completes, you'll see a detailed summary:

**Import Summary:**
- ✅ **Posts Imported**: 1,247 posts and pages
- ✅ **Terms Imported**: 45 categories and tags
- ✅ **Settings Imported**: Plugin configuration transferred
- ✅ **Redirects Imported**: 23 redirect rules
- ⚠️ **Errors**: 3 items had issues (see error log)

#### Reviewing Imported Data

**Check Your Content:**
1. Open a few posts in the editor
2. Verify SEO titles and descriptions transferred
3. Check that focus keywords are present
4. Review social media metadata

**Check Categories and Tags:**
1. Go to **Posts** → **Categories**
2. Edit a category
3. Verify MeowSEO metadata is present

**Check Settings:**
1. Go to **MeowSEO** → **Settings**
2. Review title patterns
3. Verify separator and homepage settings

**Check Redirects:**
1. Go to **MeowSEO** → **Redirects**
2. Verify redirect rules imported
3. Test a few redirects to ensure they work

### Error Handling and Recovery

#### Understanding Errors

Not all data may import perfectly. Common issues:

**Invalid UTF-8 Characters**
- Some special characters may not transfer
- MeowSEO logs these and continues

**Missing Data**
- Empty fields in the source plugin are skipped
- No error is logged for empty fields

**Data Validation Failures**
- Invalid URLs or malformed data
- Item is skipped, error is logged

#### Viewing the Error Log

If errors occurred during import:

1. Click **View Error Log** in the summary
2. Review the list of errors
3. Each error shows:
   - Post/Term ID
   - Field name
   - Error message

**Example Error Log:**
```
Post ID 123 - _meowseo_title - Invalid UTF-8 sequence
Post ID 456 - _meowseo_canonical_url - Invalid URL format
Term ID 12 - _meowseo_description - Data too long
```

#### Fixing Import Errors

**Option 1: Manual Fix**
1. Note the Post/Term IDs with errors
2. Edit each item manually
3. Re-enter the SEO data

**Option 2: Retry Failed Items**
1. Click **Retry Failed Items** button
2. MeowSEO will attempt to re-import only failed items
3. Review the new error log

**Option 3: Restore and Re-import**
1. Restore your database backup
2. Fix any data issues in the source plugin
3. Run the import again

#### Exporting the Error Log

To save the error log for later review:

1. Click **Export Error Log** button
2. A CSV file will download
3. Open in Excel or Google Sheets
4. Use for tracking manual fixes

### After Import

#### Verify Everything Works

**Test Your Site:**
1. Visit your homepage - check title and description
2. Visit a blog post - verify SEO metadata
3. Visit a category page - check term metadata
4. Test a redirect - ensure it works

**Check Search Console:**
1. Monitor for any indexing issues
2. Watch for crawl errors
3. Verify structured data still validates

#### Deactivate the Old Plugin

Once you've verified everything:

1. Go to **Plugins** → **Installed Plugins**
2. Find Yoast SEO or RankMath
3. Click **Deactivate**
4. Keep it installed for a few days (just in case)
5. After confirming everything works, click **Delete**

**Important**: Don't delete the old plugin immediately. Keep it deactivated for at least a week to ensure everything is working correctly.

#### Clean Up (Optional)

The import doesn't delete original plugin data. To clean up:

**Remove Old Postmeta:**
- Old Yoast/RankMath postmeta remains in your database
- This doesn't hurt anything but takes up space
- Use a plugin like WP-Optimize to clean up orphaned metadata

**Remove Old Options:**
- Old plugin settings remain in wp_options table
- These are harmless but can be removed
- Only do this after confirming import success

### Troubleshooting Import Issues

#### Import Won't Start

**Problem**: "Start Import" button doesn't work

**Solutions:**
1. Verify the source plugin is active
2. Check that you have Administrator role
3. Clear browser cache and try again
4. Check browser console for JavaScript errors

#### Import Stalls or Freezes

**Problem**: Progress bar stops moving

**Solutions:**
1. Wait 2-3 minutes (large batches can take time)
2. Check your server's PHP timeout settings
3. Refresh the page and check if import continued
4. Cancel and restart the import

#### No Plugins Detected

**Problem**: Import wizard says no plugins found

**Solutions:**
1. Verify Yoast SEO or RankMath is installed and active
2. Check that the plugin has data (not a fresh install)
3. Verify plugin version is supported
4. Check for plugin conflicts

#### Data Didn't Transfer

**Problem**: Some or all data is missing after import

**Solutions:**
1. Check the error log for specific failures
2. Verify the data existed in the source plugin
3. Check that fields weren't empty in the source
4. Try the "Retry Failed Items" option

#### Import Completed But Data Is Wrong

**Problem**: Data imported but doesn't look right

**Solutions:**
1. Check the data mapping tables above
2. Verify the source plugin had correct data
3. Check for character encoding issues
4. Review the error log for validation failures

#### Performance Issues During Import

**Problem**: Site is slow during import

**Solutions:**
1. This is normal for large imports
2. Batch processing prevents complete lockup
3. Consider running import during low-traffic hours
4. Increase PHP memory limit if needed

### Best Practices

**Timing Your Import:**
- Run during low-traffic hours
- Allow 5-30 minutes depending on site size
- Don't close the browser tab during import

**Large Sites (1000+ posts):**
- Expect longer import times
- Monitor server resources
- Consider increasing PHP timeout to 300 seconds
- Increase PHP memory limit to 256MB or higher

**Multisite Networks:**
- Import each site individually
- Don't run multiple imports simultaneously
- Test on a staging site first

**After Import:**
- Keep old plugin for 1-2 weeks
- Monitor search rankings
- Check Google Search Console
- Verify all redirects work

### Import System Technical Details

#### Batch Size

Default: 100 items per batch

**Why 100?**
- Balances speed with reliability
- Prevents PHP timeout on most servers
- Keeps memory usage reasonable

**Adjusting Batch Size:**
Developers can filter the batch size:
```php
add_filter( 'meowseo_import_batch_size', function( $size ) {
    return 50; // Smaller batches for slower servers
} );
```

#### Data Validation

All imported data is validated:

**String Fields:**
- Checked for valid UTF-8 encoding
- Sanitized to prevent XSS
- Trimmed of excess whitespace

**URL Fields:**
- Validated as proper URLs
- Checked for valid protocols (http/https)
- Malformed URLs are logged and skipped

**Boolean Fields:**
- Converted to proper boolean values
- Invalid values default to false

**Array Fields:**
- Validated as proper JSON
- Malformed arrays are logged and skipped

#### Data Preservation

**Original Data Is Not Deleted:**
- Source plugin data remains untouched
- MeowSEO creates new postmeta entries
- You can verify import before removing old plugin

**Duplicate Prevention:**
- Running import twice won't create duplicates
- Existing MeowSEO data is overwritten
- Source plugin data is never modified

### FAQ

**Q: Will this affect my search rankings?**
A: No. The import transfers all your SEO data, so search engines see the same metadata. Rankings should remain stable.

**Q: How long does import take?**
A: Depends on site size. Small sites (100 posts): 1-2 minutes. Medium sites (1000 posts): 5-10 minutes. Large sites (10,000+ posts): 20-30 minutes.

**Q: Can I run the import multiple times?**
A: Yes. Running it again will overwrite previously imported data with fresh data from the source plugin.

**Q: What if I have both Yoast and RankMath installed?**
A: Import from one at a time. The second import will overwrite data from the first.

**Q: Will my redirects keep working?**
A: Yes. All redirect rules are imported with their types (301, 302, etc.) preserved.

**Q: Can I undo an import?**
A: Not automatically. Restore from your database backup to undo. This is why backing up first is critical.

**Q: What happens to my old plugin's data?**
A: It stays in your database. MeowSEO creates new entries alongside the old data.

**Q: Do I need to keep the old plugin installed?**
A: Only during import. After verifying everything works, you can deactivate and delete it.

**Q: Will this import my SEO scores?**
A: No. SEO scores are calculated by MeowSEO based on your content. They'll be generated when you edit posts.

**Q: What about custom fields or advanced features?**
A: The import covers standard SEO fields. Custom fields or plugin-specific features may not transfer.

---

## SEO Score Column in Post Lists

### Overview

MeowSEO adds an SEO Score column to your WordPress admin post and page lists, giving you a quick overview of your content's optimization status. This powerful feature helps you identify which content needs attention without opening each post individually.

### Where to Find It

The SEO Score column appears in:

- **Posts list** (Posts → All Posts)
- **Pages list** (Pages → All Pages)
- **Custom post types** (any public custom post type)

The column is automatically added to all public post types in your WordPress installation.

### Understanding the Score Indicators

Each post displays a colored indicator showing its SEO optimization level:

| Score Range | Color | Indicator | Status | Action Needed |
|-------------|-------|-----------|--------|---------------|
| 71-100 | 🟢 Green | Green circle | Excellent | Content is well-optimized |
| 41-70 | 🟠 Orange | Orange circle | Good | Room for improvement |
| 0-40 | 🔴 Red | Red circle | Needs Work | Significant improvements needed |
| No score | ⚪ Gray | Gray dash | Not analyzed | Edit post to generate score |

**Visual Examples:**

```
🟢 85  - Excellent optimization
🟠 62  - Good, could be better
🔴 35  - Needs significant work
—     - Not yet analyzed
```

### Color Coding System

**Green (71-100)**: Your content is well-optimized for search engines. The focus keyword appears in key locations, content structure is good, and technical SEO elements are in place.

**Orange (41-70)**: Your content has decent optimization but has room for improvement. Review the SEO analysis to identify specific areas that need attention.

**Red (0-40)**: Your content needs significant SEO work. Missing focus keyword in critical locations, poor content structure, or missing technical elements.

**Gray Dash (—)**: The post hasn't been analyzed yet. This happens for:
- Posts created before MeowSEO was installed
- Posts that haven't been edited since MeowSEO was activated
- Posts without a focus keyword set

**To generate a score**: Edit the post, set a focus keyword, and save. The score will be calculated automatically.

### Sorting by SEO Score

You can sort your posts by SEO score to quickly identify content that needs optimization:

#### How to Sort

1. Go to **Posts → All Posts** (or any post type list)
2. Click the **SEO Score** column header
3. Posts will sort by score in **descending order** (highest scores first)
4. Click the column header again to sort in **ascending order** (lowest scores first)

#### Sorting Strategies

**Find your best content** (descending sort):
- Click SEO Score column once
- See your highest-scoring posts first
- Use these as templates for other content
- Identify what makes them successful

**Find content that needs work** (ascending sort):
- Click SEO Score column twice
- See your lowest-scoring posts first
- Prioritize these for optimization
- Focus on quick wins (posts close to next tier)

**Bulk optimization workflow**:
1. Sort by ascending order (lowest first)
2. Filter by post type or category if needed
3. Open posts in new tabs
4. Optimize multiple posts in one session
5. Track progress as scores improve

### Score Calculation Methodology

The SEO score is calculated based on multiple factors from the MeowSEO analysis engine:

#### Weighted Analysis Checks

The score is a **weighted average** of 12 SEO analysis checks:

**High Impact Checks** (weighted more heavily):
- ✓ Keyword in Title (most important)
- ✓ Keyword in Description
- ✓ Keyword in First Paragraph
- ✓ Keyword Density (optimal range)

**Medium Impact Checks**:
- ✓ Keyword in Headings
- ✓ Keyword in URL Slug
- ✓ Internal Links (3+ links)
- ✓ Content Length (300-2500 words)

**Supporting Checks**:
- ✓ Image Alt Text
- ✓ Outbound Links
- ✓ Direct Answer field
- ✓ Schema Markup

#### How Scores Are Calculated

1. **Individual Check Scores**: Each analysis check returns a score (0-100)
2. **Weighted Average**: Checks are weighted by importance
3. **Final Score**: Combined into overall SEO score (0-100)
4. **Stored in Database**: Saved as `_meowseo_seo_score` postmeta

**Example Calculation**:
```
Keyword in Title: 100 (weight: 3x)
Keyword in Description: 100 (weight: 3x)
Keyword in First Paragraph: 100 (weight: 2x)
Keyword Density: 85 (weight: 2x)
Keyword in Headings: 70 (weight: 1x)
Keyword in Slug: 100 (weight: 1x)
Internal Links: 80 (weight: 1x)
Content Length: 90 (weight: 1x)
Image Alt Text: 60 (weight: 1x)
Outbound Links: 100 (weight: 1x)
Direct Answer: 0 (weight: 1x)
Schema Markup: 0 (weight: 1x)

Weighted Average = 85 (Green indicator)
```

#### When Scores Update

Scores are recalculated automatically when:
- You edit and save a post
- You change the focus keyword
- You modify content, title, or meta description
- You add or remove images, links, or headings

**Note**: Scores are NOT recalculated automatically for old posts. You must edit and save each post to generate or update its score.

### Using the SEO Score Column Effectively

#### Quick Content Audit

**Identify optimization priorities**:
1. Sort by SEO Score (ascending)
2. Look for red indicators (0-40)
3. Focus on posts with high traffic potential
4. Optimize highest-value content first

**Track optimization progress**:
1. Note current scores before optimization
2. Work through posts systematically
3. Watch scores improve in the list
4. Celebrate as indicators turn green!

#### Content Strategy Insights

**Analyze patterns**:
- Do certain post types score lower?
- Are older posts less optimized?
- Which authors need SEO training?
- What content categories need attention?

**Set team goals**:
- Target: All posts above 70 (green)
- Minimum: No posts below 40 (red)
- Priority: High-traffic posts at 80+

#### Bulk Optimization Workflow

**Efficient optimization process**:

1. **Filter and Sort**
   - Filter by category, author, or date
   - Sort by SEO Score (ascending)
   - Identify batch of 5-10 posts

2. **Open Multiple Posts**
   - Right-click posts, open in new tabs
   - Work through tabs systematically
   - Keep list view open to track progress

3. **Focus on Quick Wins**
   - Posts at 65-70: Easy to push to green
   - Posts at 35-40: Easy to push to orange
   - Missing elements: Add focus keyword, optimize title

4. **Track Progress**
   - Refresh list view after saving posts
   - Watch indicators change color
   - Note score improvements

5. **Iterate**
   - Move to next batch
   - Focus on different post type or category
   - Build optimization momentum

### Troubleshooting

#### Score Not Showing

**Problem**: Post shows gray dash (—) instead of score

**Solutions**:
1. Edit the post in WordPress editor
2. Set a focus keyword in MeowSEO panel
3. Save the post
4. Score will be calculated and displayed

**Note**: Posts created before MeowSEO was installed won't have scores until you edit and save them.

#### Score Seems Wrong

**Problem**: Score doesn't match your expectations

**Solutions**:
1. Open the post in the editor
2. Review the detailed SEO analysis in MeowSEO panel
3. Check which specific checks are failing
4. Remember: Score is based on technical SEO factors, not content quality
5. Focus keyword must be set for accurate scoring

#### Sorting Not Working

**Problem**: Clicking column header doesn't sort

**Solutions**:
1. Refresh the page
2. Check that you're clicking the column header (not a score)
3. Verify you have posts with scores (not all gray dashes)
4. Check for plugin conflicts
5. Try a different browser

#### Scores Not Updating

**Problem**: Scores don't change after optimization

**Solutions**:
1. Ensure you saved the post after editing
2. Refresh the post list page
3. Check that analysis completed (no errors in editor)
4. Verify focus keyword is still set
5. Clear browser cache

### Best Practices

**Regular Content Audits**:
- Review SEO Score column weekly
- Identify trends and patterns
- Set optimization goals
- Track improvements over time

**Prioritize High-Value Content**:
- Focus on posts with high traffic
- Optimize cornerstone content first
- Don't obsess over every post reaching 100
- Balance SEO with content quality

**Use Scores as Guides, Not Rules**:
- Scores indicate technical optimization
- Content quality matters more than perfect scores
- User engagement is the ultimate metric
- A 70 score with great content beats 95 with poor content

**Team Collaboration**:
- Share optimization goals with content team
- Use scores to identify training needs
- Celebrate improvements and milestones
- Make SEO visible and measurable

---

## Archive Robots Settings

### Overview

Archive Robots Settings give you control over how search engines index your archive pages. This powerful feature lets you set default robots meta tags (noindex, nofollow) for different types of archive pages site-wide, helping you manage search engine crawling and prevent duplicate content issues.

### What Are Archive Pages?

Archive pages are automatically generated by WordPress to organize your content:

- **Author Archives**: Lists all posts by a specific author
- **Date Archives**: Lists posts from a specific year, month, or day
- **Category Archives**: Lists posts in a specific category
- **Tag Archives**: Lists posts with a specific tag
- **Search Results**: Lists posts matching a search query
- **Media Attachments**: Individual pages for media files
- **Post Type Archives**: Lists posts of a custom post type

### Why Control Archive Indexing?

**Prevent Duplicate Content**:
- Archive pages often contain the same content as your main posts
- Search engines may see this as duplicate content
- Noindexing archives focuses search engines on your main content

**Manage Crawl Budget**:
- Search engines have limited time to crawl your site
- Blocking low-value archives saves crawl budget for important pages
- Helps search engines find and index your best content faster

**Common Strategy**:
- **Index**: Category and tag archives (help users find content)
- **Noindex**: Author archives, date archives, search results (low value)
- **Noindex**: Attachment pages (focus on actual content)

### Accessing Archive Robots Settings

1. Go to **WordPress Admin** → **MeowSEO** → **Settings**
2. Click the **Advanced** tab
3. Find the **Archive Robots** section

### Supported Archive Types

MeowSEO provides robots settings for these archive types:

| Archive Type | Default Setting | Recommended |
|-------------|----------------|-------------|
| **Author Archives** | noindex, follow | noindex, follow |
| **Date Archives** | noindex, follow | noindex, follow |
| **Category Archives** | index, follow | index, follow |
| **Tag Archives** | index, follow | index, follow |
| **Search Results** | noindex, follow | noindex, follow |
| **Media Attachments** | noindex, follow | noindex, follow |
| **Post Type Archives** | index, follow | Depends on post type |

### Understanding Robots Meta Tags

**noindex**: Tells search engines not to include this page in search results
- Use for: Low-value pages, duplicate content, private content
- Effect: Page won't appear in Google search results

**nofollow**: Tells search engines not to follow links on this page
- Use for: Untrusted content, paid links, user-generated content
- Effect: Links don't pass PageRank/authority

**Common Combinations**:
- **index, follow**: Normal pages (default for most content)
- **noindex, follow**: Archive pages (don't index but follow links to content)
- **noindex, nofollow**: Private or low-quality pages
- **index, nofollow**: Rare (index page but don't follow links)

### Configuring Global Archive Robots

#### Setting Robots for Archive Types

1. In the **Archive Robots** section, you'll see a checkbox grid
2. Each row represents an archive type
3. Each column represents a robots directive (noindex, nofollow)

**Example Configuration**:

```
Archive Type          | noindex | nofollow
---------------------|---------|----------
Author Archives      |    ✓    |    
Date Archives        |    ✓    |    
Category Archives    |         |    
Tag Archives         |         |    
Search Results       |    ✓    |    
Media Attachments    |    ✓    |    
```

**This configuration means**:
- Author archives: `noindex, follow`
- Date archives: `noindex, follow`
- Category archives: `index, follow`
- Tag archives: `index, follow`
- Search results: `noindex, follow`
- Attachments: `noindex, follow`

#### Saving Your Settings

1. Check the boxes for your desired configuration
2. Click **Save Changes** at the bottom
3. You'll see a success message confirming the save
4. Settings take effect immediately on your site

### Term-Specific Override Precedence

**Global vs. Term-Specific Settings**:

MeowSEO allows you to override global archive robots settings for individual categories and tags. This gives you fine-grained control when needed.

**How Precedence Works**:

1. **Term-Specific Setting** (highest priority)
   - Set on individual category/tag edit page
   - Overrides global archive robots setting
   - Use for special categories that need different treatment

2. **Global Archive Setting** (fallback)
   - Set in MeowSEO Settings → Advanced → Archive Robots
   - Applies to all archives of that type
   - Used when no term-specific setting exists

**Example Scenario**:

**Global Setting**: Category archives = `index, follow`

**Term-Specific Override**: "Uncategorized" category = `noindex, follow`

**Result**:
- Most category archives: `index, follow` (use global setting)
- "Uncategorized" category: `noindex, follow` (use term override)

#### Setting Term-Specific Robots

To override robots settings for a specific category or tag:

1. Go to **Posts** → **Categories** (or **Tags**)
2. Click **Edit** on the category/tag
3. Scroll to the **MeowSEO** section
4. Check **noindex** and/or **nofollow** as needed
5. Click **Update**

**When to use term-specific overrides**:
- Default "Uncategorized" category (usually noindex)
- Test or temporary categories
- Private or internal categories
- Low-quality or spam-prone tags

### Robots Meta Tag Output

#### How Robots Tags Appear

When a user or search engine visits an archive page, MeowSEO outputs robots meta tags in the HTML `<head>`:

**Example Output**:

```html
<meta name="robots" content="noindex, follow" />
```

**What this tells search engines**:
- Don't include this page in search results (noindex)
- But do follow the links on this page (follow)

#### Verifying Robots Output

To check if robots tags are working:

**Method 1: View Page Source**
1. Visit an archive page on your site
2. Right-click and select **View Page Source**
3. Search for `<meta name="robots"`
4. Verify the content matches your settings

**Method 2: Browser DevTools**
1. Visit an archive page
2. Press F12 to open DevTools
3. Go to **Elements** tab
4. Find `<head>` section
5. Look for `<meta name="robots">`

**Method 3: SEO Browser Extensions**
- Use extensions like "SEO Meta in 1 Click"
- Visit archive page
- Check robots meta tag in extension popup

### Archive Type Detection

MeowSEO automatically detects which type of archive page is being viewed and applies the appropriate robots setting.

**Detection Logic**:

| WordPress Conditional | Archive Type | Setting Applied |
|----------------------|--------------|-----------------|
| `is_author()` | Author Archive | `robots_author_archive` |
| `is_date()` | Date Archive | `robots_date_archive` |
| `is_category()` | Category Archive | `robots_category_archive` |
| `is_tag()` | Tag Archive | `robots_tag_archive` |
| `is_search()` | Search Results | `robots_search_results` |
| `is_attachment()` | Media Attachment | `robots_attachment` |
| `is_post_type_archive()` | Post Type Archive | `robots_post_type_archive_{type}` |

**Custom Post Type Archives**:

For custom post types (e.g., "Products", "Events"), MeowSEO creates separate settings:
- `robots_post_type_archive_product`
- `robots_post_type_archive_event`

This allows different robots settings per post type archive.

### Best Practices

#### Recommended Settings for Most Sites

**Index These** (valuable for SEO):
- ✅ Category archives (help users find related content)
- ✅ Tag archives (topical content organization)
- ✅ Custom post type archives (if public-facing)

**Noindex These** (prevent duplicate content):
- ❌ Author archives (unless multi-author blog)
- ❌ Date archives (rarely useful for users)
- ❌ Search results (dynamic, low-quality pages)
- ❌ Media attachments (focus on actual content)

#### Multi-Author Blogs

If you run a multi-author blog where author pages are valuable:

**Author Archives**: `index, follow`
- Helps readers find content by favorite authors
- Useful for building author authority
- Make sure author pages have unique descriptions

#### E-commerce Sites

For WooCommerce or other e-commerce sites:

**Product Category Archives**: `index, follow`
- Essential for product discovery
- Help users browse products
- Important for SEO

**Product Tag Archives**: `noindex, follow` or `index, follow`
- Depends on how you use tags
- Index if tags represent important product attributes
- Noindex if tags are too granular or create duplicates

#### Content-Heavy Sites

For sites with thousands of posts:

**Date Archives**: `noindex, follow`
- Rarely useful for users
- Create many low-value pages
- Save crawl budget for important content

**Category Archives**: `index, follow`
- Essential for content organization
- Help users discover related posts
- Improve site structure

### Troubleshooting

#### Robots Tags Not Appearing

**Problem**: No robots meta tag in page source

**Solutions**:
1. Verify settings are saved in MeowSEO → Settings → Advanced
2. Check that you're viewing an archive page (not a single post)
3. Clear site cache (if using caching plugin)
4. Check for plugin conflicts (disable other SEO plugins)
5. Verify MeowSEO is active and up to date

#### Wrong Robots Tag Showing

**Problem**: Robots tag doesn't match your settings

**Solutions**:
1. Check for term-specific override (edit the category/tag)
2. Verify you're on the correct archive type
3. Clear browser cache and site cache
4. Check for other plugins outputting robots tags
5. Review settings in MeowSEO → Settings → Advanced

#### Term Override Not Working

**Problem**: Term-specific setting not overriding global setting

**Solutions**:
1. Verify term-specific setting is saved (edit category/tag)
2. Clear site cache
3. Check that global setting exists as fallback
4. Verify you're viewing the correct term archive
5. Check for plugin conflicts

#### Search Engines Still Indexing Noindexed Pages

**Problem**: Pages with noindex still appear in search results

**Solutions**:
1. **Be patient**: It takes time for search engines to process noindex
2. **Google Search Console**: Request removal of URLs
3. **Check robots.txt**: Ensure you're not blocking Googlebot
4. **Verify tag output**: Confirm noindex tag is in page source
5. **Wait 2-4 weeks**: Google needs time to recrawl and process

**Important**: Noindex is a directive, not a command. Search engines usually respect it, but it's not instant.

### Advanced Configuration

#### Custom Post Type Archives

For developers working with custom post types:

**Automatic Detection**:
- MeowSEO automatically detects custom post type archives
- Creates settings like `robots_post_type_archive_{post_type_slug}`
- Applies appropriate robots tags

**Programmatic Control**:

Developers can filter robots settings:

```php
add_filter( 'meowseo_archive_robots', function( $robots, $archive_type ) {
    if ( $archive_type === 'post_type_archive_product' ) {
        return [ 'noindex' => false, 'nofollow' => false ];
    }
    return $robots;
}, 10, 2 );
```

#### Conditional Robots Based on Content

For advanced use cases, you might want conditional robots:

**Example**: Noindex empty archives

```php
add_filter( 'meowseo_archive_robots', function( $robots, $archive_type ) {
    if ( is_category() && ! have_posts() ) {
        return [ 'noindex' => true, 'nofollow' => false ];
    }
    return $robots;
}, 10, 2 );
```

### FAQ

**Q: Will noindexing archives hurt my SEO?**
A: No. Noindexing low-value archives focuses search engines on your main content, which typically improves SEO.

**Q: Should I noindex all archives?**
A: No. Category and tag archives are usually valuable for users and SEO. Noindex author and date archives instead.

**Q: How long until search engines respect noindex?**
A: Usually 2-4 weeks. Search engines need to recrawl the page to see the noindex tag.

**Q: Can I noindex some categories but not others?**
A: Yes! Use term-specific overrides. Set global setting for most categories, then override specific ones.

**Q: What's the difference between noindex and robots.txt blocking?**
A: Noindex tells search engines "don't show this in results." Robots.txt tells them "don't crawl this at all." Use noindex for archives.

**Q: Will noindex affect my sitemap?**
A: MeowSEO automatically excludes noindexed pages from XML sitemaps (if sitemap feature is enabled).

**Q: Should I noindex attachment pages?**
A: Usually yes. Attachment pages rarely provide value and can create duplicate content issues.

**Q: What about pagination (page 2, 3, etc.)?**
A: Paginated archives inherit the robots setting from page 1. If category archives are indexed, all pages are indexed.

---

## Archive Title and Description Patterns

### Overview

Archive Title and Description Patterns let you define how meta titles and descriptions are generated for your archive pages. Instead of manually setting titles for every category, tag, or author page, you create patterns with variables that automatically populate with the correct information. This ensures consistent, SEO-optimized metadata across all your archive pages.

### What Are Title Patterns?

Title patterns are templates that use variables (placeholders) to generate dynamic titles and descriptions. When someone visits an archive page, MeowSEO replaces the variables with actual values.

**Example Pattern**:
```
%%category%% Archives %%sep%% %%sitename%%
```

**Becomes**:
```
WordPress Tutorials Archives | My Blog
```

### Why Use Title Patterns?

**Consistency**: All archive pages follow the same format
- Maintains brand consistency
- Professional appearance in search results
- Easier to manage than individual titles

**Efficiency**: Set once, applies to all archives
- No need to manually edit every category/tag
- Automatically works for new categories
- Saves hours of manual work

**SEO Benefits**: Optimized for search engines
- Include site name for brand recognition
- Add relevant keywords automatically
- Proper formatting for search results

**Dynamic Updates**: Changes reflect immediately
- Update pattern once, affects all archives
- No need to edit individual pages
- Instant site-wide changes

### Accessing Archive Pattern Settings

1. Go to **WordPress Admin** → **MeowSEO** → **Settings**
2. Click the **General** tab
3. Find the **Archive Patterns** section

### Supported Archive Types

MeowSEO provides title and description patterns for these archive types:

| Archive Type | Example URL | Use Case |
|-------------|-------------|----------|
| **Category Archives** | `/category/tutorials/` | Lists posts in a category |
| **Tag Archives** | `/tag/wordpress/` | Lists posts with a tag |
| **Custom Taxonomy Archives** | `/genre/fiction/` | Lists posts in custom taxonomy |
| **Author Pages** | `/author/john-smith/` | Lists posts by an author |
| **Search Results** | `/?s=seo+tips` | Lists search results |
| **Date Archives** | `/2024/01/` | Lists posts from a date |
| **404 Pages** | `/non-existent-page/` | Page not found |
| **Homepage** | `/` | Site homepage |

### Available Variables

Variables are placeholders that get replaced with actual content. All variables use the `%%variable%%` format.

#### Content Variables

| Variable | Description | Example Output | Works On |
|----------|-------------|----------------|----------|
| `%%category%%` | Category name | "WordPress Tutorials" | Category archives |
| `%%tag%%` | Tag name | "SEO Tips" | Tag archives |
| `%%term%%` | Generic term name | "Fiction" or "Tutorials" | Any taxonomy archive |
| `%%name%%` | Author display name | "John Smith" | Author pages |
| `%%searchphrase%%` | Search query | "wordpress seo" | Search results |
| `%%date%%` | Archive date | "January 2024" | Date archives |
| `%%posttype%%` | Post type label | "Products" or "Events" | Post type archives |

#### Site Variables

| Variable | Description | Example Output | Works On |
|----------|-------------|----------------|----------|
| `%%sitename%%` | Site name | "My Blog" | All pages |
| `%%title%%` | Archive title | "Category: Tutorials" | All archives |
| `%%sep%%` | Separator character | "\|" or "-" | All pages |
| `%%page%%` | Page number | "Page 2" | Paginated archives |

### Default Patterns

MeowSEO comes with sensible default patterns:

**Category Archives**:
- Title: `%%category%% Archives %%sep%% %%sitename%%`
- Description: `Browse all posts in the %%category%% category on %%sitename%%`

**Tag Archives**:
- Title: `%%tag%% Tag %%sep%% %%sitename%%`
- Description: `Posts tagged with %%tag%% on %%sitename%%`

**Author Pages**:
- Title: `%%name%% %%sep%% %%sitename%%`
- Description: `All posts by %%name%% on %%sitename%%`

**Search Results**:
- Title: `Search Results for %%searchphrase%% %%sep%% %%sitename%%`
- Description: `Search results for "%%searchphrase%%" on %%sitename%%`

**Date Archives**:
- Title: `%%date%% Archives %%sep%% %%sitename%%`
- Description: `Posts from %%date%% on %%sitename%%`

**404 Pages**:
- Title: `Page Not Found %%sep%% %%sitename%%`
- Description: `The page you're looking for could not be found on %%sitename%%`

**Homepage**:
- Title: `%%sitename%% %%sep%% %%tagline%%`
- Description: `Welcome to %%sitename%% - %%tagline%%`

### Configuring Archive Patterns

#### Setting Title Patterns

1. In the **Archive Patterns** section, find the archive type you want to configure
2. Enter your pattern in the **Title Pattern** field
3. Use variables from the reference list
4. Check the live preview to see example output

**Example Configuration**:

**Category Title Pattern**:
```
%%category%% - Expert Guides %%sep%% %%sitename%%
```

**Result**:
```
WordPress Tutorials - Expert Guides | My Blog
```

#### Setting Description Patterns

1. Find the **Description Pattern** field for the archive type
2. Enter your pattern using variables
3. Keep descriptions between 150-160 characters for optimal display
4. Check the live preview

**Example Configuration**:

**Category Description Pattern**:
```
Explore our comprehensive %%category%% guides and tutorials. Learn from experts at %%sitename%%.
```

**Result**:
```
Explore our comprehensive WordPress Tutorials guides and tutorials. Learn from experts at My Blog.
```

### Pattern Syntax and Validation Rules

#### Valid Pattern Syntax

**Correct Variable Format**:
- ✅ `%%category%%` - Correct (double percent signs)
- ❌ `%category%` - Wrong (single percent signs)
- ❌ `{{category}}` - Wrong (wrong delimiters)

**Multiple Variables**:
- ✅ `%%category%% %%sep%% %%sitename%%` - Correct
- ✅ `Posts about %%tag%% on %%sitename%%` - Correct

**Text and Variables Mixed**:
- ✅ `Browse %%category%% Archives` - Correct
- ✅ `%%name%%'s Articles` - Correct

#### Validation Rules

**Pattern Length**:
- Title patterns: Recommended under 60 characters (after variable substitution)
- Description patterns: Recommended 150-160 characters (after variable substitution)

**Variable Matching**:
- Variables must have matching opening and closing `%%`
- ❌ `%%category% %%sitename%%` - Unmatched delimiters
- ✅ `%%category%% %%sitename%%` - Correct

**Appropriate Variables**:
- Use variables that make sense for the archive type
- ❌ `%%category%%` on author pages - Won't work
- ✅ `%%name%%` on author pages - Correct

**Empty Patterns**:
- Empty patterns will use default patterns
- You can clear a pattern to restore defaults

#### Validation Errors

When you save patterns, MeowSEO validates them:

**Error: "Unmatched %% delimiters"**
- Problem: Opening `%%` without closing `%%`
- Solution: Ensure all variables have both `%%` before and after

**Error: "Invalid variable name"**
- Problem: Variable name doesn't exist
- Solution: Check spelling, use variables from the reference list

**Error: "Pattern too long"**
- Problem: Pattern exceeds recommended length
- Solution: Shorten text or remove unnecessary variables

### Live Preview Functionality

The Archive Patterns settings include a live preview feature that shows you how your patterns will look with sample data.

#### How Live Preview Works

**Real-Time Updates**:
- Preview updates as you type
- Shows example output with sample data
- Helps you visualize the final result

**Sample Data Used**:
- Category: "WordPress Tutorials"
- Tag: "SEO Tips"
- Author: "John Smith"
- Search phrase: "wordpress seo"
- Date: "January 2024"
- Site name: Your actual site name
- Separator: Your configured separator

**Example Preview**:

**Pattern**:
```
%%category%% Archives %%sep%% %%sitename%%
```

**Preview Shows**:
```
Title: WordPress Tutorials Archives | My Blog
Length: 42 characters ✅ Good length
```

#### Using the Preview

1. **Type your pattern** in the input field
2. **Watch the preview** update in real-time
3. **Check the length** indicator (green = good, orange = long, red = too long)
4. **Verify variables** are replaced correctly
5. **Adjust as needed** before saving

**Preview Indicators**:
- 🟢 **Green**: Optimal length (under 60 chars for titles, 150-160 for descriptions)
- 🟠 **Orange**: Acceptable but long (60-70 chars for titles, 160-180 for descriptions)
- 🔴 **Red**: Too long (over 70 chars for titles, over 180 for descriptions)

### Pattern Examples and Best Practices

#### Category Archive Patterns

**Simple and Clean**:
```
Title: %%category%% %%sep%% %%sitename%%
Description: Browse all %%category%% posts on %%sitename%%
```

**SEO-Focused**:
```
Title: %%category%% - Complete Guide %%sep%% %%sitename%%
Description: Explore our comprehensive %%category%% guides, tutorials, and tips on %%sitename%%
```

**Brand-Focused**:
```
Title: %%sitename%% %%category%% Archives
Description: Discover expert %%category%% content from the team at %%sitename%%
```

#### Tag Archive Patterns

**Keyword-Rich**:
```
Title: %%tag%% Articles %%sep%% %%sitename%%
Description: Read all articles tagged with %%tag%% on %%sitename%% - expert insights and tips
```

**User-Friendly**:
```
Title: Posts about %%tag%% %%sep%% %%sitename%%
Description: Everything you need to know about %%tag%% - curated content from %%sitename%%
```

#### Author Page Patterns

**Professional**:
```
Title: %%name%%, Author at %%sitename%%
Description: Read articles by %%name%%, contributing author at %%sitename%%
```

**Personal**:
```
Title: %%name%%'s Articles %%sep%% %%sitename%%
Description: Explore all posts written by %%name%% on %%sitename%%
```

#### Search Results Patterns

**Clear and Direct**:
```
Title: Search: %%searchphrase%% %%sep%% %%sitename%%
Description: Search results for "%%searchphrase%%" on %%sitename%%
```

**Helpful**:
```
Title: Results for %%searchphrase%% %%sep%% %%sitename%%
Description: Found results for "%%searchphrase%%" - browse our content on %%sitename%%
```

#### Date Archive Patterns

**Simple**:
```
Title: %%date%% %%sep%% %%sitename%%
Description: Posts published in %%date%% on %%sitename%%
```

**Descriptive**:
```
Title: %%date%% Archive %%sep%% %%sitename%%
Description: Browse all content from %%date%% on %%sitename%%
```

#### Pagination Handling

For paginated archives (page 2, 3, etc.), use the `%%page%%` variable:

**Pattern**:
```
%%category%% Archives %%page%% %%sep%% %%sitename%%
```

**Page 1 Output**:
```
WordPress Tutorials Archives | My Blog
```

**Page 2 Output**:
```
WordPress Tutorials Archives Page 2 | My Blog
```

**Best Practice**: Place `%%page%%` before the separator for clean formatting.

### Saving Your Patterns

#### How to Save

1. Configure your patterns in the Archive Patterns section
2. Review the live preview for each pattern
3. Click **Save Changes** at the bottom of the page
4. You'll see a success message confirming the save
5. Patterns take effect immediately on your site

#### Validation on Save

When you click Save Changes, MeowSEO:

1. **Validates syntax** - Checks for unmatched delimiters
2. **Sanitizes input** - Removes potentially harmful code
3. **Stores patterns** - Saves to database
4. **Shows feedback** - Success or error messages

**Success Message**:
```
✅ Settings saved successfully. Archive patterns updated.
```

**Error Message**:
```
❌ Error: Unmatched %% delimiters in Category Title pattern. Please fix and try again.
```

### Verifying Pattern Output

After saving patterns, verify they're working correctly:

#### Method 1: Visit Archive Pages

1. Visit a category archive on your site
2. View the page source (right-click → View Page Source)
3. Find the `<title>` tag in the `<head>` section
4. Verify it matches your pattern with variables replaced

**Example**:
```html
<title>WordPress Tutorials Archives | My Blog</title>
<meta name="description" content="Browse all WordPress Tutorials posts on My Blog" />
```

#### Method 2: Use SEO Browser Extensions

1. Install an SEO extension (e.g., "SEO Meta in 1 Click")
2. Visit an archive page
3. Click the extension icon
4. Check the title and description in the popup

#### Method 3: Check Search Results

1. Search for your site in Google: `site:yoursite.com category-name`
2. Look at how your category pages appear
3. Verify titles and descriptions match your patterns

### Troubleshooting

#### Patterns Not Applying

**Problem**: Archive pages still show old titles

**Solutions**:
1. Clear site cache (if using caching plugin)
2. Clear browser cache
3. Verify patterns are saved (check settings page)
4. Check for other SEO plugins overriding titles
5. Verify you're viewing an archive page (not a single post)

#### Variables Not Replacing

**Problem**: Variables show as `%%category%%` instead of actual values

**Solutions**:
1. Check variable spelling (must be exact)
2. Verify you're using correct variable for archive type
3. Clear site cache
4. Check for plugin conflicts
5. Verify MeowSEO is active and up to date

#### Titles Too Long in Search Results

**Problem**: Titles are truncated in Google search results

**Solutions**:
1. Shorten your pattern
2. Remove unnecessary words
3. Place important keywords first
4. Aim for under 60 characters total
5. Use the live preview to check length

#### Descriptions Not Showing

**Problem**: Meta descriptions don't appear in page source

**Solutions**:
1. Verify description patterns are saved
2. Check that descriptions aren't empty
3. Clear site cache
4. Check for other plugins outputting descriptions
5. Verify MeowSEO is handling meta tags

#### Wrong Variables Showing

**Problem**: Category name appears on tag archives

**Solutions**:
1. Use `%%term%%` for generic term name (works on all taxonomies)
2. Or use specific variables (`%%category%%`, `%%tag%%`) for specific types
3. Verify you're using the correct pattern for the archive type
4. Check archive type detection (see Advanced section)

### Advanced Configuration

#### Custom Post Type Archives

For custom post types (e.g., Products, Events), MeowSEO automatically creates pattern settings:

**Automatic Pattern Creation**:
- Detects registered custom post types
- Creates title and description pattern fields
- Uses post type label in default patterns

**Example for "Products" post type**:
```
Title: %%posttype%% Archive %%sep%% %%sitename%%
Result: Products Archive | My Shop
```

#### Programmatic Pattern Filtering

Developers can filter patterns programmatically:

**Filter Title Patterns**:
```php
add_filter( 'meowseo_archive_title_pattern', function( $pattern, $archive_type ) {
    if ( $archive_type === 'category' ) {
        return '%%category%% - Custom Title %%sep%% %%sitename%%';
    }
    return $pattern;
}, 10, 2 );
```

**Filter Description Patterns**:
```php
add_filter( 'meowseo_archive_description_pattern', function( $pattern, $archive_type ) {
    if ( $archive_type === 'tag' ) {
        return 'Custom description for %%tag%% on %%sitename%%';
    }
    return $pattern;
}, 10, 2 );
```

**Filter Variable Values**:
```php
add_filter( 'meowseo_pattern_variable_value', function( $value, $variable, $context ) {
    if ( $variable === 'sitename' ) {
        return 'Custom Site Name';
    }
    return $value;
}, 10, 3 );
```

#### Conditional Patterns

For advanced use cases, you can apply different patterns based on conditions:

**Example: Different patterns for specific categories**:
```php
add_filter( 'meowseo_archive_title_pattern', function( $pattern, $archive_type ) {
    if ( $archive_type === 'category' && is_category( 'uncategorized' ) ) {
        return 'Miscellaneous Posts %%sep%% %%sitename%%';
    }
    return $pattern;
}, 10, 2 );
```

### Best Practices

#### Title Pattern Best Practices

**Keep It Concise**:
- Target 50-60 characters
- Front-load important keywords
- Include site name for branding

**Use Consistent Format**:
- Same separator across all patterns
- Consistent structure (e.g., always end with site name)
- Professional and clean appearance

**Include Branding**:
- Always include `%%sitename%%` or `%%title%%`
- Helps with brand recognition
- Improves click-through rates

**Prioritize Keywords**:
- Place important terms first
- Use `%%category%%` or `%%tag%%` early in pattern
- Don't stuff with unnecessary words

#### Description Pattern Best Practices

**Optimal Length**:
- Target 150-160 characters
- Longer descriptions get truncated
- Shorter descriptions may not be compelling

**Include Call-to-Action**:
- "Explore", "Discover", "Browse", "Learn"
- Encourages clicks from search results
- Makes descriptions more engaging

**Use Natural Language**:
- Write for humans, not just search engines
- Make descriptions compelling and useful
- Avoid keyword stuffing

**Include Variables**:
- Use `%%category%%`, `%%tag%%`, etc. for relevance
- Include `%%sitename%%` for branding
- Make descriptions dynamic and specific

#### SEO Optimization Tips

**For Category Archives**:
- Include category name early in title
- Mention "guides", "tutorials", or "articles" in description
- Use `%%sep%%` for clean formatting

**For Tag Archives**:
- Use "tagged with" or "about" in descriptions
- Keep titles concise (tags are often long)
- Consider using "Posts about %%tag%%"

**For Author Pages**:
- Include author name prominently
- Mention "author" or "writer" for clarity
- Consider "Articles by %%name%%"

**For Search Results**:
- Keep simple and clear
- Include search phrase in quotes
- Don't over-optimize (low SEO value)

**For Date Archives**:
- Usually noindexed (see Archive Robots Settings)
- Keep patterns simple
- Focus on clarity over optimization

### Pattern Strategy Examples

#### Blog/Content Site

**Focus**: Content discovery and organization

**Category Pattern**:
```
Title: %%category%% Articles %%sep%% %%sitename%%
Description: Read our latest %%category%% articles, guides, and insights on %%sitename%%
```

**Tag Pattern**:
```
Title: %%tag%% Posts %%sep%% %%sitename%%
Description: All posts about %%tag%% on %%sitename%% - expert tips and advice
```

**Author Pattern**:
```
Title: %%name%%, Author at %%sitename%%
Description: Read articles by %%name%% - expert insights and analysis on %%sitename%%
```

#### E-commerce Site

**Focus**: Product discovery and conversion

**Category Pattern**:
```
Title: %%category%% - Shop Now %%sep%% %%sitename%%
Description: Browse our %%category%% collection at %%sitename%% - quality products, great prices
```

**Tag Pattern**:
```
Title: %%tag%% Products %%sep%% %%sitename%%
Description: Shop %%tag%% products at %%sitename%% - find exactly what you need
```

#### Multi-Author Blog

**Focus**: Author authority and content variety

**Author Pattern**:
```
Title: %%name%%'s Articles %%sep%% %%sitename%%
Description: Explore all articles by %%name%%, contributing writer at %%sitename%%
```

**Category Pattern**:
```
Title: %%category%% by Our Experts %%sep%% %%sitename%%
Description: Expert %%category%% content from our team of writers at %%sitename%%
```

#### News/Magazine Site

**Focus**: Timeliness and topic coverage

**Category Pattern**:
```
Title: %%category%% News %%sep%% %%sitename%%
Description: Latest %%category%% news and updates from %%sitename%%
```

**Date Pattern**:
```
Title: %%date%% News Archive %%sep%% %%sitename%%
Description: News articles from %%date%% - stay informed with %%sitename%%
```

### FAQ

**Q: Can I use HTML in patterns?**
A: No. Patterns are plain text only. HTML will be stripped during sanitization.

**Q: What happens if I use a variable that doesn't apply?**
A: The variable will be removed. For example, `%%category%%` on a tag archive will be blank.

**Q: Can I create custom variables?**
A: Not through the UI, but developers can add custom variables using filters (see Advanced section).

**Q: Do patterns affect SEO?**
A: Yes! Well-crafted patterns improve click-through rates and help search engines understand your content structure.

**Q: Can I have different patterns for different categories?**
A: Not through the UI. All categories use the same pattern. For custom patterns, use programmatic filtering (see Advanced section).

**Q: What's the difference between %%title%% and %%category%%?**
A: `%%title%%` is the generic archive title (e.g., "Category: Tutorials"), while `%%category%%` is just the category name (e.g., "Tutorials").

**Q: Should I include keywords in patterns?**
A: Yes, but naturally. Include relevant terms like "articles", "guides", "shop", etc. that make sense for your content.

**Q: How do I restore default patterns?**
A: Clear the pattern field and save. MeowSEO will use the default pattern.

**Q: Do patterns work with Yoast/RankMath imported data?**
A: Yes! If you imported patterns from Yoast or RankMath, they're converted to MeowSEO format automatically.

**Q: Can I test patterns before saving?**
A: Yes! Use the live preview feature to see how patterns will look with sample data.

---

## Understanding Your Scores

### Score Ranges

Both SEO and Readability scores range from 0 to 100:

| Score | Color | Status | Meaning |
|-------|-------|--------|---------|
| 70-100 | 🟢 Green | Excellent | Your content is well-optimized |
| 40-69 | 🟠 Orange | Good | Room for improvement |
| 0-39 | 🔴 Red | Needs Work | Significant improvements needed |

### How Scores Are Calculated

**SEO Score**: Weighted average of 12 SEO checks
- Keyword placement (title, description, headings, etc.)
- Content structure (links, images, schema)
- Technical optimization (slug, meta tags)

**Readability Score**: Weighted average of 5 readability checks
- Sentence length
- Paragraph length
- Passive voice usage
- Transition words
- Subheading distribution

### What Makes a Good Score?

**Target Scores:**
- SEO Score: Aim for 70+ (Excellent)
- Readability Score: Aim for 70+ (Excellent)

**Don't Obsess Over Perfect Scores:**
- Focus on the most impactful improvements
- Balance SEO with natural, engaging writing
- Some checks may not apply to all content types

---

## SEO Analysis Features

### 1. Keyword in Title ⭐ High Impact

**What it checks**: Whether your focus keyword appears in the SEO title

**Why it matters**: The title is the most important on-page SEO element

**How to optimize**:
- Include your focus keyword naturally in the title
- Place it near the beginning if possible
- Keep titles under 60 characters

**Example**:
- ❌ "Tips for Better Content"
- ✅ "SEO Optimization Tips for Better Content"

### 2. Keyword in Description ⭐ High Impact

**What it checks**: Whether your focus keyword appears in the meta description

**Why it matters**: Improves click-through rates from search results

**How to optimize**:
- Include keyword naturally in the description
- Write compelling, actionable descriptions
- Keep descriptions 150-160 characters

**Example**:
- ❌ "Learn how to improve your website's visibility"
- ✅ "Master SEO optimization with these proven tips to boost your rankings"

### 3. Keyword in First Paragraph ⭐ High Impact

**What it checks**: Whether your focus keyword appears in the first 100 words

**Why it matters**: Signals content relevance to search engines

**How to optimize**:
- Introduce your topic and keyword early
- Make the first paragraph engaging
- Don't force keyword placement unnaturally

### 4. Keyword Density ⭐ High Impact

**What it checks**: How often your keyword appears (as % of total words)

**Why it matters**: Balance between optimization and over-optimization

**Optimal range**: 0.5% - 2.5%

**How to optimize**:
- Too low (<0.5%): Use keyword more naturally throughout content
- Optimal (0.5-2.5%): Perfect balance
- Too high (>3.5%): Reduce keyword usage to avoid keyword stuffing

**Example for 1000-word article**:
- Too low: 2-3 mentions (0.2-0.3%)
- Optimal: 5-25 mentions (0.5-2.5%)
- Too high: 35+ mentions (3.5%+)

### 5. Keyword in Headings

**What it checks**: Whether your keyword appears in H2 or H3 headings

**Why it matters**: Reinforces content structure and topic relevance

**How to optimize**:
- Include keyword in at least one subheading
- Use variations and related terms in other headings
- Keep headings descriptive and useful

### 6. Keyword in URL Slug

**What it checks**: Whether your keyword appears in the post URL

**Why it matters**: URLs are a ranking factor and improve click-through rates

**How to optimize**:
- Edit the permalink to include your keyword
- Use hyphens to separate words
- Keep URLs short and descriptive

**Example**:
- ❌ `yoursite.com/post-12345`
- ✅ `yoursite.com/seo-optimization-tips`

### 7. Image Alt Text Analysis

**What it checks**: Whether images have alt text and include keywords

**Why it matters**: Improves accessibility and image search rankings

**How to optimize**:
- Add descriptive alt text to all images
- Include keyword naturally in some alt texts
- Describe what the image shows

**Example**:
- ❌ `<img src="image.jpg" alt="">`
- ✅ `<img src="image.jpg" alt="SEO optimization checklist on laptop screen">`

### 8. Internal Links Analysis

**What it checks**: Number and quality of internal links

**Why it matters**: Improves site structure and distributes page authority

**How to optimize**:
- Add 3+ internal links to related content
- Use descriptive anchor text (not "click here")
- Link to relevant, helpful pages

**Example**:
- ❌ "Read more [here](link)"
- ✅ "Learn more about [keyword research strategies](link)"

### 9. Outbound Links Analysis

**What it checks**: Presence of external links to authoritative sources

**Why it matters**: Shows content credibility and thoroughness

**How to optimize**:
- Link to 1-3 authoritative external sources
- Choose reputable, relevant websites
- Consider using nofollow for commercial links

### 10. Content Length

**What it checks**: Total word count of your content

**Why it matters**: Longer content tends to rank better (when quality is maintained)

**Optimal range**: 300-2500 words

**Guidelines**:
- Blog posts: 800-1500 words
- Pillar content: 2000-3000 words
- Product pages: 300-800 words
- News articles: 400-800 words

### 11. Direct Answer Presence

**What it checks**: Whether you've added a Direct Answer field

**Why it matters**: Optimizes for Google AI Overviews and featured snippets

**How to optimize**:
- Write a concise answer (300-450 characters)
- Answer the main question directly
- Use clear, simple language

**Example**:
"SEO optimization is the process of improving your website's visibility in search engine results. It involves optimizing content, technical elements, and building authority through links. Good SEO helps your target audience find your content when searching for related topics."

### 12. Schema Markup

**What it checks**: Whether structured data (schema) is configured

**Why it matters**: Enables rich results in search (stars, prices, FAQs, etc.)

**How to optimize**:
- Choose appropriate schema type (Article, Product, FAQ, etc.)
- Fill in all required fields
- Test with Google's Rich Results Test

---

## Readability Analysis Features

### 1. Sentence Length ⭐ High Impact

**What it checks**: Average words per sentence

**Why it matters**: Shorter sentences are easier to read and understand

**Optimal**: Less than 20 words per sentence

**How to optimize**:
- Break long sentences into shorter ones
- Use periods instead of commas when appropriate
- Aim for variety in sentence length

**Example**:
- ❌ "SEO optimization is important because it helps your website rank better in search engines which means more people will find your content and visit your site leading to more traffic and potential customers."
- ✅ "SEO optimization is important. It helps your website rank better in search engines. This means more people will find your content. More visitors can lead to more customers."

### 2. Paragraph Length ⭐ High Impact

**What it checks**: Average words per paragraph

**Why it matters**: Shorter paragraphs improve visual readability

**Optimal**: Less than 150 words per paragraph

**How to optimize**:
- Break long paragraphs into smaller chunks
- One main idea per paragraph
- Use white space effectively

**Mobile tip**: Paragraphs look longer on mobile screens, so keep them even shorter for mobile readers.

### 3. Passive Voice

**What it checks**: Percentage of sentences using passive voice

**Why it matters**: Active voice is more direct and engaging

**Optimal**: Less than 10% passive voice

**How to optimize**:
- Use active voice: "We recommend..." instead of "It is recommended..."
- Make the subject perform the action
- Be direct and clear

**Examples**:
- ❌ Passive: "The article was written by the author"
- ✅ Active: "The author wrote the article"
- ❌ Passive: "SEO is considered important by marketers"
- ✅ Active: "Marketers consider SEO important"

**Indonesian passive voice**: The analyzer detects Indonesian patterns (di-, ter-, ke-an)

### 4. Transition Words ⭐ High Impact

**What it checks**: Percentage of sentences with transition words

**Why it matters**: Transitions improve content flow and readability

**Optimal**: More than 30% of sentences

**How to optimize**:
- Use transitions to connect ideas
- Show relationships between sentences
- Guide readers through your content

**Common transition words**:
- **Addition**: also, furthermore, moreover, additionally
- **Contrast**: however, but, nevertheless, on the other hand
- **Cause/Effect**: therefore, consequently, as a result, thus
- **Example**: for example, for instance, such as, like
- **Sequence**: first, second, next, finally, then

**Indonesian transitions**: namun, tetapi, oleh karena itu, selain itu, misalnya, kemudian

### 5. Subheading Distribution

**What it checks**: Average words between H2/H3 headings

**Why it matters**: Regular headings improve scannability

**Optimal**: Heading every 300 words or less

**How to optimize**:
- Add H2 headings for main sections
- Use H3 headings for subsections
- Make headings descriptive and useful

**Example structure**:
```
# Main Title (H1)
Introduction (100 words)

## First Main Section (H2)
Content (250 words)

### Subsection (H3)
Content (200 words)

## Second Main Section (H2)
Content (300 words)
```

### 6. Flesch Reading Ease Score

**What it checks**: Overall readability based on sentence and word complexity

**Why it matters**: Indicates how easy your content is to read

**Score interpretation**:
- **90-100**: Very easy (5th grade level)
- **80-89**: Easy (6th grade level)
- **70-79**: Fairly easy (7th grade level)
- **60-69**: Standard (8th-9th grade level) ⭐ Target
- **50-59**: Fairly difficult (10th-12th grade level)
- **30-49**: Difficult (College level)
- **0-29**: Very difficult (College graduate level)

**How to optimize**:
- Use shorter sentences
- Choose simpler words when possible
- Break up complex ideas
- Target 60+ for general audiences

**Note**: This is informational only and doesn't affect your readability score.

---

## Content Optimization Guide

### Step-by-Step Optimization Process

#### Step 1: Start with Keyword Research
1. Choose a focus keyword with search volume
2. Enter it in the Focus Keyword field
3. Wait for initial analysis

#### Step 2: Optimize Your Title
1. Include focus keyword naturally
2. Make it compelling and click-worthy
3. Keep it under 60 characters
4. Check: ✅ Keyword in Title

#### Step 3: Write a Great Meta Description
1. Include focus keyword
2. Write compelling copy (150-160 characters)
3. Include a call-to-action
4. Check: ✅ Keyword in Description

#### Step 4: Craft Your Introduction
1. Include keyword in first 100 words
2. Hook readers with interesting opening
3. Preview what's coming
4. Check: ✅ Keyword in First Paragraph

#### Step 5: Structure Your Content
1. Add H2 headings every 300 words
2. Include keyword in at least one heading
3. Use H3 for subsections
4. Check: ✅ Keyword in Headings, ✅ Subheading Distribution

#### Step 6: Optimize Content Body
1. Aim for 800-1500 words (blog posts)
2. Use keyword naturally (0.5-2.5% density)
3. Add 3+ internal links with descriptive anchors
4. Add 1-2 external links to authoritative sources
5. Check: ✅ Content Length, ✅ Keyword Density, ✅ Internal Links, ✅ Outbound Links

#### Step 7: Enhance with Media
1. Add relevant images
2. Write descriptive alt text
3. Include keyword in some alt texts naturally
4. Check: ✅ Image Alt Text

#### Step 8: Improve Readability
1. Break long sentences (under 20 words average)
2. Keep paragraphs short (under 150 words)
3. Use transition words (30%+ of sentences)
4. Use active voice (under 10% passive)
5. Check: ✅ All readability metrics

#### Step 9: Technical Optimization
1. Edit URL slug to include keyword
2. Add Direct Answer (300-450 characters)
3. Configure schema markup
4. Check: ✅ Keyword in Slug, ✅ Direct Answer, ✅ Schema

#### Step 10: Final Review
1. Check both scores are 70+
2. Read content aloud for flow
3. Verify all links work
4. Preview on mobile
5. Publish!

### Quick Wins for Better Scores

**5-Minute Improvements:**
1. Add focus keyword to title
2. Add focus keyword to meta description
3. Edit URL slug to include keyword
4. Add 2-3 internal links
5. Break up long paragraphs

**15-Minute Improvements:**
1. Add H2 headings every 300 words
2. Include keyword in one heading
3. Add alt text to all images
4. Add transition words between paragraphs
5. Shorten long sentences

**30-Minute Improvements:**
1. Expand content to 800+ words
2. Add Direct Answer field
3. Configure schema markup
4. Add external links to sources
5. Rewrite passive voice sentences

---

## Indonesian Language Support

MeowSEO includes specialized features for Indonesian language content.

### Indonesian Stemming

The analyzer understands Indonesian word variations:

**Prefixes recognized**:
- me- (membuat → buat)
- di- (dibuat → buat)
- ber- (berjalan → jalan)
- ter- (terbuat → buat)
- pe- (pembuat → buat)

**Suffixes recognized**:
- -an (pembuatan → buat)
- -kan (buatkan → buat)
- -i (buati → buat)
- -nya (bukunya → buku)

**What this means**: Your keyword will match variations automatically!

**Example**:
- Focus keyword: "buat website"
- Will match: "membuat website", "pembuatan website", "dibuatkan website"

### Indonesian Passive Voice

The analyzer detects Indonesian passive voice patterns:

**Patterns detected**:
- di- prefix: dibuat, diambil, ditentukan
- ter- prefix: terbuat, terambil, terpilih
- ke-an pattern: keadaan, kebakaran, keputusan

**Optimization tip**: Use active voice for clearer writing
- ❌ "Website dibuat oleh developer"
- ✅ "Developer membuat website"

### Indonesian Transition Words

Comprehensive list of Indonesian transitions:

**Contrast**: namun, tetapi, akan tetapi, sebaliknya, meskipun

**Cause/Effect**: oleh karena itu, dengan demikian, akibatnya, sebagai hasilnya

**Addition**: selain itu, lebih lanjut, tambahan lagi, juga, pula

**Example**: misalnya, contohnya, sebagai contoh, seperti

**Sequence**: pertama, kedua, ketiga, kemudian, selanjutnya, akhirnya

### Indonesian Abbreviations

The sentence splitter preserves Indonesian abbreviations:
- dr., prof., dll., dst., dsb., yg., dg.

This ensures accurate sentence counting for readability analysis.

---

## Tips and Best Practices

### Writing for Both SEO and Readers

**Balance is key**:
- Write for humans first, search engines second
- Use keywords naturally, not forced
- Focus on providing value
- Make content engaging and useful

**Don't sacrifice quality for scores**:
- A 65 score with great content beats a 95 with poor content
- User engagement matters more than perfect optimization
- Natural writing often performs better than over-optimized content

### Content Types and Target Scores

Different content types have different optimization needs:

**Blog Posts** (Target: SEO 70+, Readability 70+)
- Focus on both SEO and readability
- Aim for comprehensive coverage
- 800-1500 words typical

**Product Pages** (Target: SEO 75+, Readability 65+)
- Prioritize SEO optimization
- Clear, concise descriptions
- 300-800 words typical

**Landing Pages** (Target: SEO 65+, Readability 75+)
- Prioritize readability and conversion
- Clear calls-to-action
- 500-1000 words typical

**News Articles** (Target: SEO 60+, Readability 70+)
- Focus on readability and speed
- Timely, concise content
- 400-800 words typical

### Mobile Optimization

**Remember mobile readers**:
- Shorter paragraphs (look longer on mobile)
- Shorter sentences (easier to read on small screens)
- More headings (improve scannability)
- Larger font sizes (better readability)

### Accessibility Considerations

**Make content accessible**:
- Add alt text to all images
- Use descriptive link text
- Maintain good heading hierarchy
- Ensure sufficient color contrast
- Use simple, clear language

---

## Troubleshooting

### Analysis Not Updating

**Problem**: Scores don't update after editing

**Solutions**:
1. Wait 1-2 seconds after stopping typing (800ms debounce)
2. Check that focus keyword is set
3. Refresh the page
4. Check browser console for errors

### Low Scores Despite Good Content

**Problem**: Scores are low but content seems good

**Solutions**:
1. Review specific analyzer feedback
2. Focus on high-impact improvements first
3. Check that focus keyword is appropriate
4. Verify keyword appears in key locations
5. Remember: quality matters more than perfect scores

### Keyword Not Being Detected

**Problem**: Analyzer says keyword is missing but it's there

**Solutions**:
1. Check spelling and spacing
2. Verify keyword in Focus Keyword field matches content
3. For Indonesian: check if using different word form (stemming should handle this)
4. Try using exact keyword phrase

### Readability Score Too Low

**Problem**: Readability score is low

**Solutions**:
1. Shorten sentences (under 20 words average)
2. Break up long paragraphs (under 150 words)
3. Add transition words between sentences
4. Use more active voice
5. Add more H2/H3 headings

### Performance Issues

**Problem**: Editor is slow or laggy

**Solutions**:
1. Analysis runs in background (shouldn't affect performance)
2. Try disabling other plugins temporarily
3. Clear browser cache
4. Check browser console for errors
5. Ensure browser supports Web Workers

### Indonesian Language Not Working

**Problem**: Indonesian features not detecting correctly

**Solutions**:
1. Verify content is in Indonesian
2. Check that stemming is working (test with common words)
3. Ensure transition words are spelled correctly
4. Report specific issues for improvement

---

## Getting Help

### Additional Resources

- **Developer Guide**: Technical documentation for developers
- **API Documentation**: Complete API reference
- **Requirements Document**: Detailed feature specifications
- **Design Document**: System architecture and design decisions

### Support Channels

For questions or issues:
1. Check this user guide
2. Review troubleshooting section
3. Check plugin documentation
4. Contact plugin support

---

## Conclusion

MeowSEO's Analysis Engine is a powerful tool for content optimization. Remember:

✅ **Focus on quality first**: Great content with good optimization beats perfect scores with poor content

✅ **Use scores as guides**: They indicate areas for improvement, not absolute requirements

✅ **Write naturally**: Forced optimization hurts readability and user experience

✅ **Iterate and improve**: Optimization is a process, not a one-time task

✅ **Test and measure**: Track real results (traffic, rankings, engagement) alongside scores

Happy optimizing! 🚀
