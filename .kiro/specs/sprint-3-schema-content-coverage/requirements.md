# Requirements Document

## Introduction

Sprint 3 expands MeowSEO's schema type coverage and adds specialized content optimization features to achieve feature parity with Yoast SEO Premium and RankMath Pro. This sprint focuses on six key areas: expanded schema types, video content detection, Google News sitemap support, image SEO automation, IndexNow instant indexing, and actionable fix explanations for SEO analyzers.

MeowSEO currently supports 6 schema types (Article, FAQ, HowTo, LocalBusiness, Product, Speakable) while competitors support 20+ types. This sprint adds 7 critical schema types and automated video schema generation to close the gap.

## Glossary

- **Schema_Manager**: The MeowSEO component responsible for generating and outputting JSON-LD structured data
- **Video_Detector**: Component that parses post content to identify embedded video URLs
- **News_Sitemap_Generator**: Component that generates Google News compliant XML sitemaps
- **Image_SEO_Handler**: Component that automatically generates alt text and other image attributes
- **IndexNow_Client**: Component that submits URL updates to IndexNow API endpoints
- **Analyzer**: An SEO or readability check that evaluates content and returns a score
- **Fix_Explanation**: Actionable guidance text that explains how to resolve a failing analyzer check
- **Embed_URL**: A YouTube or Vimeo video URL embedded in post content
- **News_Post**: A post published within the last 2 days, eligible for Google News sitemap inclusion
- **Pattern_Variable**: A placeholder token like %imagetitle% that gets replaced with actual values

## Requirements

### Requirement 1: Expanded Schema Type Support

**User Story:** As a content creator, I want to generate schema markup for recipes, events, videos, courses, job postings, books, and people, so that my specialized content appears with rich results in search engines.

#### Acceptance Criteria

1. WHEN a user selects "Recipe" schema type, THE Schema_Manager SHALL generate valid Recipe JSON-LD with name, description, ingredients, instructions, prepTime, cookTime, and nutrition properties
2. WHEN a user selects "Event" schema type, THE Schema_Manager SHALL generate valid Event JSON-LD with name, startDate, endDate, location, and organizer properties
3. WHEN a user selects "VideoObject" schema type, THE Schema_Manager SHALL generate valid VideoObject JSON-LD with name, description, thumbnailUrl, uploadDate, and duration properties
4. WHEN a user selects "Course" schema type, THE Schema_Manager SHALL generate valid Course JSON-LD with name, description, provider, and courseCode properties
5. WHEN a user selects "JobPosting" schema type, THE Schema_Manager SHALL generate valid JobPosting JSON-LD with title, description, datePosted, employmentType, and hiringOrganization properties
6. WHEN a user selects "Book" schema type, THE Schema_Manager SHALL generate valid Book JSON-LD with name, author, isbn, numberOfPages, and publisher properties
7. WHEN a user selects "Person" schema type, THE Schema_Manager SHALL generate valid Person JSON-LD with name, jobTitle, description, and image properties
8. THE Schema_Manager SHALL validate all generated schema against schema.org specifications
9. WHEN schema is generated, THE Schema_Manager SHALL output it as JSON-LD in the document head
10. THE Schema_Manager SHALL provide form fields in the Gutenberg sidebar for all required and recommended properties of each schema type

### Requirement 2: Video Schema Auto-Detection

**User Story:** As a content creator, I want video schema to be automatically generated when I embed YouTube or Vimeo videos, so that my video content appears with rich results without manual schema configuration.

#### Acceptance Criteria

1. WHEN post content contains an Embed_URL, THE Video_Detector SHALL identify all YouTube and Vimeo video URLs
2. WHEN a YouTube Embed_URL is detected, THE Video_Detector SHALL extract the video ID from the URL
3. WHEN a Vimeo Embed_URL is detected, THE Video_Detector SHALL extract the video ID from the URL
4. WHEN a video is detected, THE Schema_Manager SHALL automatically generate VideoObject schema with the video URL
5. WHEN VideoObject schema is generated, THE Schema_Manager SHALL attempt to fetch video metadata including title, description, thumbnail URL, and duration
6. IF video metadata cannot be fetched, THEN THE Schema_Manager SHALL generate VideoObject schema with the video URL and allow manual property entry
7. THE Video_Detector SHALL detect videos in WordPress video blocks, YouTube embed blocks, and Vimeo embed blocks
8. THE Video_Detector SHALL detect videos in classic editor content using oEmbed patterns
9. WHEN multiple videos are detected in a single post, THE Schema_Manager SHALL generate a separate VideoObject for each video
10. THE Schema_Manager SHALL provide a toggle in settings to enable or disable automatic video schema generation

### Requirement 3: Google News Sitemap

**User Story:** As a news publisher, I want a dedicated Google News sitemap, so that my articles are discovered and indexed by Google News.

#### Acceptance Criteria

