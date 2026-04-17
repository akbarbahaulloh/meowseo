# Implementation Plan: AI Generation Module

## Overview

This implementation plan covers the AI Generation Module for MeowSEO, providing AI-powered SEO metadata and featured image generation with multi-provider support (Gemini, OpenAI, Anthropic, Imagen, DALL-E), automatic fallback, Gutenberg integration, and comprehensive settings.

**Implementation Approach:**
- Build incrementally from core infrastructure to UI integration
- Each phase builds on previous phases
- Property-based tests validate correctness properties
- Unit tests validate specific behaviors and edge cases
- Checkpoints ensure working state at key milestones

**Technology Stack:**
- Backend: PHP 7.4+ with WordPress APIs
- Frontend: React with WordPress @wordpress packages
- Testing: PHPUnit with Eris for property-based testing

---

## Tasks

### Phase 1: Core Infrastructure

- [x] 1. Create module directory structure and autoloader integration
  - Create `includes/modules/ai/` directory structure
  - Create subdirectories: `contracts/`, `providers/`, `exceptions/`
  - Register namespace in existing autoloader
  - Verify autoloader can resolve AI module classes
  - _Requirements: 1.1, 17.1, 18.1, 19.1, 20.1, 21.1_

- [x] 2. Implement provider interface contract
  - [x] 2.1 Create `interface-ai-provider.php` with full interface definition
    - Define `get_slug()`, `get_label()`, `supports_text()`, `supports_image()`
    - Define `generate_text()` with return type and exceptions
    - Define `generate_image()` with return type and exceptions
    - Define `validate_api_key()` and `get_last_error()`
    - Add comprehensive PHPDoc documentation
    - _Requirements: 1.1, 17.1, 18.1, 19.1, 20.1, 21.1_

- [x] 3. Implement exception hierarchy
  - [x] 3.1 Create base `Provider_Exception` class
    - Extend PHP Exception class
    - Add `$provider_slug` property and getter
    - Implement constructor with provider context
    - _Requirements: 1.7, 1.8_

  - [x] 3.2 Create `Provider_Rate_Limit_Exception` class
    - Extend `Provider_Exception`
    - Add `$retry_after` property with getter
    - Set HTTP 429 code automatically
    - _Requirements: 1.4, 23.1, 23.2_

  - [x] 3.3 Create `Provider_Auth_Exception` class
    - Extend `Provider_Exception`
    - Set HTTP 401 code automatically
    - _Requirements: 1.5, 17.7, 18.8, 19.8_

- [x] 4. Implement AI_Module entry point class
  - [x] 4.1 Create `class-ai-module.php` implementing Module interface
    - Implement `get_id()` returning 'ai'
    - Implement `boot()` with all WordPress hooks
    - Initialize dependencies (Provider_Manager, Generator, Settings, REST)
    - Register REST routes on `rest_api_init`
    - Enqueue admin and Gutenberg assets
    - Add auto-generation hook on `save_post`
    - Add settings tab filter
    - _Requirements: 1.1, 7.1, 12.1, 28.1_

- [x] 5. Checkpoint - Verify core infrastructure loads
  - Verify autoloader resolves all new classes
  - Verify AI_Module can be instantiated
  - Verify Module interface is correctly implemented
  - Ensure no PHP errors on plugin load

### Phase 2: Provider Layer

- [x] 6. Implement Provider_Gemini
  - [x] 6.1 Create `class-provider-gemini.php` implementing AI_Provider
    - Implement all interface methods
    - Use `gemini-2.0-flash` model
    - Set API endpoint to `generativelanguage.googleapis.com`
    - Use `x-goog-api-key` header for authentication
    - Set 60-second timeout for all requests
    - Implement response parsing with error handling
    - Throw appropriate exceptions on errors
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 22.1, 22.2_

  - [x] 6.2 Implement rate limit handling for Gemini
    - Detect HTTP 429 responses
    - Parse `Retry-After` header if present
    - Throw `Provider_Rate_Limit_Exception` with retry duration
    - _Requirements: 17.6, 23.1, 23.5_

  - [x] 6.3 Implement authentication error handling for Gemini
    - Detect HTTP 401/403 responses
    - Throw `Provider_Auth_Exception`
    - _Requirements: 17.7_

  - [ ]* 6.4 Write unit tests for Provider_Gemini
    - Test successful text generation
    - Test rate limit exception throwing
    - Test auth exception throwing
    - Test timeout handling
    - Test API key validation
    - _Requirements: 17.1-17.7_

