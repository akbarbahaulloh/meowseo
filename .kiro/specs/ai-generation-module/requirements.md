# Requirements Document: AI Generation Module

## Introduction

This document specifies the requirements for the AI Generation Module for the MeowSEO WordPress plugin. The module uses large language models to automatically generate SEO metadata and featured images based on article content. It supports multiple AI providers with automatic fallback, integrates with the Gutenberg editor, and provides comprehensive settings for configuration and control.

## Glossary

- **AI_Generation_Module**: The core module providing AI-powered SEO metadata and image generation
- **Provider**: An external AI service (Gemini, OpenAI, Anthropic, Imagen, DALL-E)
- **Provider_Priority**: The ordered list of providers to attempt, with automatic fallback on failure
- **Fallback**: Automatic attempt to use the next provider when the current provider fails
- **SEO_Metadata**: Generated fields including title, description, keywords, schema type, and social tags
- **Featured_Image**: AI-generated image saved to WordPress media library and set as post featured image
- **Gutenberg_Sidebar**: The editor sidebar panel in the WordPress block editor
- **Settings_Page**: The configuration interface under MeowSEO > Settings > AI
- **API_Key**: Encrypted credential for authenticating with AI providers
- **Rate_Limit**: HTTP 429 response indicating provider quota exceeded
- **Timeout**: Request exceeding 60 seconds without response
- **Nonce**: WordPress security token for verifying request authenticity
- **Postmeta**: WordPress post metadata stored in wp_postmeta table
- **OG_Image**: Open Graph image for social media sharing
- **Twitter_Image**: Twitter Card image for Twitter sharing
- **Schema_Type**: Structured data type (Article, FAQPage, HowTo, LocalBusiness, Product)
- **Direct_Answer**: Concise answer for Google AI Overviews (formerly SGE)
- **Slug_Suggestion**: SEO-friendly URL path for the post

## Requirements

### Requirement 1: Multi-Provider Architecture

**User Story:** As a site administrator, I want to configure multiple AI providers with automatic fallback, so that content generation continues even if one provider fails.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL support Gemini, OpenAI, Anthropic Claude, Imagen, and DALL-E as providers
2. THE AI_Generation_Module SHALL allow configuration of provider priority order via drag-and-drop in settings
3. THE AI_Generation_Module SHALL attempt providers in priority order until one succeeds
4. WHEN a provider fails with HTTP 429 (rate limit), THE AI_Generation_Module SHALL skip to the next provider
5. WHEN a provider fails with invalid API key, THE AI_Generation_Module SHALL skip to the next provider
6. WHEN a provider request times out after 60 seconds, THE AI_Generation_Module SHALL skip to the next provider
7. WHEN all providers fail, THE AI_Generation_Module SHALL return an error message with details of each failure
8. THE AI_Generation_Module SHALL log each provider attempt with timestamp, provider name, and failure reason

### Requirement 2: Provider Configuration

**User Story:** As a site administrator, I want to configure API keys for each provider, so that the module can authenticate with AI services.

#### Acceptance Criteria

1. THE Settings_Page SHALL display an "AI Providers" section with configuration for each provider
2. THE Settings_Page SHALL provide a password-type input field for each provider's API key
3. THE Settings_Page SHALL encrypt API keys using AES-256-CBC with AUTH_KEY before storage
4. THE Settings_Page SHALL display a "Test Connection" button for each provider
5. WHEN a user clicks "Test Connection", THE Settings_Page SHALL verify the API key by making a test request
6. WHEN test connection succeeds, THE Settings_Page SHALL display "Connected" status with green indicator
7. WHEN test connection fails, THE Settings_Page SHALL display the error message and red indicator
8. THE Settings_Page SHALL provide an active/inactive toggle for each provider
9. THE Settings_Page SHALL allow drag-and-drop reordering of provider priority
10. THE Settings_Page SHALL display the current priority order as a numbered list

### Requirement 3: Provider Status Display

**User Story:** As a site administrator, I want to see real-time provider status, so that I know which providers are available.

#### Acceptance Criteria

1. THE Settings_Page SHALL display a "Provider Status" section showing status for each provider
2. THE Settings_Page SHALL display status as one of: Active, No API Key, Rate Limited, Error, Inactive
3. WHEN a provider is rate limited, THE Settings_Page SHALL display the time remaining until rate limit resets
4. WHEN a provider has an error, THE Settings_Page SHALL display the error message
5. THE Settings_Page SHALL update provider status every 30 seconds without page reload
6. THE Settings_Page SHALL cache provider status in Object Cache with 5-minute TTL

