# Implementation Plan: Sprint 3 - Schema + Content Coverage

## Overview

This implementation plan breaks down Sprint 3 into discrete coding tasks that build incrementally. The sprint adds 7 new schema types, video auto-detection, Google News sitemap, image SEO automation, IndexNow instant indexing, and analyzer fix explanations.

Each task references specific requirements for traceability. Property-based tests validate universal correctness properties from the design document. Tasks marked with `*` are optional and can be skipped for faster MVP.

## Tasks

- [ ] 1. Set up module structure and base interfaces
  - Create directory structure for new modules (image-seo, indexnow)
  - Create directory structure for new schema generators
  - Set up autoloader entries for new classes
  - _Requirements: 1.1-1.10, 2.1-2.10, 3.1-3.10, 4.1-4.10, 5.1-5.12, 6.1-6.15_

- [ ] 2. Implement Recipe schema generator
  - [ ] 2.1 Create Recipe_Schema_Generator class with generate() method
    - Implement Recipe schema JSON-LD structure with all required fields (name, description, recipeIngredient, recipeInstructions)
    - Implement optional fields (prepTime, cookTime, totalTime, recipeYield, recipeCategory, recipeCuisine, nutrition)
    - Add format_ingredients(), format_instructions(), and format_nutrition() helper methods
    - Implement validate_config() to check required fields
    - _Requirements: 1.1_
  
  - [ ]* 2.2 Write property test for Recipe schema generation
    - **Property 1: Schema Generation Preserves Configuration**
    - **Validates: Requirements 1.1**
    - Generate random Recipe configurations, verify all required properties present in output
  
  - [ ] 2.3 Add Recipe schema to Schema_Builder
    - Register 'Recipe' in schema_types array
    - Add build_recipe_schema() method to Schema_Builder
    - Wire Recipe_Schema_Generator into schema generation flow
    - _Requirements: 1.1, 1.9_

- [ ] 3. Implement Event schema generator
  - [ ] 3.1 Create Event_Schema_Generator class with generate() method
    - Implement Event schema JSON-LD structure with required fields (name, startDate, location)
    - Implement optional fields (endDate, description, eventStatus, eventAttendanceMode, organizer, offers)
    - Implement validate_config() to check required fields
    - _Requirements: 1.2_
  
  - [ ]* 3.2 Write property test for Event schema generation
    - **Property 1: Schema Generation Preserves Configuration**
    - **Validates: Requirements 1.2**
    - Generate random Event configurations, verify all required properties present in output
  
  - [ ] 3.3 Add Event schema to Schema_Builder
    - Register 'Event' in schema_types array
    - Add build_event_schema() method to Schema_Builder
    - Wire Event_Schema_Generator into schema generation flow
    - _Requirements: 1.2, 1.9_

- [ ] 4. Implement VideoObject schema generator
  - [ ] 4.1 Create Video_Schema_Generator class with generate() method
    - Implement VideoObject schema JSON-LD structure with required fields (name, description, thumbnailUrl, uploadDate)
    - Implement optional fields (duration, contentUrl, embedUrl)
    - Implement validate_config() to check required fields
    - _Requirements: 1.3_
  
  - [ ]* 4.2 Write property test for VideoObject schema generation
    - **Property 1: Schema Generation Preserves Configuration**
    - **Validates: Requirements 1.3**
    - Generate random VideoObject configurations, verify all required properties present in output
  
  - [ ] 4.3 Add VideoObject schema to Schema_Builder
    - Register 'VideoObject' in schema_types array
    - Add build_video_schema() method to Schema_Builder
    - Wire Video_Schema_Generator into schema generation flow
    - _Requirements: 1.3, 1.9_