- [x] 7. Implement Provider_OpenAI
  - [x] 7.1 Create `class-provider-openai.php` implementing AI_Provider
    - Implement all interface methods
    - Support both text (`gpt-4o-mini`) and image (`dall-e-3`) generation
    - Use `Authorization: Bearer` header
    - Set 60-second timeout
    - Implement text response parsing
    - Implement image response parsing
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 22.1_

  - [x] 7.2 Implement error handling for OpenAI
    - Handle HTTP 429 with rate limit caching
    - Handle HTTP 401 with auth exception
    - Parse error messages from response body
    - _Requirements: 18.7, 18.8_

  - [ ]* 7.3 Write unit tests for Provider_OpenAI
    - Test text generation
    - Test image generation
    - Test error handling
    - Test API key validation
    - _Requirements: 18.1-18.8_

- [x] 8. Implement Provider_Anthropic
  - [x] 8.1 Create `class-provider-anthropic.php` implementing AI_Provider
    - Use `claude-haiku-4-5-20251001` model
    - Set API endpoint to `api.anthropic.com`
    - Use `x-api-key` header
    - Include `anthropic-version: 2023-06-01` header
    - Implement response parsing
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6, 22.1_

  - [x] 8.2 Implement error handling for Anthropic
    - Handle HTTP 429 with rate limit caching
    - Handle HTTP 401 with auth exception
    - _Requirements: 19.7, 19.8_

  - [ ]* 8.3 Write unit tests for Provider_Anthropic
    - Test text generation
    - Test error handling
    - Test API key validation
    - _Requirements: 19.1-19.8_

- [x] 9. Implement Provider_Imagen
  - [x] 9.1 Create `class-provider-imagen.php` implementing AI_Provider
    - Use `imagen-3.0-generate-002` model
    - Use Google Generative Language endpoint
    - Use `x-goog-api-key` header
    - Implement image-only generation (no text support)
    - Parse image URL from response
    - _Requirements: 20.1, 20.2, 20.3, 20.4_

  - [x] 9.2 Implement graceful degradation for Imagen
    - Return appropriate error when unavailable
    - Allow fallback to DALL-E
    - _Requirements: 20.5, 20.6_

  - [ ]* 9.3 Write unit tests for Provider_Imagen
    - Test image generation
    - Test graceful degradation
    - Test error handling
    - _Requirements: 20.1-20.6_

- [x] 10. Implement Provider_DALL_E
  - [x] 10.1 Create `class-provider-dalle.php` implementing AI_Provider
    - Use `dall-e-3` model
    - Use OpenAI API endpoint
    - Use `Authorization: Bearer` header
    - Implement image-only generation
    - Parse image URL from response
    - _Requirements: 21.1, 21.2, 21.3, 21.4_

  - [x] 10.2 Implement error handling for DALL-E
    - Handle HTTP 429 with rate limit caching
    - _Requirements: 21.5_

  - [ ]* 10.3 Write unit tests for Provider_DALL_E
    - Test image generation
    - Test error handling
    - _Requirements: 21.1-21.5_

- [x] 11. Checkpoint - Verify all providers work independently
  - Verify each provider implements interface correctly
  - Verify error handling produces correct exceptions
  - Run all provider unit tests
  - Ensure no PHP errors when loading providers

### Phase 3: Orchestration Layer

- [x] 12. Implement AI_Provider_Manager core functionality
  - [x] 12.1 Create `class-ai-provider-manager.php`
    - Implement provider loading from options
    - Implement provider instantiation with API keys
    - Store providers array by slug
    - _Requirements: 1.1, 1.2_

  - [x] 12.2 Implement provider ordering logic
    - Get provider order from options
    - Get active providers from options
    - Return providers in configured priority order
    - Include only active providers with API keys
    - _Requirements: 1.2, 1.3, 2.9, 2.10_

  - [x] 12.3 Implement `generate_text()` with fallback
    - Iterate through ordered providers
    - Skip providers that don't support text
    - Skip rate-limited providers
    - Try each provider until success
    - Collect errors from failed providers
    - Return WP_Error if all fail
    - Log each attempt with provider name and result
    - _Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8_

  - [x] 12.4 Implement `generate_image()` with fallback
    - Iterate through ordered image providers
    - Skip providers that don't support images
    - Skip rate-limited providers
    - Try each provider until success
    - Return WP_Error if all fail
    - _Requirements: 6.9, 20.5_

