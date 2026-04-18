# Requirements Document

## Introduction

Sprint 2 - Feature Parity addresses core SEO features that professional users expect from a premium SEO plugin. These features are standard in both Yoast SEO Premium and RankMath Pro, and their absence represents a significant barrier to adoption. This sprint focuses on six essential capabilities: global schema identity markup, real-time SERP preview with character counting, webmaster tools verification methods, robots.txt management UI, redirect import/export functionality, and cornerstone content marking.

## Glossary

- **MeowSEO**: The WordPress SEO plugin system being developed
- **Schema_Output**: The component responsible for generating JSON-LD structured data
- **SERP_Preview**: The visual component showing how content appears in search results
- **Settings_Manager**: The admin interface component for managing plugin configuration
- **Robots_Txt_Editor**: The admin UI for editing virtual robots.txt content
- **Redirect_Manager**: The component handling URL redirects and their persistence
- **Post_List_Table**: WordPress admin interface showing lists of posts/pages
- **Cornerstone_Manager**: The component for marking and managing pillar content
- **Webmaster_Verification**: The component outputting verification meta tags
- **Character_Counter**: The component tracking and displaying character counts for SEO fields

## Requirements

### Requirement 1: Global Schema Identity Markup

**User Story:** As a site owner, I want WebSite and Organization schema output on every page, so that Google can display sitelinks search box and brand knowledge panels in search results.

#### Acceptance Criteria

1. THE Schema_Output SHALL generate WebSite schema with name, url, and potentialAction properties on every page
2. THE Schema_Output SHALL generate Organization schema with name, url, logo, and contactPoint properties on every page
3. WHEN Organization schema is configured, THE Schema_Output SHALL include sameAs properties for social media profiles
4. THE Settings_Manager SHALL provide input fields for organization name, logo URL, and social media URLs
5. WHEN no organization logo is configured, THE Schema_Output SHALL omit the logo property from Organization schema
6. THE Schema_Output SHALL output WebSite and Organization schema as separate JSON-LD script blocks in the document head
7. FOR ALL pages with valid organization settings, generating then parsing the JSON-LD output SHALL produce equivalent schema objects (round-trip property)

### Requirement 2: Real-Time SERP Preview

**User Story:** As a content editor, I want to see a live preview of how my content appears in Google search results with character counting, so that I can optimize titles and descriptions before publishing.

#### Acceptance Criteria

1. THE SERP_Preview SHALL display a visual representation of Google search result appearance
2. WHEN the SEO title changes, THE SERP_Preview SHALL update the displayed title within 500ms
3. WHEN the meta description changes, THE SERP_Preview SHALL update the displayed description within 500ms
4. THE Character_Counter SHALL display the current character count for SEO title fields
5. THE Character_Counter SHALL display the current character count for meta description fields
6. WHEN the SEO title exceeds 60 characters, THE Character_Counter SHALL display a red color indicator
7. WHEN the SEO title is between 50 and 60 characters, THE Character_Counter SHALL display an orange color indicator
8. WHEN the SEO title is below 50 characters, THE Character_Counter SHALL display a green color indicator
9. WHEN the meta description exceeds 155 characters, THE Character_Counter SHALL display a red color indicator
10. WHEN the meta description is between 120 and 155 characters, THE Character_Counter SHALL display a green color indicator
11. WHEN the meta description is below 120 characters, THE Character_Counter SHALL display an orange color indicator
12. THE SERP_Preview SHALL provide a toggle control for switching between mobile and desktop display modes
13. WHEN mobile mode is selected, THE SERP_Preview SHALL display the preview with mobile-specific width constraints
14. WHEN desktop mode is selected, THE SERP_Preview SHALL display the preview with desktop-specific width constraints
15. THE SERP_Preview SHALL truncate displayed title text at 60 characters with an ellipsis indicator
16. THE SERP_Preview SHALL truncate displayed description text at 155 characters with an ellipsis indicator

### Requirement 3: Webmaster Tools Verification

**User Story:** As a site administrator, I want to add webmaster verification meta tags for Google, Bing, and Yandex, so that I can verify site ownership without uploading HTML files.

#### Acceptance Criteria

1. THE Settings_Manager SHALL provide an input field for Google Search Console verification code
2. THE Settings_Manager SHALL provide an input field for Bing Webmaster Tools verification code
3. THE Settings_Manager SHALL provide an input field for Yandex Webmaster verification code
4. WHEN a Google verification code is configured, THE Webmaster_Verification SHALL output a meta tag with name "google-site-verification" in the document head
5. WHEN a Bing verification code is configured, THE Webmaster_Verification SHALL output a meta tag with name "msvalidate.01" in the document head
6. WHEN a Yandex verification code is configured, THE Webmaster_Verification SHALL output a meta tag with name "yandex-verification" in the document head
7. WHEN no verification code is configured for a service, THE Webmaster_Verification SHALL omit that service's meta tag
8. THE Webmaster_Verification SHALL sanitize verification codes to remove HTML tags and script content
9. THE Webmaster_Verification SHALL output verification meta tags before other meta tags in the document head

### Requirement 4: Robots.txt Editor UI