- [ ] 5. Implement Course, JobPosting, Book, and Person schema generators
  - [ ] 5.1 Create Course_Schema_Generator class
    - Implement Course schema with required fields (name, description, provider)
    - Implement optional fields (courseCode, hasCourseInstance)
    - _Requirements: 1.4_
  
  - [ ] 5.2 Create Job_Schema_Generator class
    - Implement JobPosting schema with required fields (title, description, datePosted, hiringOrganization)
    - Implement optional fields (validThrough, employmentType, jobLocation, baseSalary)
    - _Requirements: 1.5_
  
  - [ ] 5.3 Create Book_Schema_Generator class
    - Implement Book schema with required fields (name, author)
    - Implement optional fields (isbn, numberOfPages, publisher, datePublished, bookFormat)
    - _Requirements: 1.6_
  
  - [ ] 5.4 Create Person_Schema_Generator class
    - Implement Person schema with required fields (name)
    - Implement optional fields (jobTitle, description, image, url, sameAs)
    - _Requirements: 1.7_
  
  - [ ]* 5.5 Write property tests for Course, JobPosting, Book, and Person schemas
    - **Property 1: Schema Generation Preserves Configuration**
    - **Validates: Requirements 1.4, 1.5, 1.6, 1.7**
    - Generate random configurations for each schema type, verify all required properties present
  
  - [ ] 5.6 Register all four schema types in Schema_Builder
    - Add 'Course', 'JobPosting', 'Book', 'Person' to schema_types array
    - Add build methods for each schema type
    - Wire generators into schema generation flow
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.9_

- [ ] 6. Implement schema validation
  - [ ] 6.1 Add schema validation to Schema_Builder
    - Implement validation for all 7 new schema types
    - Check required properties are present
    - Check property types are correct
    - Log validation warnings for invalid schemas
    - _Requirements: 1.8_
  
  - [ ]* 6.2 Write property test for schema validation
    - **Property 2: Schema Validation Correctness**
    - **Validates: Requirements 1.8**
    - Generate valid and invalid schema objects, verify validation correctly identifies each