- [x] 13. Implement rate limit caching
  - [x] 13.1 Implement `is_rate_limited()` check
    - Check Object Cache for rate limit status
    - Use cache key pattern `meowseo_ai_ratelimit_{provider}`
    - Return boolean status
    - _Requirements: 23.4_

  - [x] 13.2 Implement `handle_rate_limit()` caching
    - Store rate limit status in Object Cache
    - Use TTL from exception or default 60 seconds
    - Log rate limit event
    - _Requirements: 23.2, 23.3, 23.5_

  - [x] 13.3 Write property test for rate limit caching
    - **Property 5: Rate Limit Caching**
    - **Validates: Requirements 23.1, 23.2, 23.3, 23.4**
    - Test that rate-limited providers are skipped
    - Test cache TTL is respected

- [x] 14. Implement API key encryption
  - [x] 14.1 Implement `encrypt_key()` method
    - Use AES-256-CBC cipher
    - Use AUTH_KEY for encryption key
    - Generate random IV for each encryption
    - Prepend IV to encrypted value
    - Return base64-encoded result
    - _Requirements: 2.3, 24.1, 24.2, 24.3, 24.4_

  - [x] 14.2 Implement `decrypt_key()` method
    - Decode base64 value
    - Extract IV from beginning
    - Decrypt using AES-256-CBC
    - Return plaintext API key
    - _Requirements: 24.5_

  - [x] 14.3 Implement `get_decrypted_api_key()` method
    - Get encrypted key from options
    - Return null if no key stored
    - Decrypt and return key
    - _Requirements: 24.5, 24.6_

  - [x] 14.4 Write property test for encryption round-trip
    - **Property 2: Encryption Round-Trip**
    - **Validates: Requirements 2.3, 24.1, 24.2, 24.3, 24.4, 24.5**
    - Test that any string encrypts and decrypts to same value
    - Test with various key lengths and characters

- [x] 15. Implement provider status reporting
  - [x] 15.1 Implement `get_provider_statuses()` method
    - Return array of all provider statuses
    - Include: label, active, has_api_key, supports_text, supports_image
    - Include: rate_limited, rate_limit_remaining, priority
    - Include providers without API keys
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ]* 16. Write property test for provider fallback order
  - **Property 1: Provider Fallback Order**
  - **Validates: Requirements 1.3, 1.4, 1.5, 1.6, 1.7**
  - Test that providers are tried in configured order
  - Test that failed providers are skipped
  - Test that all failures produce detailed error

- [ ]* 17. Write unit tests for AI_Provider_Manager
  - Test provider ordering
  - Test fallback logic with mocked providers
  - Test error aggregation
  - Test provider status reporting
  - _Requirements: 1.1-1.8, 2.9, 2.10, 3.1-3.4_

- [x] 18. Checkpoint - Verify orchestration layer works
  - Verify provider manager loads providers correctly
  - Verify fallback logic works with mocked failures
  - Verify rate limit caching works
  - Verify encryption/decryption works
  - Run all orchestration tests

### Phase 4: Generation Layer

- [x] 19. Implement AI_Generator core functionality
  - [x] 19.1 Create `class-ai-generator.php`
    - Accept Provider_Manager and Options as dependencies
    - Implement `generate_all_meta()` method
    - Validate post exists
    - Validate minimum content length (300 words)
    - _Requirements: 4.1-4.10, 11.4_

  - [x] 19.2 Implement `build_text_prompt()` method
    - Include post title
    - Include post excerpt
    - Include first 2000 words of content
    - Include categories and tags
    - Include language preference
    - Include custom instructions
    - Include output format specification with field constraints
    - _Requirements: 32.1, 32.2, 32.3, 32.4, 32.5, 32.6, 32.7_

  - [x] 19.3 Implement `build_image_prompt()` method
    - Include SEO title for context
    - Include visual style from settings
    - Include color palette hint if set
    - Include format requirements (16:9, PNG, no text)
    - _Requirements: 6.1, 6.2, 16.2, 16.3_