### Requirement 4: SEO Metadata Generation - Text Fields

**User Story:** As a content editor, I want AI to generate SEO metadata automatically, so that I can save time creating optimized titles and descriptions.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL generate seo_title with maximum 60 characters
2. THE AI_Generation_Module SHALL generate seo_description with 140-160 characters
3. THE AI_Generation_Module SHALL generate focus_keyword as a single primary keyword
4. THE AI_Generation_Module SHALL generate og_title as an engaging title (more engaging than seo_title)
5. THE AI_Generation_Module SHALL generate og_description with 100-200 characters
6. THE AI_Generation_Module SHALL generate twitter_title for Twitter Card display
7. THE AI_Generation_Module SHALL generate twitter_description as conversational text for Twitter
8. THE AI_Generation_Module SHALL generate direct_answer with 300-450 characters for Google AI Overviews
9. THE AI_Generation_Module SHALL generate slug_suggestion as SEO-friendly URL path
10. THE AI_Generation_Module SHALL generate secondary_keywords as 3-5 supporting keywords

### Requirement 5: Schema Type Generation

**User Story:** As a content editor, I want AI to recommend the appropriate schema type, so that my content gets the best rich results.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL generate schema_type from: Article, FAQPage, HowTo, LocalBusiness, Product
2. THE AI_Generation_Module SHALL generate schema_justification as one-sentence explanation for the choice
3. THE AI_Generation_Module SHALL analyze content structure to determine schema type
4. WHEN content contains Q&A format, THE AI_Generation_Module SHALL recommend FAQPage schema
5. WHEN content contains step-by-step instructions, THE AI_Generation_Module SHALL recommend HowTo schema
6. WHEN content is a standard article, THE AI_Generation_Module SHALL recommend Article schema

### Requirement 6: Featured Image Generation

**User Story:** As a content editor, I want AI to generate featured images automatically, so that my posts have visually appealing images without manual creation.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL generate featured images in PNG format
2. THE AI_Generation_Module SHALL generate images with 1200x630 pixel dimensions (16:9 ratio)
3. THE AI_Generation_Module SHALL save generated images to WordPress media library
4. THE AI_Generation_Module SHALL set generated image as post featured image
5. THE AI_Generation_Module SHALL store image URL in _meowseo_og_image postmeta field
6. THE AI_Generation_Module SHALL store image URL in _meowseo_twitter_image postmeta field
7. THE AI_Generation_Module SHALL set image alt text to post title
8. THE AI_Generation_Module SHALL set image title to post title
9. WHEN image generation fails for all providers, THE AI_Generation_Module SHALL continue without image

### Requirement 7: Gutenberg Sidebar Panel

**User Story:** As a content editor, I want a sidebar panel in the block editor to generate SEO content, so that I can optimize my posts without leaving the editor.

#### Acceptance Criteria

1. THE Gutenberg_Sidebar SHALL display a "Generate SEO" panel in the editor sidebar
2. THE Gutenberg_Sidebar SHALL display a "Generate All SEO" button with loading state
3. THE Gutenberg_Sidebar SHALL display a provider indicator badge showing which provider was used
4. THE Gutenberg_Sidebar SHALL display a preview panel showing generated content before applying
5. THE Gutenberg_Sidebar SHALL display an "Apply to Fields" button to confirm and save results
6. THE Gutenberg_Sidebar SHALL display partial generation options: "Text Only" and "Image Only"
7. THE Gutenberg_Sidebar SHALL display a loading spinner during generation
8. WHEN generation completes, THE Gutenberg_Sidebar SHALL display success message with provider name
9. WHEN generation fails, THE Gutenberg_Sidebar SHALL display error message with fallback notification

### Requirement 8: Preview Panel

**User Story:** As a content editor, I want to preview generated content before applying, so that I can review and approve changes.

#### Acceptance Criteria

1. THE Gutenberg_Sidebar SHALL display all generated fields in a preview panel
2. THE Gutenberg_Sidebar SHALL show field labels and generated values
3. THE Gutenberg_Sidebar SHALL display character counts for title and description fields
4. THE Gutenberg_Sidebar SHALL highlight fields that exceed recommended character limits
5. THE Gutenberg_Sidebar SHALL display generated image thumbnail in preview
6. THE Gutenberg_Sidebar SHALL allow user to edit preview values before applying
7. THE Gutenberg_Sidebar SHALL display "Apply to Fields" button only after preview is shown