- [ ] 7. Checkpoint - Verify schema generation
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Implement Video_Detector for YouTube and Vimeo detection
  - [ ] 8.1 Create Video_Detector class with detect_videos() method
    - Implement detect_youtube_videos() with regex patterns for standard, short, and embed URLs
    - Implement detect_vimeo_videos() with regex patterns for standard and player URLs
    - Implement extract_youtube_id() and extract_vimeo_id() helper methods
    - Return array of detected videos with platform and ID
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [ ]* 8.2 Write property test for video URL parsing
    - **Property 3: Video URL Parsing Accuracy**
    - **Validates: Requirements 2.1, 2.2, 2.3**
    - Generate random YouTube/Vimeo URLs in various formats, verify correct ID extraction
  
  - [ ] 8.3 Add Gutenberg block parsing to Video_Detector
    - Implement parse_gutenberg_blocks() to extract video URLs from embed blocks
    - Detect YouTube embed blocks (<!-- wp:embed {"url":"https://youtube.com/watch?v=...")
    - Detect Vimeo embed blocks (<!-- wp:embed {"url":"https://vimeo.com/...")
    - _Requirements: 2.7_
  
  - [ ] 8.4 Add classic editor parsing to Video_Detector
    - Implement parse_classic_editor_content() to extract oEmbed URLs
    - Use regex patterns to find YouTube and Vimeo URLs in content
    - _Requirements: 2.8_

- [ ] 9. Implement video metadata fetching
  - [ ] 9.1 Add fetch_video_metadata() to Video_Detector
    - Implement fetch_youtube_metadata() using YouTube oEmbed API
    - Implement fetch_vimeo_metadata() using Vimeo oEmbed API
    - Extract title, description, thumbnail_url, and duration from API responses
    - Handle API failures gracefully with fallback to URL-only schema
    - _Requirements: 2.5, 2.6_
  
  - [ ]* 9.2 Write property test for video schema fallback behavior
    - **Property 5: Video Schema Fallback Behavior**
    - **Validates: Requirements 2.6**
    - Simulate API failures, verify schema is generated with URL only

- [ ] 10. Integrate video detection with Schema_Builder
  - [ ] 10.1 Add automatic video schema generation to Schema_Builder
    - Call Video_Detector in build() method to detect videos in post content
    - Generate VideoObject schema for each detected video
    - Add is_auto_video_schema_enabled() check
    - Add build_video_schema_from_detection() method
    - _Requirements: 2.4_
  
  - [ ]* 10.2 Write property tests for automatic video schema generation
    - **Property 4: Automatic Video Schema Generation**
    - **Validates: Requirements 2.4**
    - Generate content with embedded videos, verify VideoObject schemas are created
    - **Property 6: Multiple Video Schema Generation**
    - **Validates: Requirements 2.9**
    - Generate content with 0-10 videos, verify schema count matches video count
  
  - [ ] 10.3 Add video schema settings
    - Add 'auto_video_schema_enabled' option (default: true)
    - Add settings UI toggle in Schema settings tab
    - _Requirements: 2.10_

- [ ] 11. Checkpoint - Verify video detection and schema generation
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Implement News_Sitemap_Generator
  - [ ] 12.1 Create News_Sitemap_Generator class with generate() method
    - Implement get_news_posts() to query posts from last 2 days
    - Filter posts by 'publish' status and exclude Googlebot-News noindex posts
    - Implement build_news_xml() to generate Google News compliant XML
    - Add news:news elements with publication, publication_date, title, and keywords
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_
  
  - [ ]* 12.2 Write property tests for news sitemap filtering
    - **Property 7: News Post Date Filtering**
    - **Validates: Requirements 3.2**
    - Generate posts with dates from -5 days to +1 day, verify only posts within 2 days included
    - **Property 8: News Post Noindex Exclusion**
    - **Validates: Requirements 3.6, 3.7**
    - Generate posts with/without noindex tag, verify exclusion logic
  
  - [ ]* 12.3 Write property test for news sitemap XML structure
    - **Property 9: News Sitemap XML Structure**
    - **Validates: Requirements 3.3, 3.4, 3.5**
    - Generate random posts, verify XML contains all required news:news elements
  
  - [ ] 12.4 Add publication name and language methods
    - Implement get_publication_name() with fallback to site name
    - Implement get_publication_language() with fallback to site language
    - _Requirements: 3.9_

- [ ] 13. Implement news sitemap URL routing and caching
  - [ ] 13.1 Add rewrite rule for /news-sitemap.xml
    - Register rewrite rule in init hook
    - Add 'meowseo_news_sitemap' query var
    - Implement template_redirect handler to serve XML
    - Set proper headers (Content-Type: application/xml, X-Robots-Tag: noindex)
    - _Requirements: 3.1_
  
  - [ ] 13.2 Add news sitemap caching
    - Cache generated XML for 5 minutes using transients
    - Invalidate cache on post publish/update via transition_post_status hook
    - _Requirements: 3.8_
  
  - [ ] 13.3 Add news sitemap to sitemap index
    - Extend Sitemap_Generator::build_index_xml() to include news sitemap
    - Add news sitemap entry with lastmod timestamp
    - _Requirements: 3.10_
  
  - [ ] 13.4 Add news sitemap settings
    - Add 'news_sitemap_publication_name' option (default: site name)
    - Add 'news_sitemap_language' option (default: site language)
    - Add settings UI in Sitemap settings tab
    - _Requirements: 3.9_

- [ ] 14. Checkpoint - Verify news sitemap generation
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 15. Implement Pattern_Processor for image SEO
  - [ ] 15.1 Create Pattern_Processor class with process() method
    - Implement variable substitution for %imagetitle%, %imagealt%, %sitename%
    - Implement sanitize_output() to strip HTML and limit length to 125 characters
    - Implement get_available_variables() to return variable descriptions
    - _Requirements: 4.2, 4.3, 4.4_
  
  - [ ]* 15.2 Write property test for pattern processing
    - **Property 10: Pattern-Based Alt Text Generation**
    - **Validates: Requirements 4.2, 4.3, 4.4**
    - Generate random patterns and image data, verify variable substitution

- [ ] 16. Implement Image_SEO_Handler
  - [ ] 16.1 Create Image_SEO_Handler class with filter_image_attributes() method
    - Hook into wp_get_attachment_image_attributes filter
    - Implement generate_alt_text() using Pattern_Processor
    - Check is_enabled() and should_override_existing() before modifying attributes
    - Apply alt text to images in post content, featured images, and gallery blocks
    - _Requirements: 4.1, 4.5, 4.9_
  
  - [ ]* 16.2 Write property test for alt text preservation
    - **Property 11: Alt Text Preservation**
    - **Validates: Requirements 4.10**
    - Generate images with existing alt text, verify preservation when override is disabled
  
  - [ ] 16.3 Add image SEO settings
    - Add 'image_seo_enabled' option (default: false)
    - Add 'image_seo_alt_pattern' option (default: '%imagetitle%')
    - Add 'image_seo_override_existing' option (default: false)
    - Add settings UI in Advanced tab with pattern variable reference
    - _Requirements: 4.6, 4.7, 4.8, 4.10_

- [ ] 17. Checkpoint - Verify image SEO automation
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Implement Submission_Queue for IndexNow
  - [ ] 18.1 Create Submission_Queue class with add() and process() methods
    - Implement add() to add URLs to queue (avoid duplicates)
    - Implement process() to submit batches of up to 10 URLs
    - Implement should_throttle() to enforce 5-second minimum delay
    - Store queue in 'meowseo_indexnow_queue' option
    - _Requirements: 5.7, 5.8_
  
  - [ ]* 18.2 Write property test for IndexNow throttling
    - **Property 14: IndexNow Request Throttling**
    - **Validates: Requirements 5.7, 5.8**
    - Submit multiple URLs rapidly, verify 5-second delays

- [ ] 19. Implement Submission_Logger for IndexNow
  - [ ] 19.1 Create Submission_Logger class with log() and get_history() methods
    - Implement log() to create log entries with timestamp, URLs, success status, and error
    - Store log entries in 'meowseo_indexnow_log' option
    - Keep only last 100 entries
    - Implement get_history() to retrieve log entries
    - _Requirements: 5.9_
  
  - [ ]* 19.2 Write property test for IndexNow logging
    - **Property 15: IndexNow Submission Logging**
    - **Validates: Requirements 5.9**
    - Submit URLs, verify log entries contain all required fields

- [ ] 20. Implement IndexNow_Client
  - [ ] 20.1 Create IndexNow_Client class with submit_url() and submit_urls() methods
    - Implement make_request() to POST to api.indexnow.org with host, key, and urlList
    - Implement get_api_key() to retrieve or generate API key
    - Implement generate_api_key() to create 32-character hexadecimal key
    - Handle API responses (200/202 = success, other = error)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
  
  - [ ]* 20.2 Write property tests for IndexNow API key handling
    - **Property 12: IndexNow API Key Inclusion**
    - **Validates: Requirements 5.3**
    - Generate random submission requests, verify API key is present
    - **Property 13: IndexNow API Key Generation**
    - **Validates: Requirements 5.5**
    - Test with no configured key, verify generation and storage
  
  - [ ] 20.3 Add retry logic with exponential backoff
    - Implement make_request_with_retry() to retry up to 3 times
    - Use exponential backoff delays (5s, 10s, 20s)
    - Log retry attempts in Submission_Logger
    - _Requirements: 5.11_
  
  - [ ]* 20.4 Write property test for IndexNow retry logic
    - **Property 16: IndexNow Retry with Exponential Backoff**
    - **Validates: Requirements 5.11**
    - Simulate API failures, verify 3 retries with exponential backoff

- [ ] 21. Integrate IndexNow with WordPress hooks
  - [ ] 21.1 Add post publish/update hook to IndexNow_Client
    - Hook into transition_post_status to queue URLs on publish/update
    - Check post type is public before queuing
    - Add URL to Submission_Queue instead of immediate submission
    - _Requirements: 5.1, 5.2_
  
  - [ ] 21.2 Set up WP-Cron for queue processing
    - Register custom cron interval (10 seconds) for queue processing
    - Schedule 'meowseo_process_indexnow_queue' event
    - Hook Submission_Queue::process() to cron event
    - _Requirements: 5.8_
  
  - [ ] 21.3 Add IndexNow settings and submission history UI
    - Add 'indexnow_enabled' option (default: false)
    - Add 'indexnow_api_key' option (auto-generated)
    - Add settings UI toggle in Advanced tab
    - Add submission history view in Tools page showing last 100 submissions
    - _Requirements: 5.4, 5.10, 5.12_

- [ ] 22. Checkpoint - Verify IndexNow integration
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 23. Implement Fix_Explanation_Provider
  - [ ] 23.1 Create Fix_Explanation_Provider class with get_explanation() method
    - Define explanation templates for all analyzer types
    - Implement replace_variables() to substitute context data into templates
    - Handle unknown analyzer IDs gracefully (return empty string)
    - Format explanations with issue and fix sections
    - _Requirements: 6.1, 6.2, 6.3_
  
  - [ ]* 23.2 Write property test for fix explanation presence
    - **Property 17: Fix Explanation Presence**
    - **Validates: Requirements 6.1**
    - Generate failing analyzer results, verify explanations are present
  
  - [ ] 23.3 Add explanation templates for title length checks
    - Add 'title_too_short' template with character count variables
    - Add 'title_too_long' template with character count variables
    - _Requirements: 6.4, 6.5_
  
  - [ ]* 23.4 Write property test for character count inclusion
    - **Property 18: Fix Explanation Character Count Inclusion**
    - **Validates: Requirements 6.4, 6.5**
    - Generate title length failures, verify character counts in explanations
  
  - [ ] 23.5 Add explanation templates for keyword-related checks
    - Add 'keyword_missing_title' template
    - Add 'keyword_missing_first_paragraph' template
    - Add 'keyword_missing_headings' template
    - Add 'slug_not_optimized' template
    - _Requirements: 6.6, 6.7, 6.12, 6.14_
  
  - [ ]* 23.6 Write property test for keyword suggestions
    - **Property 19: Fix Explanation Keyword Suggestions**
    - **Validates: Requirements 6.6, 6.7, 6.12, 6.14**
    - Generate keyword-related failures, verify keyword appears in suggestions
  
  - [ ] 23.7 Add explanation templates for content and density checks
    - Add 'description_missing' template
    - Add 'content_too_short' template
    - Add 'keyword_density_low' template
    - Add 'keyword_density_high' template
    - Add 'images_missing_alt' template
    - _Requirements: 6.8, 6.9, 6.10, 6.11, 6.13_
  
  - [ ]* 23.8 Write property test for density range inclusion
    - **Property 20: Fix Explanation Density Range**
    - **Validates: Requirements 6.10, 6.11**
    - Generate density failures, verify range appears in explanations

- [ ] 24. Integrate Fix_Explanation_Provider with Analysis_Engine
  - [ ] 24.1 Extend Analysis_Engine to include fix explanations
    - Inject Fix_Explanation_Provider into Analysis_Engine constructor
    - Call get_explanation() for failing/warning analyzer results
    - Add 'fix_explanation' field to analyzer result array
    - Pass context data (keyword, lengths, counts, densities) to explanation provider
    - _Requirements: 6.1, 6.15_
  
  - [ ] 24.2 Update Gutenberg sidebar to display fix explanations
    - Extend AnalyzerResult component to render fix_explanation field
    - Add CSS styling for issue and fix sections
    - Display explanations below analyzer message
    - _Requirements: 6.15_

- [ ] 25. Add Gutenberg UI for new schema types
  - [ ] 25.1 Extend SchemaTabContent component with new schema type forms
    - Add form fields for Recipe schema (ingredients, instructions, nutrition, etc.)
    - Add form fields for Event schema (location, dates, organizer, offers, etc.)
    - Add form fields for VideoObject schema (thumbnailUrl, duration, etc.)
    - Add form fields for Course schema (provider, courseCode, etc.)
    - Add form fields for JobPosting schema (hiringOrganization, location, salary, etc.)
    - Add form fields for Book schema (author, isbn, publisher, etc.)
    - Add form fields for Person schema (jobTitle, description, sameAs, etc.)
    - _Requirements: 1.10_
  
  - [ ] 25.2 Add schema type selector dropdown
    - Update schema type dropdown to include all 7 new types
    - Show/hide form fields based on selected schema type
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

- [ ] 26. Final checkpoint - Integration testing and verification
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- Checkpoints ensure incremental validation at logical breakpoints
- Implementation uses PHP for backend and TypeScript for Gutenberg components
- All features follow MeowSEO's existing architectural patterns (module-based, options storage, WordPress hooks)