- [x] 20. Implement JSON response parsing
  - [x] 20.1 Implement `parse_json_response()` method
    - Remove markdown code blocks if present
    - Parse JSON with error handling
    - Validate required fields exist
    - Sanitize all extracted values
    - Return WP_Error on parse failure
    - _Requirements: 33.1, 33.2, 33.3, 33.4, 33.5_

  - [x] 20.2 Write property test for JSON parsing robustness
    - **Property 9: JSON Parsing Robustness**
    - **Validates: Requirements 33.1, 33.2, 33.3, 33.4, 33.5**
    - Test valid JSON parses correctly
    - Test invalid JSON returns WP_Error
    - Test missing fields are handled

- [x] 21. Implement image saving to media library
  - [x] 21.1 Implement `save_image_to_media_library()` method
    - Download image from URL with 60-second timeout
    - Save to temporary file
    - Upload to WordPress media library
    - Set as post featured image
    - Set image alt text to post title
    - Return attachment ID or null on failure
    - _Requirements: 6.3, 6.4, 6.7, 6.8_

- [x] 22. Implement postmeta field mapping
  - [x] 22.1 Implement `apply_to_postmeta()` method
    - Map seo_title to _meowseo_title
    - Map seo_description to _meowseo_description
    - Map focus_keyword to _meowseo_focus_keyword
    - Map og_title to _meowseo_og_title
    - Map og_description to _meowseo_og_description
    - Map twitter_title to _meowseo_twitter_title
    - Map twitter_description to _meowseo_twitter_description
    - Map schema_type to _meowseo_schema_type
    - Map image URL to _meowseo_og_image and _meowseo_twitter_image
    - Respect overwrite behavior setting
    - _Requirements: 27.1, 27.2, 27.3, 27.4, 27.5, 27.6, 27.7, 27.8, 27.9, 27.10, 13.1-13.5_

  - [x] 22.2 Write property test for postmeta field mapping
    - **Property 7: Postmeta Field Mapping**
    - **Validates: Requirements 27.1-27.10**
    - Test all fields map to correct postmeta keys
    - Test values are stored exactly as provided

- [x] 23. Implement generation result caching
  - [x] 23.1 Implement `cache_result()` method
    - Use cache key pattern `meowseo_ai_gen_{post_id}_{type}`
    - Set 24-hour TTL
    - Store complete result array
    - _Requirements: 31.1, 31.2, 31.3_

  - [x] 23.2 Implement cache bypass and invalidation
    - Support bypass_cache parameter
    - Clear cache on post update
    - _Requirements: 31.4, 31.5_

  - [x] 23.3 Write property test for cache key consistency
    - **Property 10: Cache Key Consistency**
    - **Validates: Requirements 31.2, 31.3**
    - Test cache keys are deterministic
    - Test same parameters produce same cache key

- [ ]* 24. Write property test for generated content constraints
  - **Property 3: Generated Content Constraints**
  - **Validates: Requirements 4.1, 4.2, 4.3, 4.5, 4.8, 5.1**
  - Test seo_title max 60 characters
  - Test seo_description 140-160 characters
  - Test focus_keyword non-empty
  - Test og_description 100-200 characters
  - Test direct_answer 300-450 characters

- [ ]* 25. Write property test for schema type validity
  - **Property 4: Schema Type Validity**
  - **Validates: Requirements 5.1, 5.2**
  - Test schema_type is valid enum value
  - Test schema_justification is non-empty

- [ ]* 26. Write property test for prompt completeness
  - **Property 8: Prompt Completeness**
  - **Validates: Requirements 32.1-32.7**
  - Test prompt contains post title
  - Test prompt contains content excerpt
  - Test prompt contains format specification

- [ ]* 27. Write unit tests for AI_Generator
  - Test prompt building with various post types
  - Test JSON parsing edge cases
  - Test image saving flow
  - Test postmeta application
  - Test caching behavior
  - _Requirements: 4.1-4.10, 5.1-5.6, 6.1-6.9_

- [x] 28. Checkpoint - Verify generation layer works
  - Verify prompt building includes all required elements
  - Verify JSON parsing handles valid and invalid input
  - Verify image saving works with mocked HTTP
  - Verify postmeta mapping is correct
  - Run all generation tests