**User Story:** As a site administrator, I want to edit the virtual robots.txt file through the admin interface, so that I can manage crawler directives without FTP access.

#### Acceptance Criteria

1. THE Settings_Manager SHALL provide a textarea input for editing robots.txt content
2. THE Robots_Txt_Editor SHALL load the current virtual robots.txt content into the textarea on page load
3. WHEN the administrator saves robots.txt changes, THE Robots_Txt_Editor SHALL persist the content to WordPress options
4. WHEN the administrator saves robots.txt changes, THE Robots_Txt_Editor SHALL validate that the content contains valid robots.txt syntax
5. WHEN invalid robots.txt syntax is detected, THE Robots_Txt_Editor SHALL display an error message and prevent saving
6. THE Robots_Txt_Editor SHALL display a preview of the current robots.txt content accessible at /robots.txt
7. THE Robots_Txt_Editor SHALL provide a reset button that restores default WordPress robots.txt content
8. WHEN the reset button is clicked, THE Robots_Txt_Editor SHALL display a confirmation dialog before resetting
9. THE Robots_Txt_Editor SHALL display help text explaining robots.txt syntax and common directives

### Requirement 5: Redirect CSV Import and Export

**User Story:** As a site administrator, I want to import and export redirects as CSV files, so that I can bulk manage redirects and migrate them between sites.

#### Acceptance Criteria

1. THE Redirect_Manager SHALL provide a file upload control for importing CSV files
2. THE Redirect_Manager SHALL provide a download button for exporting redirects as CSV
3. WHEN a CSV file is uploaded, THE Redirect_Manager SHALL parse the CSV content into redirect records
4. THE Redirect_Manager SHALL validate that imported CSV files contain required columns: source_url, target_url, status_code
5. WHEN CSV parsing fails, THE Redirect_Manager SHALL display an error message indicating the line number and error type
6. WHEN CSV import succeeds, THE Redirect_Manager SHALL create redirect records for each valid row
7. WHEN CSV import succeeds, THE Redirect_Manager SHALL display a success message with the count of imported redirects
8. THE Redirect_Manager SHALL export redirects as CSV with columns: source_url, target_url, status_code, hits, created_date
9. THE Redirect_Manager SHALL include all existing redirects in the CSV export
10. THE Redirect_Manager SHALL generate CSV export filenames with the format "meowseo-redirects-YYYY-MM-DD.csv"
11. FOR ALL valid redirect CSV exports, importing the exported file SHALL recreate equivalent redirect records (round-trip property)
12. WHEN importing redirects with duplicate source URLs, THE Redirect_Manager SHALL skip duplicates and report the count of skipped records

### Requirement 6: Cornerstone Content Management

**User Story:** As a content strategist, I want to mark posts as cornerstone content and see them highlighted in the post list, so that I can prioritize and maintain my most important content.

#### Acceptance Criteria

1. THE Settings_Manager SHALL provide a checkbox control labeled "Mark as Cornerstone Content" in the post editor sidebar
2. WHEN the cornerstone checkbox is checked, THE Cornerstone_Manager SHALL store the value "1" in postmeta key "_meowseo_is_cornerstone"
3. WHEN the cornerstone checkbox is unchecked, THE Cornerstone_Manager SHALL delete the "_meowseo_is_cornerstone" postmeta key
4. THE Post_List_Table SHALL display a cornerstone indicator icon in a dedicated column for posts marked as cornerstone
5. THE Post_List_Table SHALL provide a filter dropdown to show only cornerstone content
6. WHEN the cornerstone filter is applied, THE Post_List_Table SHALL display only posts with "_meowseo_is_cornerstone" postmeta set to "1"
7. THE Cornerstone_Manager SHALL add the cornerstone column to all public post types
8. THE Cornerstone_Manager SHALL make the cornerstone column sortable in the post list table
9. WHEN generating internal link suggestions, THE Cornerstone_Manager SHALL weight cornerstone posts with 2x priority compared to non-cornerstone posts
10. THE Cornerstone_Manager SHALL display a count of total cornerstone posts in the MeowSEO dashboard widget

## Notes

### Parser and Serializer Requirements

This specification includes two critical round-trip requirements:

1. **Requirement 1.7**: Schema JSON-LD generation and parsing must be reversible. This ensures that the structured data output is valid and can be correctly interpreted by search engines.

2. **Requirement 5.11**: CSV export and import must be reversible. This ensures data integrity when migrating redirects between sites or backing up redirect configurations.

Both requirements MUST be tested with property-based tests that generate diverse inputs and verify the round-trip property holds for all valid cases.

### Integration with Existing Systems

- **Schema_Output** extends the existing `includes/modules/schema/class-schema-output.php` component
- **SERP_Preview** integrates with the Gutenberg sidebar in `assets/src/gutenberg/components/`
- **Settings_Manager** extends `includes/admin/class-settings-manager.php`
- **Robots_Txt_Editor** builds on `includes/modules/seo/class-robots-txt.php`
- **Redirect_Manager** extends `includes/modules/redirects/class-redirect-manager.php`
- **Cornerstone_Manager** integrates with WordPress post list tables via `manage_posts_columns` filter