### Requirement 9: Partial Generation Options

**User Story:** As a content editor, I want to generate only text or only images, so that I can regenerate specific content types.

#### Acceptance Criteria

1. THE Gutenberg_Sidebar SHALL provide "Text Only" button to generate only SEO metadata
2. THE Gutenberg_Sidebar SHALL provide "Image Only" button to generate only featured image
3. WHEN "Text Only" is clicked, THE AI_Generation_Module SHALL skip image generation
4. WHEN "Image Only" is clicked, THE AI_Generation_Module SHALL skip text generation
5. THE Gutenberg_Sidebar SHALL display appropriate loading message for each option

### Requirement 10: Fallback Notifications

**User Story:** As a content editor, I want to know when a fallback provider is used, so that I understand which service generated my content.

#### Acceptance Criteria

1. WHEN a fallback provider is used, THE Gutenberg_Sidebar SHALL display notification "Generated via [Provider Name] (primary provider unavailable)"
2. THE Gutenberg_Sidebar SHALL display the notification in a warning color (yellow/orange)
3. THE Gutenberg_Sidebar SHALL include a link to Settings page to configure providers
4. THE Gutenberg_Sidebar SHALL log fallback usage with timestamp and provider names

### Requirement 11: Error Messages

**User Story:** As a content editor, I want clear error messages when generation fails, so that I can troubleshoot issues.

#### Acceptance Criteria

1. WHEN all providers fail, THE Gutenberg_Sidebar SHALL display "Generation failed. Please check provider configuration."
2. THE Gutenberg_Sidebar SHALL display a link to "Settings" page
3. WHEN a specific provider fails, THE Gutenberg_Sidebar SHALL log the error with provider name and reason
4. WHEN content is too short, THE Gutenberg_Sidebar SHALL display "Content must be at least 300 words for generation"
5. WHEN user is not authenticated, THE Gutenberg_Sidebar SHALL display "You do not have permission to generate content"

### Requirement 12: Auto-Generation on Save

**User Story:** As a site administrator, I want to automatically generate SEO content on first draft save, so that new posts are optimized without manual action.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide toggle for "Auto-generate on first draft save"
2. WHEN toggle is enabled, THE AI_Generation_Module SHALL auto-generate on first save if content > 300 words
3. WHEN auto-generation is triggered, THE AI_Generation_Module SHALL run in background without blocking save
4. WHEN auto-generation completes, THE AI_Generation_Module SHALL update postmeta fields
5. WHEN auto-generation fails, THE AI_Generation_Module SHALL log error but not prevent post save
6. THE Settings_Page SHALL provide toggle for "Auto-generate featured image if missing"
7. WHEN toggle is enabled, THE AI_Generation_Module SHALL generate image if post has no featured image

### Requirement 13: Overwrite Settings

**User Story:** As a site administrator, I want to control whether generation overwrites existing metadata, so that I can protect manually-edited content.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide option "Overwrite existing metadata" with choices: Always, Never, Ask
2. WHEN set to "Always", THE AI_Generation_Module SHALL overwrite all existing fields
3. WHEN set to "Never", THE AI_Generation_Module SHALL skip fields that already have values
4. WHEN set to "Ask", THE Gutenberg_Sidebar SHALL display checkboxes for each field to overwrite
5. THE Settings_Page SHALL default to "Ask" for safety

### Requirement 14: Output Language Selection

**User Story:** As a site administrator, I want to control the language of generated content, so that it matches my site's language.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide "Output Language" dropdown with options: Auto-detect, Indonesian, English
2. WHEN set to "Auto-detect", THE AI_Generation_Module SHALL detect post language and generate in that language
3. WHEN set to "Indonesian", THE AI_Generation_Module SHALL generate all content in Indonesian
4. WHEN set to "English", THE AI_Generation_Module SHALL generate all content in English
5. THE Settings_Page SHALL default to "Auto-detect"

### Requirement 15: Custom Instructions

**User Story:** As a site administrator, I want to provide custom instructions to the AI, so that generated content matches my brand voice.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide a textarea for "Custom Instructions"
2. THE Settings_Page SHALL allow up to 500 characters of custom instructions
3. THE AI_Generation_Module SHALL include custom instructions in all generation prompts
4. THE Settings_Page SHALL display character count for custom instructions
5. THE Settings_Page SHALL provide example instructions