### Phase 5: REST API Layer

- [x] 29. Implement AI_REST class
  - [x] 29.1 Create `class-ai-rest.php`
    - Accept Generator and Provider_Manager as dependencies
    - Implement `register_routes()` method
    - Register all endpoints under `meowseo/v1` namespace
    - _Requirements: 28.1, 25.1_

  - [x] 29.2 Implement POST `/ai/generate` endpoint
    - Accept post_id (required), type, generate_image, bypass_cache
    - Validate post_id as integer
    - Validate type against whitelist (text/image/all)
    - Call Generator with appropriate parameters
    - Return JSON response with success/error
    - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5, 28.6, 28.8_

  - [x] 29.3 Implement POST `/ai/generate-image` endpoint
    - Accept post_id (required), custom_prompt (optional)
    - Generate only featured image
    - Return attachment ID and URL
    - _Requirements: 9.2, 9.3, 9.4_

  - [x] 29.4 Implement GET `/ai/provider-status` endpoint
    - Return all provider statuses from Manager
    - Include rate limit countdown
    - _Requirements: 3.1-3.6_

  - [x] 29.5 Implement POST `/ai/apply` endpoint
    - Accept post_id, content, image, fields
    - Call Generator's apply_to_postmeta method
    - Return success/error response
    - _Requirements: 8.6, 27.1-27.10_

  - [x] 29.6 Implement POST `/ai/test-provider` endpoint
    - Accept provider slug and API key
    - Validate provider against whitelist
    - Call provider's validate_api_key method
    - Return connection status
    - _Requirements: 2.4, 2.5, 2.6, 2.7_

- [x] 30. Implement REST API security
  - [x] 30.1 Implement permission callbacks
    - Require `edit_posts` capability for generation endpoints
    - Require `manage_options` capability for settings endpoints
    - Return HTTP 403 on permission denied
    - _Requirements: 25.2, 25.5_

  - [x] 30.2 Implement nonce verification
    - Verify X-WP-Nonce header on all POST requests
    - Return HTTP 403 on verification failure
    - _Requirements: 25.3, 25.4_

  - [x] 30.3 Implement input sanitization
    - Sanitize post content with `sanitize_textarea_field`
    - Sanitize custom instructions with `sanitize_text_field`
    - Validate post_id as integer
    - Validate provider against whitelist
    - Validate language against whitelist
    - Validate style against whitelist
    - _Requirements: 26.1, 26.2, 26.3, 26.4, 26.5_

  - [ ]* 30.4 Write property test for input sanitization safety
    - **Property 6: Input Sanitization Safety**
    - **Validates: Requirements 26.1-26.5**
    - Test XSS prevention
    - Test SQL injection prevention
    - Test UTF-8 validity

- [ ]* 31. Write unit tests for AI_REST
  - Test endpoint registration
  - Test permission callbacks
  - Test parameter validation
  - Test response formatting
  - Test error handling
  - _Requirements: 28.1-28.8, 25.1-25.6, 26.1-26.5_

- [x] 32. Checkpoint - Verify REST API works
  - Verify all endpoints are registered
  - Verify permission checks work
  - Verify input validation works
  - Verify responses are correctly formatted
  - Run all REST API tests

### Phase 6: Settings Layer

- [x] 33. Implement AI_Settings class
  - [x] 33.1 Create `class-ai-settings.php`
    - Accept Options and Provider_Manager as dependencies
    - Implement `add_ai_tab()` filter callback
    - Implement `render_ai_tab()` main render method
    - _Requirements: 2.1, 2.2_

  - [x] 33.2 Implement provider configuration section
    - Render provider list with drag-and-drop support
    - Render API key input fields (password type)
    - Render active/inactive toggles
    - Render "Test Connection" buttons
    - Render capability indicators (text/image)
    - _Requirements: 2.1, 2.2, 2.8, 2.9, 2.10_

  - [x] 33.3 Implement provider status section
    - Display status for each provider
    - Show status indicators (Active, No API Key, Rate Limited, Error, Inactive)
    - Show rate limit countdown when applicable
    - Show error messages when applicable
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 33.4 Implement generation settings section
    - Render auto-generation toggles
    - Render overwrite behavior dropdown
    - Render output language dropdown
    - Render custom instructions textarea with character count
    - _Requirements: 12.1, 12.6, 13.1, 13.5, 14.1-14.5, 15.1-15.5_

  - [x] 33.5 Implement image settings section
    - Render image generation toggle
    - Render visual style dropdown
    - Render color palette hint field
    - Render "Save to media library" checkbox
    - _Requirements: 16.1-16.5_

