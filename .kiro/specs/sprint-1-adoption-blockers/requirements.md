# Requirements Document

## Introduction

Sprint 1 - Adoption Blockers addresses the five most critical features preventing users from switching from Yoast SEO Premium or RankMath Pro to MeowSEO. These features represent the minimum viable feature set required for competitive parity in the WordPress SEO plugin market. Without these capabilities, users cannot migrate their existing SEO data, manage multiple keywords, view SEO health across their content, or configure site-wide SEO defaults.

## Glossary

- **MeowSEO**: The WordPress SEO plugin being developed
- **Yoast_SEO**: Yoast SEO Premium plugin (competitor)
- **RankMath**: RankMath Pro plugin (competitor)
- **Import_System**: The data migration subsystem that converts competitor plugin data to MeowSEO format
- **Keyword_Analyzer**: The analysis engine that evaluates content against focus keywords
- **List_Table**: WordPress WP_List_Table component displaying posts/pages in admin
- **Settings_Manager**: The admin interface for configuring global plugin settings
- **Title_Pattern_Engine**: The system that generates meta titles and descriptions using variable substitution
- **Postmeta**: WordPress post metadata stored in wp_postmeta table
- **Termmeta**: WordPress term metadata stored in wp_termmeta table
- **Archive_Page**: WordPress template for category, tag, author, date, or custom taxonomy listings
- **Robots_Meta**: HTML meta tags controlling search engine indexing (noindex, nofollow)

## Requirements

### Requirement 1: Import Competitor SEO Data

**User Story:** As a site owner switching from Yoast SEO or RankMath, I want to import all my existing SEO data, so that I don't lose years of optimization work.

#### Acceptance Criteria

1. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_title` postmeta to `_meowseo_title`
2. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_metadesc` postmeta to `_meowseo_description`
3. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_focuskw` postmeta to `_meowseo_focus_keyword`
4. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_canonical` postmeta to `_meowseo_canonical_url`
5. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_meta-robots-noindex` postmeta to `_meowseo_robots_noindex`
6. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_meta-robots-nofollow` postmeta to `_meowseo_robots_nofollow`
7. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_opengraph-title` postmeta to `_meowseo_og_title`
8. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_opengraph-description` postmeta to `_meowseo_og_description`
9. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_twitter-title` postmeta to `_meowseo_twitter_title`
10. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL map `_yoast_wpseo_twitter-description` postmeta to `_meowseo_twitter_description`
11. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_title` postmeta to `_meowseo_title`
12. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_description` postmeta to `_meowseo_description`
13. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_focus_keyword` postmeta to `_meowseo_focus_keyword`
14. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_canonical_url` postmeta to `_meowseo_canonical_url`
15. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_robots` postmeta array to `_meowseo_robots_noindex` and `_meowseo_robots_nofollow`
16. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_facebook_title` postmeta to `_meowseo_og_title`
17. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_facebook_description` postmeta to `_meowseo_og_description`
18. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_twitter_title` postmeta to `_meowseo_twitter_title`
19. WHEN the user initiates a RankMath import, THE Import_System SHALL map `rank_math_twitter_description` postmeta to `_meowseo_twitter_description`
20. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL import taxonomy term metadata from `_wpseo_title` and `_wpseo_desc` termmeta
21. WHEN the user initiates a RankMath import, THE Import_System SHALL import taxonomy term metadata from `rank_math_title` and `rank_math_description` termmeta
22. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL import plugin settings from `wpseo` and `wpseo_titles` options
23. WHEN the user initiates a RankMath import, THE Import_System SHALL import plugin settings from `rank-math-options-general` and `rank-math-options-titles` options
24. WHEN the user initiates a Yoast SEO import, THE Import_System SHALL import redirects from `wpseo_redirect` custom post type
25. WHEN the user initiates a RankMath import, THE Import_System SHALL import redirects from `rank_math_redirections` database table
26. WHEN an import completes successfully, THE Import_System SHALL display a summary showing the count of imported posts, terms, settings, and redirects
27. IF an import encounters invalid data, THEN THE Import_System SHALL log the error and continue processing remaining items
28. WHEN an import is in progress, THE Import_System SHALL process items in batches of 100 to prevent timeout
29. FOR ALL imported postmeta values, parsing the stored value then validating it against MeowSEO field constraints SHALL produce a valid result

### Requirement 2: Analyze Multiple Focus Keywords

**User Story:** As a content creator, I want to optimize my content for up to 5 focus keywords, so that I can target multiple search queries per page.

#### Acceptance Criteria

1. THE MeowSEO SHALL store secondary keywords in `_meowseo_secondary_keywords` postmeta as a JSON array
2. WHEN a user adds a secondary keyword, THE MeowSEO SHALL validate that the total keyword count does not exceed 5
3. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword density analysis once for each focus keyword
4. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword-in-title analysis once for each focus keyword
5. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword-in-heading analysis once for each focus keyword
6. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword-in-slug analysis once for each focus keyword
7. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword-in-first-paragraph analysis once for each focus keyword
8. WHEN content is analyzed, THE Keyword_Analyzer SHALL run keyword-in-meta-description analysis once for each focus keyword
9. WHEN analysis completes, THE MeowSEO SHALL display a separate score row for each keyword in the Gutenberg sidebar
10. WHEN a user removes a secondary keyword, THE MeowSEO SHALL remove that keyword from the `_meowseo_secondary_keywords` array
11. WHEN a user reorders secondary keywords, THE MeowSEO SHALL update the array order in `_meowseo_secondary_keywords`
12. FOR ALL valid keyword sets with N keywords where 1 <= N <= 5, running analysis then adding a keyword then running analysis again SHALL produce N+1 keyword score rows

### Requirement 3: Display SEO Score in Post Lists

**User Story:** As a site administrator, I want to see SEO scores for all posts and pages in the admin list tables, so that I can identify content that needs optimization.

#### Acceptance Criteria

1. WHEN viewing the posts list table, THE List_Table SHALL display an SEO Score column
2. WHEN viewing the pages list table, THE List_Table SHALL display an SEO Score column
3. WHERE a custom post type is public, THE List_Table SHALL display an SEO Score column for that post type
4. WHEN displaying an SEO score, THE List_Table SHALL show a colored indicator based on score value
5. WHEN the SEO score is 0-40, THE List_Table SHALL display a red indicator
6. WHEN the SEO score is 41-70, THE List_Table SHALL display an orange indicator
7. WHEN the SEO score is 71-100, THE List_Table SHALL display a green indicator
8. WHEN a user clicks the SEO Score column header, THE List_Table SHALL sort posts by `_meowseo_seo_score` postmeta in descending order
9. WHEN a user clicks the SEO Score column header again, THE List_Table SHALL sort posts by `_meowseo_seo_score` postmeta in ascending order
10. WHEN a post has no SEO score, THE List_Table SHALL display a gray dash indicator
11. FOR ALL posts with stored `_meowseo_seo_score` values, sorting by SEO Score column then retrieving the first post SHALL return the post with the highest score value

### Requirement 4: Configure Global Archive Robots Settings

**User Story:** As a site administrator, I want to set default robots meta tags for archive pages, so that I can control search engine indexing site-wide without editing individual pages.

#### Acceptance Criteria

1. THE Settings_Manager SHALL provide a setting for author archive robots meta tags
2. THE Settings_Manager SHALL provide a setting for date archive robots meta tags
3. THE Settings_Manager SHALL provide a setting for category archive robots meta tags
4. THE Settings_Manager SHALL provide a setting for tag archive robots meta tags
5. THE Settings_Manager SHALL provide a setting for search results page robots meta tags
6. THE Settings_Manager SHALL provide a setting for media attachment page robots meta tags
7. THE Settings_Manager SHALL provide a setting for custom post type archive robots meta tags
8. WHEN viewing an author archive page, THE MeowSEO SHALL output robots meta tags based on the author archive setting
9. WHEN viewing a date archive page, THE MeowSEO SHALL output robots meta tags based on the date archive setting
10. WHEN viewing a category archive page, THE MeowSEO SHALL output robots meta tags based on the category archive setting
11. WHEN viewing a tag archive page, THE MeowSEO SHALL output robots meta tags based on the tag archive setting
12. WHEN viewing a search results page, THE MeowSEO SHALL output robots meta tags based on the search results setting
13. WHEN viewing a media attachment page, THE MeowSEO SHALL output robots meta tags based on the media attachment setting
14. WHEN viewing a custom post type archive page, THE MeowSEO SHALL output robots meta tags based on that post type's archive setting
15. WHERE a taxonomy term has custom robots meta tags set, THE MeowSEO SHALL use the term-specific setting instead of the global default
16. FOR ALL archive page types, configuring a global robots setting then visiting that archive type SHALL output the configured robots meta tag

### Requirement 5: Generate Archive Page Titles and Descriptions

**User Story:** As a site administrator, I want to define title and description patterns for archive pages, so that search engines display consistent, branded metadata for my category, tag, and author pages.

#### Acceptance Criteria

1. THE Title_Pattern_Engine SHALL support a pattern for category archive titles
2. THE Title_Pattern_Engine SHALL support a pattern for category archive descriptions
3. THE Title_Pattern_Engine SHALL support a pattern for tag archive titles
4. THE Title_Pattern_Engine SHALL support a pattern for tag archive descriptions
5. THE Title_Pattern_Engine SHALL support a pattern for custom taxonomy archive titles
6. THE Title_Pattern_Engine SHALL support a pattern for custom taxonomy archive descriptions
7. THE Title_Pattern_Engine SHALL support a pattern for author page titles
8. THE Title_Pattern_Engine SHALL support a pattern for author page descriptions
9. THE Title_Pattern_Engine SHALL support a pattern for search results page titles
10. THE Title_Pattern_Engine SHALL support a pattern for search results page descriptions
11. THE Title_Pattern_Engine SHALL support a pattern for date archive titles
12. THE Title_Pattern_Engine SHALL support a pattern for date archive descriptions
13. THE Title_Pattern_Engine SHALL support a pattern for 404 page titles
14. THE Title_Pattern_Engine SHALL support a pattern for 404 page descriptions
15. THE Title_Pattern_Engine SHALL support a pattern for homepage titles
16. THE Title_Pattern_Engine SHALL support a pattern for homepage descriptions
17. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%title%%` with the archive title
18. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%sitename%%` with the site name
19. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%category%%` with the category name
20. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%tag%%` with the tag name
21. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%term%%` with the taxonomy term name
22. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%date%%` with the archive date
23. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%name%%` with the author display name
24. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%searchphrase%%` with the search query
25. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%posttype%%` with the post type label
26. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%sep%%` with the configured separator character
27. WHEN processing a pattern, THE Title_Pattern_Engine SHALL replace `%%page%%` with the current page number for paginated archives
28. WHEN viewing a category archive, THE MeowSEO SHALL output the meta title generated from the category title pattern
29. WHEN viewing a tag archive, THE MeowSEO SHALL output the meta title generated from the tag title pattern
30. WHEN viewing an author page, THE MeowSEO SHALL output the meta title generated from the author title pattern
31. WHEN viewing a search results page, THE MeowSEO SHALL output the meta title generated from the search title pattern
32. WHEN viewing a date archive, THE MeowSEO SHALL output the meta title generated from the date title pattern
33. WHEN viewing a 404 page, THE MeowSEO SHALL output the meta title generated from the 404 title pattern
34. WHEN viewing the homepage, THE MeowSEO SHALL output the meta title generated from the homepage title pattern
35. FOR ALL archive patterns with variables, setting a pattern then visiting that archive type SHALL output a title containing the substituted variable values