### Requirement 16: Image Generation Settings

**User Story:** As a site administrator, I want to control image generation style and appearance, so that generated images match my brand.

#### Acceptance Criteria

1. THE Settings_Page SHALL provide toggle to enable/disable image generation
2. THE Settings_Page SHALL provide "Visual Style" dropdown with options: Professional, Modern, Minimal, Illustrative, Photography-style
3. THE Settings_Page SHALL provide "Color Palette Hint" text field for color preferences
4. THE Settings_Page SHALL provide checkbox "Save to media library" (default: checked)
5. THE Settings_Page SHALL display preview of style examples

### Requirement 17: Gemini Provider Integration

**User Story:** As a developer, I want Gemini integration for text generation, so that the module can use Google's latest models.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL use gemini-2.0-flash model for text generation
2. THE AI_Generation_Module SHALL use generativelanguage.googleapis.com endpoint
3. THE AI_Generation_Module SHALL send requests with Content-Type: application/json
4. THE AI_Generation_Module SHALL include API key in x-goog-api-key header
5. THE AI_Generation_Module SHALL parse JSON response and extract generated text
6. WHEN Gemini returns HTTP 429, THE AI_Generation_Module SHALL cache rate limit status for 60 seconds
7. WHEN Gemini returns HTTP 401, THE AI_Generation_Module SHALL mark API key as invalid

### Requirement 18: OpenAI Provider Integration

**User Story:** As a developer, I want OpenAI integration for text and image generation, so that the module can use GPT and DALL-E.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL use gpt-4o-mini model for text generation (configurable)
2. THE AI_Generation_Module SHALL use dall-e-3 model for image generation
3. THE AI_Generation_Module SHALL use api.openai.com endpoint
4. THE AI_Generation_Module SHALL send requests with Authorization: Bearer {api_key} header
5. THE AI_Generation_Module SHALL send requests with Content-Type: application/json
6. THE AI_Generation_Module SHALL parse JSON response and extract generated text or image URL
7. WHEN OpenAI returns HTTP 429, THE AI_Generation_Module SHALL cache rate limit status for 60 seconds
8. WHEN OpenAI returns HTTP 401, THE AI_Generation_Module SHALL mark API key as invalid

### Requirement 19: Anthropic Provider Integration

**User Story:** As a developer, I want Anthropic integration for text generation, so that the module can use Claude models.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL use claude-haiku-4-5-20251001 model for text generation
2. THE AI_Generation_Module SHALL use api.anthropic.com endpoint
3. THE AI_Generation_Module SHALL send requests with x-api-key header
4. THE AI_Generation_Module SHALL send requests with Content-Type: application/json
5. THE AI_Generation_Module SHALL send requests with anthropic-version: 2023-06-01 header
6. THE AI_Generation_Module SHALL parse JSON response and extract generated text
7. WHEN Anthropic returns HTTP 429, THE AI_Generation_Module SHALL cache rate limit status for 60 seconds
8. WHEN Anthropic returns HTTP 401, THE AI_Generation_Module SHALL mark API key as invalid

### Requirement 20: Imagen Provider Integration

**User Story:** As a developer, I want Imagen integration for image generation, so that the module can use Google's image model.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL use imagen-3.0-generate-002 model for image generation
2. THE AI_Generation_Module SHALL use generativelanguage.googleapis.com endpoint
3. THE AI_Generation_Module SHALL send requests with x-goog-api-key header
4. THE AI_Generation_Module SHALL parse JSON response and extract image URL
5. WHEN Imagen is unavailable, THE AI_Generation_Module SHALL gracefully degrade to DALL-E
6. WHEN Imagen returns HTTP 429, THE AI_Generation_Module SHALL skip to next image provider

### Requirement 21: DALL-E Provider Integration

**User Story:** As a developer, I want DALL-E integration for image generation, so that the module has a fallback image provider.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL use dall-e-3 model for image generation
2. THE AI_Generation_Module SHALL use api.openai.com endpoint
3. THE AI_Generation_Module SHALL send requests with Authorization: Bearer {api_key} header
4. THE AI_Generation_Module SHALL parse JSON response and extract image URL
5. WHEN DALL-E returns HTTP 429, THE AI_Generation_Module SHALL cache rate limit status for 60 seconds