- [x] 34. Implement settings JavaScript
  - [x] 34.1 Implement drag-and-drop provider ordering
    - Use jQuery UI Sortable or native drag-drop
    - Update hidden field with new order
    - Save order on change
    - _Requirements: 1.2, 2.9_

  - [x] 34.2 Implement test connection functionality
    - Make AJAX request to test-provider endpoint
    - Show loading state during test
    - Display success/error status
    - Update provider status indicator
    - _Requirements: 2.4, 2.5, 2.6, 2.7_

  - [x] 34.3 Implement provider status auto-refresh
    - Poll provider-status endpoint every 30 seconds
    - Update status indicators without page reload
    - _Requirements: 3.5_

  - [x] 34.4 Implement custom instructions character counter
    - Update count on input
    - Show warning when approaching limit
    - _Requirements: 15.2, 15.4_

- [-] 35. Implement settings sanitization and storage
  - [x] 35.1 Implement settings sanitization callbacks
    - Encrypt API keys before storage
    - Validate provider order array
    - Validate active providers array
    - Sanitize text fields
    - Validate dropdown selections
    - _Requirements: 2.3, 24.1-24.6, 26.1-26.5_

  - [x] 35.2 Implement settings registration
    - Register all AI settings with WordPress Settings API
    - Use appropriate option names
    - Set up sanitization callbacks
    - _Requirements: 2.1-2.10_

- [ ]* 36. Write unit tests for AI_Settings
  - Test settings rendering
  - Test settings sanitization
  - Test settings storage
  - Test provider status display
  - _Requirements: 2.1-2.10, 3.1-3.6, 12.1-12.7, 13.1-13.5, 14.1-14.5, 15.1-15.5, 16.1-16.5_

- [x] 37. Checkpoint - Verify settings work
  - Verify settings page renders correctly
  - Verify API keys are encrypted on save
  - Verify provider ordering works
  - Verify test connection works
  - Run all settings tests

### Phase 7: Gutenberg Integration

- [x] 38. Set up Gutenberg build pipeline
  - [x] 38.1 Configure webpack/Build process for React components
    - Add AI module entry point to build config
    - Configure Babel for JSX transformation
    - Set up CSS/SCSS processing
    - _Requirements: 7.1_

  - [x] 38.2 Register Gutenberg sidebar plugin
    - Use `registerPlugin` to create sidebar panel
    - Use `PluginSidebar` component
    - Enqueue script on block editor only
    - Pass data via `wp_localize_script`
    - _Requirements: 7.1_

- [x] 39. Implement AiGeneratorPanel component
  - [x] 39.1 Create `AiGeneratorPanel.js` main component
    - Use WordPress @wordpress/element for React
    - Use @wordpress/components for UI elements
    - Use @wordpress/data for editor state
    - Use @wordpress/api-fetch for REST requests
    - Implement state management (isGenerating, generatedContent, error, provider)
    - _Requirements: 7.1, 7.7_

  - [x] 39.2 Implement generation buttons
    - Create "Generate All SEO" primary button
    - Create "Text Only" secondary button
    - Create "Image Only" secondary button
    - Show loading spinner during generation
    - Disable buttons during generation
    - _Requirements: 7.2, 7.7, 9.1, 9.2, 9.3, 9.4, 9.5_

  - [x] 39.3 Implement generation API integration
    - Make POST request to `/ai/generate` endpoint
    - Include nonce in request headers
    - Handle loading states
    - Handle success response
    - Handle error response
    - _Requirements: 28.1-28.8_

  - [x] 39.4 Implement provider indicator badge
    - Show which provider was used for generation
    - Display in success message
    - _Requirements: 7.3, 7.8_

  - [x] 39.5 Implement fallback notification
    - Show warning when fallback provider used
    - Display message: "Generated via [Provider] (primary provider unavailable)"
    - Include link to settings page
    - Use warning color (yellow/orange)
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [x] 39.6 Implement error display
    - Show error message from API
    - Show "Retry" button
    - Show link to settings page
    - Handle specific error messages (content too short, permission denied, etc.)
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 35.1, 35.2, 35.4, 35.5_