1. THE News_Sitemap_Generator SHALL generate a sitemap at the URL path /news-sitemap.xml
2. WHEN the news sitemap is requested, THE News_Sitemap_Generator SHALL include only News_Posts
3. WHEN generating news sitemap entries, THE News_Sitemap_Generator SHALL include news:news elements with publication name, language, and publication date
4. THE News_Sitemap_Generator SHALL include news:title elements containing the post title
5. THE News_Sitemap_Generator SHALL include news:keywords elements if focus keywords are set
6. THE News_Sitemap_Generator SHALL respect the Googlebot-News noindex meta tag when set on individual posts
7. WHEN a post has noindex set for Googlebot-News, THE News_Sitemap_Generator SHALL exclude it from the news sitemap
8. THE News_Sitemap_Generator SHALL cache the news sitemap for 5 minutes to reduce database queries
9. THE News_Sitemap_Generator SHALL provide settings to configure the publication name and default language
10. THE News_Sitemap_Generator SHALL add the news sitemap URL to the sitemap index at /sitemap.xml

### Requirement 4: Image SEO Automation

**User Story:** As a content creator, I want alt text to be automatically generated for images that lack it, so that my images are accessible and optimized for search engines without manual effort.

#### Acceptance Criteria

1. WHEN an image is inserted without alt text, THE Image_SEO_Handler SHALL generate alt text from the image title
2. THE Image_SEO_Handler SHALL support Pattern_Variables including %imagetitle% and %imagealt%
3. WHEN %imagetitle% is used in a pattern, THE Image_SEO_Handler SHALL replace it with the attachment title
4. WHEN %imagealt% is used in a pattern, THE Image_SEO_Handler SHALL replace it with the existing alt text if present
5. THE Image_SEO_Handler SHALL apply alt text patterns via the wp_get_attachment_image_attributes filter
6. THE Image_SEO_Handler SHALL provide a setting to define the alt text pattern template
7. THE Image_SEO_Handler SHALL provide a setting to enable or disable automatic alt text generation
8. WHEN automatic alt text is disabled, THE Image_SEO_Handler SHALL not modify image attributes
9. THE Image_SEO_Handler SHALL generate alt text for images in post content, featured images, and gallery blocks
10. THE Image_SEO_Handler SHALL not override existing alt text unless explicitly configured to do so

### Requirement 5: IndexNow Instant Indexing

**User Story:** As a content publisher, I want my content to be instantly submitted to search engines when published or updated, so that it appears in search results faster.

#### Acceptance Criteria

1. WHEN a post is published, THE IndexNow_Client SHALL submit the post URL to api.indexnow.org
2. WHEN a post is updated, THE IndexNow_Client SHALL submit the post URL to api.indexnow.org
3. THE IndexNow_Client SHALL include a valid API key in all IndexNow requests
4. THE IndexNow_Client SHALL provide a settings field for users to enter their IndexNow API key
5. THE IndexNow_Client SHALL generate a new IndexNow API key if none is configured
6. THE IndexNow_Client SHALL store the API key in WordPress options
7. THE IndexNow_Client SHALL implement request throttling with a minimum 5-second delay between submissions
8. WHEN multiple posts are published simultaneously, THE IndexNow_Client SHALL batch submissions with throttling
9. THE IndexNow_Client SHALL log all submission attempts with timestamp, URL, and response status
10. THE IndexNow_Client SHALL provide a submission history view in the admin interface showing the last 100 submissions
11. IF an IndexNow submission fails, THEN THE IndexNow_Client SHALL retry up to 3 times with exponential backoff
12. THE IndexNow_Client SHALL provide a setting to enable or disable IndexNow submissions

### Requirement 6: Analyzer Fix Explanations

**User Story:** As a content creator, I want to see actionable guidance when an SEO check fails, so that I understand how to fix the issue and improve my content.

#### Acceptance Criteria

1. WHEN an Analyzer returns a failing score, THE Analyzer SHALL include a Fix_Explanation in the result
2. THE Fix_Explanation SHALL describe what the issue is in clear, non-technical language
3. THE Fix_Explanation SHALL provide specific, actionable steps to resolve the issue
4. WHEN the title is too short, THE Fix_Explanation SHALL specify the recommended minimum character count
5. WHEN the title is too long, THE Fix_Explanation SHALL specify the recommended maximum character count
6. WHEN the focus keyword is missing from the title, THE Fix_Explanation SHALL suggest adding it near the beginning
7. WHEN the focus keyword is missing from the first paragraph, THE Fix_Explanation SHALL suggest including it in the opening sentences
8. WHEN the meta description is missing, THE Fix_Explanation SHALL suggest writing a compelling 150-160 character summary
9. WHEN the content is too short, THE Fix_Explanation SHALL specify the recommended minimum word count
10. WHEN keyword density is too low, THE Fix_Explanation SHALL suggest the target density range and where to add the keyword
11. WHEN keyword density is too high, THE Fix_Explanation SHALL warn about keyword stuffing and suggest natural language alternatives
12. WHEN headings lack the focus keyword, THE Fix_Explanation SHALL suggest adding it to at least one H2 or H3 heading
13. WHEN images lack alt text, THE Fix_Explanation SHALL suggest describing the image content and including the focus keyword where relevant
14. WHEN the URL slug is not optimized, THE Fix_Explanation SHALL suggest including the focus keyword and keeping it short
15. THE Fix_Explanation SHALL be displayed in the Gutenberg sidebar below each failing analyzer result