### Requirement 22: Request Timeout Handling

**User Story:** As a developer, I want proper timeout handling, so that slow requests don't block the editor.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL set 60-second timeout for all provider requests
2. WHEN a request times out, THE AI_Generation_Module SHALL skip to next provider
3. THE AI_Generation_Module SHALL log timeout with provider name and request type
4. THE AI_Generation_Module SHALL display "Request timed out" error to user

### Requirement 23: Rate Limit Handling

**User Story:** As a developer, I want rate limit detection and caching, so that the module doesn't repeatedly hit rate-limited providers.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL detect HTTP 429 responses from all providers
2. WHEN HTTP 429 is received, THE AI_Generation_Module SHALL cache rate limit status in Object Cache
3. THE AI_Generation_Module SHALL cache rate limit for 60 seconds by default
4. WHEN rate limit cache exists, THE AI_Generation_Module SHALL skip provider without attempting request
5. THE AI_Generation_Module SHALL parse Retry-After header if present and use that duration
6. THE Settings_Page SHALL display rate limit countdown for affected providers

### Requirement 24: API Key Encryption

**User Story:** As a security-conscious administrator, I want API keys encrypted, so that credentials are protected.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL encrypt API keys using AES-256-CBC cipher
2. THE AI_Generation_Module SHALL use AUTH_KEY constant for encryption key
3. THE AI_Generation_Module SHALL use random IV for each encryption
4. THE AI_Generation_Module SHALL store encrypted key with IV prepended
5. THE AI_Generation_Module SHALL decrypt API keys only when needed for requests
6. THE AI_Generation_Module SHALL never log or display decrypted API keys

### Requirement 25: REST Endpoint Security

**User Story:** As a security-conscious administrator, I want REST endpoints protected, so that only authorized users can generate content.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL register REST endpoints under meowseo/v1 namespace
2. THE AI_Generation_Module SHALL require edit_posts capability for all generation endpoints
3. THE AI_Generation_Module SHALL verify WordPress nonce for all POST requests
4. WHEN nonce verification fails, THE AI_Generation_Module SHALL return HTTP 403 status
5. WHEN capability check fails, THE AI_Generation_Module SHALL return HTTP 403 status
6. THE AI_Generation_Module SHALL sanitize all request parameters

### Requirement 26: Input Sanitization

**User Story:** As a security-conscious administrator, I want all inputs sanitized, so that XSS attacks are prevented.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL sanitize post content using sanitize_textarea_field
2. THE AI_Generation_Module SHALL sanitize custom instructions using sanitize_text_field
3. THE AI_Generation_Module SHALL validate post_id as integer
4. THE AI_Generation_Module SHALL validate language parameter against whitelist
5. THE AI_Generation_Module SHALL validate style parameter against whitelist

### Requirement 27: Postmeta Field Integration

**User Story:** As a developer, I want generated content stored in standard postmeta fields, so that it integrates with existing MeowSEO functionality.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL populate _meowseo_title postmeta field with seo_title
2. THE AI_Generation_Module SHALL populate _meowseo_description postmeta field with seo_description
3. THE AI_Generation_Module SHALL populate _meowseo_focus_keyword postmeta field with focus_keyword
4. THE AI_Generation_Module SHALL populate _meowseo_og_title postmeta field with og_title
5. THE AI_Generation_Module SHALL populate _meowseo_og_description postmeta field with og_description
6. THE AI_Generation_Module SHALL populate _meowseo_og_image postmeta field with image URL
7. THE AI_Generation_Module SHALL populate _meowseo_twitter_title postmeta field with twitter_title
8. THE AI_Generation_Module SHALL populate _meowseo_twitter_description postmeta field with twitter_description
9. THE AI_Generation_Module SHALL populate _meowseo_twitter_image postmeta field with image URL
10. THE AI_Generation_Module SHALL populate _meowseo_schema_type postmeta field with schema_type

### Requirement 28: REST Endpoint for Generation

**User Story:** As a developer, I want a REST endpoint to trigger generation, so that the Gutenberg sidebar can request content generation.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL register POST endpoint at /ai/generate under meowseo/v1 namespace
2. THE AI_Generation_Module SHALL accept parameters: post_id (integer), type (text|image|all)
3. THE AI_Generation_Module SHALL return generated content as JSON object
4. THE AI_Generation_Module SHALL return provider name used for generation
5. THE AI_Generation_Module SHALL return HTTP 200 on success
6. THE AI_Generation_Module SHALL return HTTP 400 for invalid parameters
7. THE AI_Generation_Module SHALL return HTTP 403 for permission denied
8. THE AI_Generation_Module SHALL return HTTP 500 for generation failure