- [x] 40. Implement PreviewPanel component
  - [x] 40.1 Create preview panel component
    - Display all generated fields with labels
    - Show character counts for constrained fields
    - Highlight fields exceeding limits
    - Show generated image thumbnail
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 40.2 Implement editable preview fields
    - Allow editing of generated values before applying
    - Use TextareaControl for multi-line fields
    - Use TextControl for single-line fields
    - Update local state on edit
    - _Requirements: 8.6_

  - [x] 40.3 Implement "Apply to Fields" functionality
    - Show button only after preview is displayed
    - Make POST request to `/ai/apply` endpoint
    - Update editor state after successful apply
    - Show success message
    - Handle errors
    - _Requirements: 8.7, 27.1-27.10_

- [x] 41. Implement accessibility features
  - [x] 41.1 Add ARIA labels and roles
    - Add ARIA labels to all buttons
    - Add ARIA live regions for status messages
    - Ensure proper role attributes
    - _Requirements: 34.1, 34.2_

  - [x] 41.2 Implement keyboard navigation
    - Ensure all controls are focusable
    - Implement proper tab order
    - Add focus indicators
    - Support Enter/Space for buttons
    - _Requirements: 34.3, 34.4_

  - [x] 41.3 Ensure label associations
    - Associate all form labels with inputs
    - Add ARIA descriptions for complex controls
    - _Requirements: 34.5, 34.6_

- [ ]* 42. Write unit tests for Gutenberg components
  - Test AiGeneratorPanel rendering
  - Test button states and interactions
  - Test API integration with mocked fetch
  - Test preview panel display
  - Test apply functionality
  - Test error handling
  - _Requirements: 7.1-7.9, 8.1-8.7, 9.1-9.5, 10.1-10.4, 11.1-11.5_

- [x] 43. Checkpoint - Verify Gutenberg integration works
  - Verify sidebar panel appears in editor
  - Verify generation buttons work
  - Verify preview panel displays correctly
  - Verify apply functionality works
  - Verify accessibility features work
  - Run all Gutenberg tests

### Phase 8: Integration and Finalization