### Requirement 29: Logging and Monitoring

**User Story:** As a site administrator, I want generation attempts logged, so that I can troubleshoot issues and monitor usage.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL log each generation attempt with timestamp, post_id, user_id, and type
2. THE AI_Generation_Module SHALL log provider used and success/failure status
3. THE AI_Generation_Module SHALL log each provider failure with reason (rate limit, timeout, invalid key, etc.)
4. THE AI_Generation_Module SHALL log fallback provider usage
5. THE AI_Generation_Module SHALL use Logger helper class for all logging
6. THE AI_Generation_Module SHALL store logs in Object Cache for UI display

### Requirement 30: Performance - Generation Speed

**User Story:** As a content editor, I want generation to complete quickly, so that my workflow is not interrupted.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL complete text generation within 30 seconds for typical articles
2. THE AI_Generation_Module SHALL complete image generation within 60 seconds
3. THE AI_Generation_Module SHALL display loading indicator during generation
4. THE AI_Generation_Module SHALL allow user to cancel generation
5. WHEN generation is cancelled, THE AI_Generation_Module SHALL stop all provider requests

### Requirement 31: Performance - Caching

**User Story:** As a developer, I want generation results cached, so that repeated requests don't hit providers unnecessarily.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL cache generation results in Object Cache with 24-hour TTL
2. THE AI_Generation_Module SHALL use cache key: meowseo_ai_gen_{post_id}_{type}
3. WHEN cached result exists, THE AI_Generation_Module SHALL return cached result without provider request
4. THE AI_Generation_Module SHALL provide option to bypass cache and regenerate
5. THE AI_Generation_Module SHALL clear cache when post is updated

### Requirement 32: Prompt Engineering

**User Story:** As a developer, I want optimized prompts, so that generated content is high quality and consistent.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL include post title in generation prompt
2. THE AI_Generation_Module SHALL include post excerpt in generation prompt
3. THE AI_Generation_Module SHALL include first 2000 words of post content in generation prompt
4. THE AI_Generation_Module SHALL include custom instructions in generation prompt
5. THE AI_Generation_Module SHALL include language preference in generation prompt
6. THE AI_Generation_Module SHALL include output format specifications in generation prompt
7. THE AI_Generation_Module SHALL include character limit specifications for each field

### Requirement 33: JSON Response Parsing

**User Story:** As a developer, I want robust JSON parsing, so that malformed responses don't crash the module.

#### Acceptance Criteria

1. THE AI_Generation_Module SHALL parse provider JSON responses with error handling
2. WHEN JSON parsing fails, THE AI_Generation_Module SHALL log error and skip to next provider
3. THE AI_Generation_Module SHALL validate response structure before extracting fields
4. THE AI_Generation_Module SHALL provide fallback values if fields are missing
5. THE AI_Generation_Module SHALL sanitize all extracted text values

### Requirement 34: Accessibility - WCAG 2.1 AA Compliance

**User Story:** As a user with disabilities, I want the generation interface to be accessible, so that I can use screen readers and keyboard navigation.

#### Acceptance Criteria

1. THE Gutenberg_Sidebar SHALL provide ARIA labels for all buttons
2. THE Gutenberg_Sidebar SHALL provide ARIA live regions for status messages
3. THE Gutenberg_Sidebar SHALL support full keyboard navigation
4. THE Gutenberg_Sidebar SHALL provide focus indicators for all focusable elements
5. THE Settings_Page SHALL associate all form labels with input fields
6. THE Settings_Page SHALL provide ARIA descriptions for complex settings

### Requirement 35: Error Recovery

**User Story:** As a content editor, I want the module to recover gracefully from errors, so that I can retry generation.

#### Acceptance Criteria

1. WHEN generation fails, THE Gutenberg_Sidebar SHALL display "Retry" button
2. WHEN user clicks "Retry", THE AI_Generation_Module SHALL attempt generation again
3. THE AI_Generation_Module SHALL not retry more than 3 times automatically
4. WHEN all retries fail, THE Gutenberg_Sidebar SHALL display detailed error message
5. THE Gutenberg_Sidebar SHALL provide link to Settings page for troubleshooting