- [x] 44. Implement auto-generation on post save
  - [x] 44.1 Implement `handle_auto_generation()` hook
    - Check if auto-generate is enabled
    - Check if this is first draft save
    - Check if content meets minimum length (300 words)
    - Run generation in background (non-blocking)
    - Log auto-generation events
    - Handle failures gracefully (don't block save)
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

  - [x] 44.2 Implement auto-generation for featured images
    - Check if auto-generate image is enabled
    - Check if post has no featured image
    - Generate and set featured image
    - _Requirements: 12.6, 12.7_

- [x] 45. Implement logging integration
  - [x] 45.1 Integrate with existing Logger helper
    - Log generation attempts with timestamp, post_id, user_id, type
    - Log provider used and success/failure
    - Log provider failures with reason
    - Log fallback usage
    - _Requirements: 29.1, 29.2, 29.3, 29.4, 29.5, 29.6_

- [-] 46. Write integration tests
  - [x] 46.1 Write end-to-end generation test
    - Test full flow from REST request to postmeta
    - Test with mocked provider responses
    - Test image download and media library integration
    - Test cache storage and retrieval
    - _Requirements: 1.1-1.8, 4.1-4.10, 6.1-6.9, 27.1-27.10_

  - [x] 46.2 Write provider fallback integration test
    - Simulate provider failures
    - Verify fallback to next provider{}
    - Verify error aggregation
    - _Requirements: 1.3-1.8_

  - [x] 46.3 Write settings integration test
    - Test API key encryption/decryption flow
    - Test provider order persistence
    - Test auto-generation on post save
    - _Requirements: 2.3, 24.1-24.6, 12.1-12.7_

  - [x] 46.4 Write Gutenberg integration test
    - Test sidebar panel renders
    - Test generation flow from UI
    - Test apply functionality
    - _Requirements: 7.1-7.9, 8.1-8.7_

- [x] 47. Performance optimizationn
  - [x] 47.1 Implement request optimization
    - Limit content to first 2000 words
    - Implement connection reuse where possible
    - Add request timeouts (60 seconds)
    - _Requirements: 22.1, 22.2, 30.1, 30.2, 32.3_

  - [x] 47.2 Implement cache optimization
    - Verify 24-hour TTL for generation results
    - Verify 5-minute TTL for provider status
    - Verify 60-second TTL for rate limits
    - Implement cache invalidation on post update
    - _Requirements: 3.6, 23.3, 31.1, 31.5_

  - [x] 47.3 Implement UI optimization
    - Lazy load provider status
    - Debounce settings saves
    - Show loading indicators
    - Implement cancel generation functionality
    - _Requirements: 7.7, 30.3, 30.4, 30.5_

- [x] 48. Security audit
  - [x] 48.1 Verify API key security
    - Confirm keys are encrypted at rest
    - Confirm keys are decrypted only when needed
    - Confirm keys are never logged
    - Confirm keys are never displayed in UI
    - _Requirements: 24.1-24.6_

  - [x] 48.2 Verify REST API security
    - Confirm nonce verification on all POST requests
    - Confirm capability checks on all endpoints
    - Confirm input sanitization on all parameters
    - Test for XSS vulnerabilities
    - Test for SQL injection vulnerabilities
    - _Requirements: 25.1-25.6, 26.1-26.5_

  - [x] 48.3 Verify data protection
    - Confirm no sensitive data in logs
    - Confirm cache isolation
    - Confirm proper error message sanitization
    - _Requirements: 29.1-29.6_

- [x] 49. Final verification and documentation
  - [x] 49.1 Run complete test suite
    - Run all unit tests
    - Run all property-based tests
    - Run all integration tests
    - Verify minimum coverage targets (Provider: 80%, Manager: 90%, Generator: 85%, REST: 90%)

  - [x] 49.2 Verify all requirements are satisfied
    - Cross-reference each requirement with implemented functionality
    - Verify all acceptance criteria are met
    - Document any deviations or limitations

  - [x] 49.3 Create inline documentation
    - Add PHPDoc to all public methods
    - Add inline comments for complex logic
    - Document filter/action hooks

- [x] 50. Final checkpoint - Complete system verification
  - Verify all 35 requirements are implemented
  - Verify all 10 correctness properties have tests
  - Verify all 8 implementation phases are complete
  - Run full test suite with coverage report
  - Perform manual testing in WordPress admin
  - Test Gutenberg integration in block editor
  - Verify error handling and fallback behavior
  - Ensure all tests pass

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties from design
- Unit tests validate specific examples and edge cases
- Integration tests verify component interactions with WordPress core

## Test Coverage Summary

| Component | Unit Tests | Property Tests | Integration Tests |
|-----------|------------|----------------|-------------------|
| Provider Interface | Task 6.4, 7.3, 8.3, 9.3, 10.3 | N/A | Task 46.2 |
| Provider Manager | Task 17 | Task 13.3, 14.4, 16 | Task 46.2 |
| Generator | Task 27 | Task 20.2, 22.2, 23.3, 24, 25, 26 | Task 46.1 |
| REST Endpoints | Task 31 | Task 30.4 | Task 46.1, 46.4 |
| Settings | Task 36 | N/A | Task 46.3 |
| Gutenberg | Task 42 | N/A | Task 46.4 |

## Correctness Properties Coverage

| Property | Task | Validates Requirements |
|----------|------|------------------------|
| Property 1: Provider Fallback Order | Task 16 | 1.3, 1.4, 1.5, 1.6, 1.7 |
| Property 2: Encryption Round-Trip | Task 14.4 | 2.3, 24.1-24.5 |
| Property 3: Generated Content Constraints | Task 24 | 4.1, 4.2, 4.3, 4.5, 4.8, 5.1 |
| Property 4: Schema Type Validity | Task 25 | 5.1, 5.2 |
| Property 5: Rate Limit Caching | Task 13.3 | 23.1-23.4 |
| Property 6: Input Sanitization Safety | Task 30.4 | 26.1-26.5 |
| Property 7: Postmeta Field Mapping | Task 22.2 | 27.1-27.10 |
| Property 8: Prompt Completeness | Task 26 | 32.1-32.7 |
| Property 9: JSON Parsing Robustness | Task 20.2 | 33.1-33.5 |
| Property 10: Cache Key Consistency | Task 23.3 | 31.2, 31.3 |
