# MeowSEO - API Documentation

## Table of Contents

1. [Overview](#overview)
2. [Import System](#import-system)
3. [Keyword Management](#keyword-management)
4. [Admin UI](#admin-ui)
5. [Meta Output](#meta-output)
6. [Analysis Engine](#analysis-engine)
7. [Utility Functions](#utility-functions)
8. [SEO Analyzers](#seo-analyzers)
9. [Readability Analyzers](#readability-analyzers)
10. [Web Worker API](#web-worker-api)
11. [React Hooks](#react-hooks)
12. [Redux Store](#redux-store)
13. [Type Definitions](#type-definitions)

---

## Overview

This document provides complete API reference for MeowSEO. All classes, functions, and methods are documented with parameters, return values, and usage examples.

### Module Structure

```
includes/
├── modules/
│   ├── import/                     # Import System
│   │   ├── class-import-manager.php
│   │   ├── class-batch-processor.php
│   │   └── importers/
│   │       ├── class-base-importer.php
│   │       └── class-yoast-importer.php
│   ├── keywords/                   # Keyword Management
│   │   ├── class-keyword-manager.php
│   │   └── class-keyword-analyzer.php
│   ├── admin/                      # Admin UI
│   │   └── class-list-table-columns.php
│   └── meta/                       # Meta Output
│       ├── class-meta-resolver.php
│       └── class-title-patterns.php
src/
├── analysis/                       # Analysis Engine
│   ├── analysis-engine.js
│   ├── analyzers/
│   │   ├── seo/
│   │   └── readability/
│   └── utils/
└── gutenberg/                      # Gutenberg Integration
    ├── workers/
    ├── hooks/
    └── store/
```

---

## Import System

The Import System migrates SEO data from competitor plugins (Yoast SEO, RankMath) to MeowSEO. It handles postmeta, termmeta, options, and redirects with batch processing to prevent timeouts.

### Import_Manager

**Namespace**: `MeowSEO\Modules\Import`

**Responsibility**: Orchestrates the import process, detects installed plugins, and manages import state.

#### `__construct( Options $options, Batch_Processor $processor )`

Creates a new Import_Manager instance.

**Parameters**:
- `$options` (Options): Options instance
- `$processor` (Batch_Processor): Batch processor instance

**Example**:
```php
use MeowSEO\Modules\Import\Import_Manager;
use MeowSEO\Modules\Import\Batch_Processor;
use MeowSEO\Options;

$options = new Options();
$processor = new Batch_Processor( 100 );
$manager = new Import_Manager( $options, $processor );
```

---

#### `register_importer( string $slug, Base_Importer $importer ): void`

Registers an importer for a specific plugin.

**Parameters**:
- `$slug` (string): Plugin slug (e.g., 'yoast', 'rankmath')
- `$importer` (Base_Importer): Importer instance

**Example**:
```php
$yoast_importer = new Yoast_Importer( $processor );
$manager->register_importer( 'yoast', $yoast_importer );
```

---

#### `detect_installed_plugins(): array`

Scans for installed competitor plugins by checking for option keys and plugin files.

**Returns**: `array` - Array of detected plugins
```php
[
    [
        'slug' => 'yoast',
        'name' => 'Yoast SEO',
        'installed' => true,
    ],
]
```

**Example**:
```php
$detected = $manager->detect_installed_plugins();
foreach ( $detected as $plugin ) {
    echo $plugin['name'] . ' is installed';
}
```

---

#### `start_import( string $plugin_slug ): array`

Starts an import process for a specific plugin.

**Parameters**:
- `$plugin_slug` (string): Plugin slug to import from

**Returns**: `array` - Import job data or error array
```php
[
    'import_id' => 'yoast_20240115_123456',
    'status' => 'pending',
    'plugin' => 'yoast',
    'started_at' => 1705324456,
    'progress' => [
        'posts' => ['processed' => 0, 'total' => 0],
        'terms' => ['processed' => 0, 'total' => 0],
        'options' => ['processed' => 0, 'total' => 0],
        'redirects' => ['processed' => 0, 'total' => 0],
    ],
]
```

**Example**:
```php
$job = $manager->start_import( 'yoast' );
if ( isset( $job['error'] ) ) {
    echo 'Error: ' . $job['message'];
} else {
    echo 'Import started: ' . $job['import_id'];
}
```

---

#### `get_import_status( string $import_id ): array`

Retrieves the current status of an import job.

**Parameters**:
- `$import_id` (string): Import ID

**Returns**: `array` - Import job data or error array

**Example**:
```php
$status = $manager->get_import_status( 'yoast_20240115_123456' );
echo 'Processed: ' . $status['progress']['posts']['processed'];
echo ' / ' . $status['progress']['posts']['total'];
```

---

#### `cancel_import( string $import_id ): bool`

Cancels an in-progress import.

**Parameters**:
- `$import_id` (string): Import ID

**Returns**: `bool` - True on success, false on failure

---

#### `update_progress( string $import_id, array $progress ): bool`

Updates import job progress data.

**Parameters**:
- `$import_id` (string): Import ID
- `$progress` (array): Progress data to merge

**Returns**: `bool` - True on success, false on failure

---

#### `complete_import( string $import_id, array $summary ): bool`

Marks an import as completed and updates summary.

**Parameters**:
- `$import_id` (string): Import ID
- `$summary` (array): Summary data

**Returns**: `bool` - True on success, false on failure

---

#### `log_error( string $import_id, array $error ): bool`

Logs an import error.

**Parameters**:
- `$import_id` (string): Import ID
- `$error` (array): Error data with keys: post_id, field, error

**Returns**: `bool` - True on success, false on failure

---

### Batch_Processor

**Namespace**: `MeowSEO\Modules\Import`

**Responsibility**: Processes large datasets in chunks to prevent PHP timeouts and memory exhaustion.

#### `__construct( int $batch_size = 100 )`

Creates a new Batch_Processor instance.

**Parameters**:
- `$batch_size` (int): Number of items per batch (default: 100)

**Example**:
```php
$processor = new Batch_Processor( 50 ); // Process 50 items at a time
```

---

#### `process_posts( callable $callback, array $args = [] ): array`

Processes posts in batches using WP_Query.

**Parameters**:
- `$callback` (callable): Function to process each post. Receives post ID, should return true/false
- `$args` (array): WP_Query arguments (optional)

**Returns**: `array` - Processing results
```php
[
    'processed' => 150,
    'total' => 500,
    'errors' => 3,
]
```

**Example**:
```php
$results = $processor->process_posts(
    function( $post_id ) {
        // Process post
        return update_post_meta( $post_id, '_meowseo_title', 'New Title' );
    },
    [ 'post_type' => 'post' ]
);
```

---

#### `process_terms( callable $callback, array $args = [] ): array`

Processes terms in batches using get_terms.

**Parameters**:
- `$callback` (callable): Function to process each term. Receives term ID, should return true/false
- `$args` (array): get_terms arguments (optional)

**Returns**: `array` - Processing results

**Example**:
```php
$results = $processor->process_terms(
    function( $term_id ) {
        // Process term
        return update_term_meta( $term_id, '_meowseo_title', 'New Title' );
    },
    [ 'taxonomy' => 'category' ]
);
```

---

#### `get_progress(): array`

Returns current progress data.

**Returns**: `array` - Progress with keys: processed, total, errors

---

### Base_Importer

**Namespace**: `MeowSEO\Modules\Import\Importers`

**Responsibility**: Abstract base class defining the import contract and shared logic.

#### Abstract Methods

##### `get_plugin_name(): string`

Returns the plugin name (e.g., "Yoast SEO").

##### `is_plugin_installed(): bool`

Checks if the plugin is installed by looking for option keys or files.

##### `get_postmeta_mappings(): array`

Returns postmeta field mappings.

**Returns**: `array`
```php
[
    'source_key' => 'meowseo_key',
]
```

##### `get_termmeta_mappings(): array`

Returns termmeta field mappings.

##### `get_options_mappings(): array`

Returns options field mappings.

**Returns**: `array`
```php
[
    'source_option' => [
        'source_key' => 'meowseo_key',
    ],
]
```

##### `import_redirects(): array`

Imports redirects from plugin-specific storage.

**Returns**: `array`
```php
[
    'imported' => 10,
    'errors' => 1,
]
```

---

#### Public Methods

##### `import_postmeta( array $post_ids = [] ): array`

Imports postmeta for all posts or specific post IDs.

**Parameters**:
- `$post_ids` (array): Optional array of post IDs

**Returns**: `array` - Import results

##### `import_termmeta( array $term_ids = [] ): array`

Imports termmeta for all terms or specific term IDs.

**Parameters**:
- `$term_ids` (array): Optional array of term IDs

**Returns**: `array` - Import results

##### `import_options(): array`

Imports plugin settings from options table.

**Returns**: `array` - Import results

---

#### Protected Methods

##### `validate_and_transform( string $key, mixed $value ): mixed`

Validates and transforms a value before storage.

**Parameters**:
- `$key` (string): MeowSEO meta key
- `$value` (mixed): Value to validate

**Returns**: `mixed` - Transformed value or false on failure

**Validation**:
- Checks UTF-8 encoding
- Sanitizes strings
- Handles empty values
- Plugin-specific transformations

---

### Yoast_Importer

**Namespace**: `MeowSEO\Modules\Import\Importers`

**Extends**: `Base_Importer`

**Responsibility**: Imports SEO data from Yoast SEO plugin.

#### Postmeta Mappings

```php
[
    '_yoast_wpseo_title' => '_meowseo_title',
    '_yoast_wpseo_metadesc' => '_meowseo_description',
    '_yoast_wpseo_focuskw' => '_meowseo_focus_keyword',
    '_yoast_wpseo_canonical' => '_meowseo_canonical_url',
    '_yoast_wpseo_meta-robots-noindex' => '_meowseo_robots_noindex',
    '_yoast_wpseo_meta-robots-nofollow' => '_meowseo_robots_nofollow',
    '_yoast_wpseo_opengraph-title' => '_meowseo_og_title',
    '_yoast_wpseo_opengraph-description' => '_meowseo_og_description',
    '_yoast_wpseo_twitter-title' => '_meowseo_twitter_title',
    '_yoast_wpseo_twitter-description' => '_meowseo_twitter_description',
]
```

#### Termmeta Mappings

```php
[
    '_wpseo_title' => '_meowseo_title',
    '_wpseo_desc' => '_meowseo_description',
]
```

#### Title Pattern Transformation

Yoast uses `%%variable%%` syntax, MeowSEO uses `{variable}` syntax.

**Variable Mappings**:
```php
[
    '%%title%%' => '{title}',
    '%%sitename%%' => '{site_name}',
    '%%sep%%' => '{sep}',
    '%%page%%' => '{page}',
    '%%category%%' => '{category}',
    '%%tag%%' => '{tag}',
    // ... and more
]
```

---

## Keyword Management

The Keyword Management system supports up to 5 focus keywords per post (1 primary + 4 secondary) with per-keyword analysis.

### Keyword_Manager

**Namespace**: `MeowSEO\Modules\Keywords`

**Responsibility**: Manages storage and retrieval of primary and secondary keywords.

#### Constants

- `MAX_KEYWORDS` = 5 (1 primary + 4 secondary)

---

#### `__construct( Options $options )`

Creates a new Keyword_Manager instance.

**Parameters**:
- `$options` (Options): Options instance

---

#### `get_keywords( int $post_id ): array`

Retrieves all keywords for a post.

**Parameters**:
- `$post_id` (int): Post ID

**Returns**: `array`
```php
[
    'primary' => 'wordpress seo',
    'secondary' => ['seo plugin', 'search optimization'],
]
```

**Example**:
```php
$keywords = $manager->get_keywords( 123 );
echo 'Primary: ' . $keywords['primary'];
echo 'Secondary: ' . implode( ', ', $keywords['secondary'] );
```

---

#### `set_primary_keyword( int $post_id, string $keyword ): bool`

Sets the primary keyword for a post.

**Parameters**:
- `$post_id` (int): Post ID
- `$keyword` (string): Keyword to set (empty string clears it)

**Returns**: `bool` - True on success, false on failure

**Example**:
```php
$manager->set_primary_keyword( 123, 'wordpress seo' );
```

---

#### `add_secondary_keyword( int $post_id, string $keyword ): bool|array`

Adds a secondary keyword.

**Parameters**:
- `$post_id` (int): Post ID
- `$keyword` (string): Keyword to add

**Returns**: `bool|array` - True on success, error array on failure

**Error Response**:
```php
[
    'error' => true,
    'message' => 'Maximum of 5 keywords allowed.',
]
```

**Example**:
```php
$result = $manager->add_secondary_keyword( 123, 'seo plugin' );
if ( is_array( $result ) && $result['error'] ) {
    echo 'Error: ' . $result['message'];
}
```

---

#### `remove_secondary_keyword( int $post_id, string $keyword ): bool`

Removes a secondary keyword.

**Parameters**:
- `$post_id` (int): Post ID
- `$keyword` (string): Keyword to remove

**Returns**: `bool` - True on success, false on failure

---

#### `reorder_secondary_keywords( int $post_id, array $keywords ): bool|array`

Reorders secondary keywords.

**Parameters**:
- `$post_id` (int): Post ID
- `$keywords` (array): Keywords in desired order

**Returns**: `bool|array` - True on success, error array on failure

---

#### `validate_keyword_count( int $post_id ): bool`

Validates that keyword count doesn't exceed maximum.

**Parameters**:
- `$post_id` (int): Post ID

**Returns**: `bool` - True if valid, false if exceeds maximum

---

#### `get_keyword_count( int $post_id ): int`

Returns total keyword count for a post.

**Parameters**:
- `$post_id` (int): Post ID

**Returns**: `int` - Total keyword count

---

### Keyword_Analyzer

**Namespace**: `MeowSEO\Modules\Keywords`

**Responsibility**: Runs keyword-based analysis checks for each focus keyword.

#### `__construct( Keyword_Manager $keyword_manager )`

Creates a new Keyword_Analyzer instance.

**Parameters**:
- `$keyword_manager` (Keyword_Manager): Keyword Manager instance

---

#### `analyze_all_keywords( int $post_id, string $content, array $context = [] ): array`

Analyzes all keywords for a post.

**Parameters**:
- `$post_id` (int): Post ID
- `$content` (string): Post content (HTML)
- `$context` (array): Additional context (title, description, slug)

**Returns**: `array` - Analysis results keyed by keyword
```php
[
    'wordpress seo' => [
        'density' => ['score' => 85, 'status' => 'good', 'value' => 1.2],
        'in_title' => ['score' => 100, 'status' => 'good'],
        'in_headings' => ['score' => 100, 'status' => 'good'],
        'in_slug' => ['score' => 100, 'status' => 'good'],
        'in_first_paragraph' => ['score' => 100, 'status' => 'good'],
        'in_meta_description' => ['score' => 100, 'status' => 'good'],
        'overall_score' => 97,
    ],
]
```

**Example**:
```php
$results = $analyzer->analyze_all_keywords(
    123,
    '<p>Your content...</p>',
    [
        'title' => 'WordPress SEO Guide',
        'description' => 'Learn WordPress SEO',
        'slug' => 'wordpress-seo-guide',
    ]
);
```

---

#### `analyze_single_keyword( string $keyword, string $content, array $context = [] ): array`

Analyzes a single keyword.

**Parameters**:
- `$keyword` (string): Focus keyword
- `$content` (string): Post content (HTML)
- `$context` (array): Additional context

**Returns**: `array` - Analysis result with individual check scores

**Analysis Checks**:
1. **Keyword Density** - Optimal range: 0.5% - 2.5%
2. **Keyword in Title** - Checks SEO title
3. **Keyword in Headings** - Checks H1-H6 tags
4. **Keyword in Slug** - Checks URL slug
5. **Keyword in First Paragraph** - Checks first 200 characters
6. **Keyword in Meta Description** - Checks meta description field

---

## Admin UI

### List_Table_Columns

**Namespace**: `MeowSEO\Modules\Admin`

**Responsibility**: Adds SEO Score column to WordPress admin list tables with sorting support.

#### `__construct( Options $options )`

Creates a new List_Table_Columns instance.

**Parameters**:
- `$options` (Options): Options instance

---

#### `register_hooks(): void`

Registers WordPress hooks for all public post types.

**Excluded Post Types**:
- attachment
- revision
- nav_menu_item

**Example**:
```php
$columns = new List_Table_Columns( $options );
$columns->register_hooks();
```

---

#### `add_seo_score_column( array $columns ): array`

Adds SEO Score column to list table.

**Parameters**:
- `$columns` (array): Existing columns

**Returns**: `array` - Modified columns

**Position**: After "Title" column

---

#### `render_seo_score_column( string $column_name, int $post_id ): void`

Renders SEO Score column content.

**Parameters**:
- `$column_name` (string): Column name
- `$post_id` (int): Post ID

**Output**: Colored indicator based on score range

**Score Ranges**:
- 0-40: Red (poor)
- 41-70: Orange (ok)
- 71-100: Green (good)
- null: Gray dash (no score)

**HTML Output**:
```html
<span class="meowseo-score-indicator meowseo-score-good" 
      title="SEO Score: 85/100" 
      aria-label="SEO Score: 85 out of 100">
    <span class="meowseo-score-circle"></span>
    <span class="meowseo-score-text">85</span>
</span>
```

---

#### `register_sortable_column( array $columns ): array`

Registers SEO Score as sortable column.

**Parameters**:
- `$columns` (array): Sortable columns

**Returns**: `array` - Modified sortable columns

---

#### `handle_seo_score_sorting( WP_Query $query ): void`

Modifies query to sort by SEO score.

**Parameters**:
- `$query` (WP_Query): WordPress query object

**Sorting**: By `_meowseo_seo_score` postmeta (numeric)

---

## Meta Output

### Meta_Resolver

**Namespace**: `MeowSEO\Modules\Meta`

**Responsibility**: Resolves meta field values with fallback chains and archive-specific logic.

#### Archive Type Constants

```php
const ARCHIVE_TYPE_AUTHOR = 'author_archive';
const ARCHIVE_TYPE_DATE = 'date_archive';
const ARCHIVE_TYPE_CATEGORY = 'category_archive';
const ARCHIVE_TYPE_TAG = 'tag_archive';
const ARCHIVE_TYPE_SEARCH = 'search_results';
const ARCHIVE_TYPE_ATTACHMENT = 'attachment';
```

---

#### `get_archive_robots( string $archive_type ): string`

Resolves robots meta tags for archive pages.

**Parameters**:
- `$archive_type` (string): Archive type constant

**Returns**: `string` - Robots directives (e.g., "noindex, follow")

**Resolution Logic**:
1. Check for term-specific override (for category/tag archives)
2. Fall back to global setting
3. Format as comma-separated string

**Example**:
```php
$robots = $resolver->get_archive_robots( Meta_Resolver::ARCHIVE_TYPE_AUTHOR );
// Returns: "noindex, follow"
```

**Global Settings**:
- `robots_author_archive`
- `robots_date_archive`
- `robots_category_archive`
- `robots_tag_archive`
- `robots_search_results`
- `robots_attachment`

**Term-Specific Override**:
For category/tag archives, checks termmeta:
- `_meowseo_robots_noindex`
- `_meowseo_robots_nofollow`

---

#### `resolve_robots_for_archive(): string`

Resolves robots directives for the current archive page.

**Returns**: `string` - Robots directives

**Example**:
```php
// On author archive page
$robots = $resolver->resolve_robots_for_archive();
// Returns: "noindex, follow" (based on global setting)
```

---

### Title_Patterns

**Namespace**: `MeowSEO\Modules\Meta`

**Responsibility**: Parses, validates, and resolves title patterns with variable substitution.

#### Supported Variables

```php
[
    'title', 'sep', 'site_name', 'tagline', 'page',
    'term_name', 'term_description', 'author_name',
    'current_year', 'current_month', 'category', 'tag',
    'term', 'date', 'name', 'searchphrase', 'posttype',
]
```

---

#### `__construct( Options $options )`

Creates a new Title_Patterns instance.

**Parameters**:
- `$options` (Options): Options instance

---

#### `resolve( string $pattern, array $context ): string`

Resolves pattern with context variables.

**Parameters**:
- `$pattern` (string): Pattern string with variables
- `$context` (array): Variable values

**Returns**: `string` - Resolved pattern

**Example**:
```php
$resolved = $patterns->resolve(
    '{title} {sep} {site_name}',
    [
        'title' => 'My Post',
        'site_name' => 'My Site',
    ]
);
// Returns: "My Post | My Site"
```

---

#### `parse( string $pattern ): array|object`

Parses pattern into structured representation.

**Parameters**:
- `$pattern` (string): Pattern string

**Returns**: `array|object` - Parsed structure or error object

**Parsed Structure**:
```php
[
    ['type' => 'literal', 'value' => 'Welcome to '],
    ['type' => 'variable', 'name' => 'site_name'],
]
```

**Error Object**:
```php
(object) [
    'error' => true,
    'message' => 'Unbalanced curly braces at position 5',
]
```

---

#### `validate( string $pattern ): bool|object`

Validates pattern syntax.

**Parameters**:
- `$pattern` (string): Pattern string

**Returns**: `bool|object` - True if valid, error object if invalid

**Example**:
```php
$result = $patterns->validate( '{title} {sep} {site_name}' );
if ( $result === true ) {
    echo 'Valid pattern';
} else {
    echo 'Error: ' . $result->message;
}
```

---

#### `get_pattern_for_post_type( string $post_type ): string`

Gets title pattern for a post type.

**Parameters**:
- `$post_type` (string): Post type

**Returns**: `string` - Pattern string

---

#### `get_pattern_for_archive_type( string $archive_type ): string`

Gets title pattern for an archive type.

**Parameters**:
- `$archive_type` (string): Archive type

**Returns**: `string` - Pattern string

**Archive Types**:
- `category_archive`
- `tag_archive`
- `author_page`
- `search_results`
- `date_archive`
- `404_page`
- `homepage`

---

#### `resolve_archive_variables(): array`

Builds context array with archive-specific variables.

**Returns**: `array` - Context with resolved variables

**Example**:
```php
// On category archive page
$context = $patterns->resolve_archive_variables();
// Returns: ['category' => 'News', 'term' => 'News']
```

**Resolved Variables**:
- `page_number` - For paginated archives
- `category` - Category name
- `tag` - Tag name
- `term` - Generic term name
- `name` - Author display name
- `searchphrase` - Search query
- `date` - Formatted archive date
- `posttype` - Post type label

---

#### `get_default_patterns(): array`

Returns default patterns for all page types.

**Returns**: `array` - Default patterns

**Default Patterns**:
```php
[
    'post' => '{title} {sep} {site_name}',
    'page' => '{title} {sep} {site_name}',
    'homepage' => '{site_name} {sep} {tagline}',
    'category_archive' => '{category} Archives {sep} {site_name}',
    'tag_archive' => '{tag} Tag {sep} {site_name}',
    'author_page' => '{name} {sep} {site_name}',
    'search_results' => 'Search Results for {searchphrase} {sep} {site_name}',
    'date_archive' => '{date} Archives {sep} {site_name}',
    '404_page' => 'Page Not Found {sep} {site_name}',
]
```

---

## Analysis Engine

### `analyzeContent(data)`

Main analysis function that orchestrates all 16 analyzers.

**Module**: `src/analysis/analysis-engine.js`

**Parameters**:
- `data` (Object): Analysis input data
  - `content` (string): Post content (HTML)
  - `title` (string): SEO title
  - `description` (string): Meta description
  - `slug` (string): URL slug
  - `keyword` (string): Focus keyword
  - `directAnswer` (string): Direct Answer field
  - `schemaType` (string): Schema Type field

**Returns**: `Object`
```javascript
{
  seoResults: Array<AnalyzerResult>,
  readabilityResults: Array<AnalyzerResult>,
  seoScore: number,              // 0-100
  readabilityScore: number,      // 0-100
  wordCount: number,
  sentenceCount: number,
  paragraphCount: number,
  fleschScore: number,           // 0-100
  keywordDensity: number,        // 0-100 (percentage)
  analysisTimestamp: number      // Unix timestamp
}
```

**Example**:
```javascript
import { analyzeContent } from './analysis/analysis-engine.js';

const result = analyzeContent({
  content: '<p>Your content here...</p>',
  title: 'Your SEO Title',
  description: 'Your meta description',
  slug: 'your-url-slug',
  keyword: 'focus keyword',
  directAnswer: 'Direct answer text...',
  schemaType: 'Article'
});

console.log('SEO Score:', result.seoScore);
console.log('Readability Score:', result.readabilityScore);
```

---

## Utility Functions

### Indonesian Stemmer

#### `stemWord(word)`

Stems an Indonesian word by removing prefixes and suffixes.

**Module**: `src/analysis/utils/indonesian-stemmer.js`

**Parameters**:
- `word` (string): The word to stem

**Returns**: `string` - The stemmed base form

**Example**:
```javascript
import { stemWord } from './utils/indonesian-stemmer.js';

stemWord('membuat');    // → 'buat'
stemWord('dibuat');     // → 'buat'
stemWord('pembuatan');  // → 'buat'
stemWord('berjalan');   // → 'jalan'
```

**Supported Patterns**:
- Prefixes: me-, di-, ber-, ter-, pe-
- Suffixes: -an, -kan, -i, -nya
- Combinations: me-...-kan, di-...-i, etc.

---

### Sentence Splitter

#### `splitSentences(text)`

Splits text into sentences, handling Indonesian abbreviations.

**Module**: `src/analysis/utils/sentence-splitter.js`

**Parameters**:
- `text` (string): Text to split into sentences

**Returns**: `Array<string>` - Array of sentences

**Example**:
```javascript
import { splitSentences } from './utils/sentence-splitter.js';

const text = 'Dr. Ahmad adalah profesor. Dia mengajar di universitas.';
const sentences = splitSentences(text);
// → ['Dr. Ahmad adalah profesor.', 'Dia mengajar di universitas.']
```

**Preserved Abbreviations**:
- dr., prof., dll., dst., dsb., yg., dg.

---

### Syllable Counter

#### `countSyllables(word)`

Counts syllables in a word using Indonesian vowel patterns.

**Module**: `src/analysis/utils/syllable-counter.js`

**Parameters**:
- `word` (string): Word to count syllables in

**Returns**: `number` - Number of syllables

**Example**:
```javascript
import { countSyllables } from './utils/syllable-counter.js';

countSyllables('membuat');     // → 3 (mem-bu-at)
countSyllables('pendidikan');  // → 4 (pen-di-di-kan)
countSyllables('sekolah');     // → 3 (se-ko-lah)
```

**Algorithm**:
- Counts vowel groups: a, e, i, o, u, y
- Handles diphthongs: ai, au, ei, oi, ui, ey, oy

---

### HTML Parser

#### `parseHtml(html)`

Parses HTML content and extracts structured data.

**Module**: `src/analysis/utils/html-parser.js`

**Parameters**:
- `html` (string): HTML content to parse

**Returns**: `Object`
```javascript
{
  text: string,                    // Plain text content
  headings: Array<{
    level: number,                 // 2 or 3
    text: string,
    position: number               // Character position
  }>,
  images: Array<{
    src: string,
    alt: string
  }>,
  links: Array<{
    href: string,
    text: string,
    isInternal: boolean,
    hasNofollow: boolean
  }>,
  paragraphs: Array<{
    text: string,
    wordCount: number
  }>
}
```

**Example**:
```javascript
import { parseHtml } from './utils/html-parser.js';

const html = '<h2>Heading</h2><p>Content...</p>';
const parsed = parseHtml(html);

console.log('Headings:', parsed.headings.length);
console.log('Paragraphs:', parsed.paragraphs.length);
```

---

## SEO Analyzers

All SEO analyzers follow the same interface and return an `AnalyzerResult` object.

### Common Return Structure

```javascript
{
  id: string,                      // Unique analyzer ID
  type: 'good' | 'ok' | 'problem', // Status
  message: string,                 // User-facing message
  score: number,                   // 0, 50, or 100
  weight: number,                  // 0.0-1.0 (percentage)
  details: Object                  // Analyzer-specific data
}
```

---

### `analyzeKeywordInTitle(title, keyword)`

Checks if focus keyword appears in SEO title.

**Module**: `src/analysis/analyzers/seo/keyword-in-title.js`

**Parameters**:
- `title` (string): SEO title
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean,
  position: number  // -1 if not found
}
```

**Example**:
```javascript
import { analyzeKeywordInTitle } from './analyzers/seo/keyword-in-title.js';

const result = analyzeKeywordInTitle(
  'SEO Optimization Tips for Beginners',
  'seo optimization'
);

console.log(result.type);     // 'good'
console.log(result.score);    // 100
console.log(result.details.found);  // true
```

---

### `analyzeKeywordInDescription(description, keyword)`

Checks if focus keyword appears in meta description.

**Module**: `src/analysis/analyzers/seo/keyword-in-description.js`

**Parameters**:
- `description` (string): Meta description
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean
}
```

---

### `analyzeKeywordInFirstParagraph(content, keyword)`

Checks if focus keyword appears in first 100 words.

**Module**: `src/analysis/analyzers/seo/keyword-in-first-paragraph.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  found: boolean,
  firstParagraphWordCount: number
}
```

---

### `analyzeKeywordDensity(content, keyword)`

Calculates keyword density as percentage of total words.

**Module**: `src/analysis/analyzers/seo/keyword-density.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  density: number,        // Percentage (0-100)
  count: number,          // Keyword occurrences
  totalWords: number
}
```

**Scoring**:
- Good (100): 0.5% - 2.5%
- OK (50): 0.3% - 0.5% or 2.5% - 3.5%
- Problem (0): < 0.3% or > 3.5%

---

### `analyzeKeywordInHeadings(content, keyword)`

Checks if focus keyword appears in H2/H3 headings.

**Module**: `src/analysis/analyzers/seo/keyword-in-headings.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  headingCount: number,
  headingsWithKeyword: number,
  found: boolean
}
```

---

### `analyzeKeywordInSlug(slug, keyword)`

Checks if focus keyword appears in URL slug.

**Module**: `src/analysis/analyzers/seo/keyword-in-slug.js`

**Parameters**:
- `slug` (string): URL slug
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  keyword: string,
  slug: string,
  found: boolean
}
```

---

### `analyzeImageAlt(content, keyword)`

Analyzes image alt text coverage and keyword presence.

**Module**: `src/analysis/analyzers/seo/image-alt-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)
- `keyword` (string): Focus keyword

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalImages: number,
  withAlt: number,
  withKeyword: number,
  coverage: number  // Percentage with alt text
}
```

**Scoring**:
- Good (100): > 80% with alt text and keyword
- OK (50): > 50% with alt text
- Problem (0): < 50% with alt text

---

### `analyzeInternalLinks(content)`

Analyzes internal linking structure.

**Module**: `src/analysis/analyzers/seo/internal-links-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalLinks: number,
  descriptiveLinks: number,
  genericAnchors: Array<string>  // List of generic anchors found
}
```

**Scoring**:
- Good (100): > 3 descriptive internal links
- OK (50): 1-3 descriptive internal links
- Problem (0): < 1 or generic anchor text

---

### `analyzeOutboundLinks(content)`

Analyzes external linking.

**Module**: `src/analysis/analyzers/seo/outbound-links-analysis.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  totalLinks: number,
  withNofollow: number
}
```

---

### `analyzeContentLength(content)`

Analyzes total word count.

**Module**: `src/analysis/analyzers/seo/content-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  wordCount: number
}
```

**Scoring**:
- Good (100): 300-2500 words
- OK (50): 150-300 or 2500-5000 words
- Problem (0): < 150 or > 5000 words

---

### `analyzeDirectAnswer(directAnswer)`

Checks Direct Answer field presence and length.

**Module**: `src/analysis/analyzers/seo/direct-answer-presence.js`

**Parameters**:
- `directAnswer` (string): Direct Answer field value

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  characterCount: number,
  present: boolean
}
```

**Scoring**:
- Good (100): 300-450 characters
- OK (50): Present but outside range
- Problem (0): Missing

---

### `analyzeSchemaPresence(schemaType)`

Checks if schema markup is configured.

**Module**: `src/analysis/analyzers/seo/schema-presence.js`

**Parameters**:
- `schemaType` (string): Schema Type field value

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  schemaType: string,
  configured: boolean
}
```

---

## Readability Analyzers

### `analyzeSentenceLength(content)`

Analyzes average sentence length.

**Module**: `src/analysis/analyzers/readability/sentence-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageLength: number,
  sentenceCount: number,
  longSentences: number  // Count of sentences > 25 words
}
```

**Scoring**:
- Good (100): < 20 words average
- OK (50): 20-25 words average
- Problem (0): > 25 words average

---

### `analyzeParagraphLength(content)`

Analyzes average paragraph length.

**Module**: `src/analysis/analyzers/readability/paragraph-length.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageLength: number,
  paragraphCount: number,
  longParagraphs: number  // Count of paragraphs > 200 words
}
```

**Scoring**:
- Good (100): < 150 words average
- OK (50): 150-200 words average
- Problem (0): > 200 words average

---

### `analyzePassiveVoice(content)`

Detects passive voice usage (Indonesian patterns).

**Module**: `src/analysis/analyzers/readability/passive-voice.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  passivePercentage: number,
  passiveSentences: number,
  totalSentences: number,
  examples: Array<string>  // Sample passive sentences
}
```

**Scoring**:
- Good (100): < 10% passive voice
- OK (50): 10-15% passive voice
- Problem (0): > 15% passive voice

**Detected Patterns**:
- di- prefix: dibuat, diambil
- ter- prefix: terbuat, terambil
- ke-an pattern: keadaan, kebakaran

---

### `analyzeTransitionWords(content)`

Analyzes transition word usage.

**Module**: `src/analysis/analyzers/readability/transition-words.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  transitionPercentage: number,
  sentencesWithTransitions: number,
  totalSentences: number,
  transitionsFound: Array<string>  // List of transitions used
}
```

**Scoring**:
- Good (100): > 30% sentences with transitions
- OK (50): 20-30% sentences with transitions
- Problem (0): < 20% sentences with transitions

---

### `analyzeSubheadingDistribution(content)`

Analyzes spacing between headings.

**Module**: `src/analysis/analyzers/readability/subheading-distribution.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  averageSpacing: number,  // Words between headings
  headingCount: number,
  sections: Array<{
    heading: string,
    wordCount: number
  }>
}
```

**Scoring**:
- Good (100): < 300 words between headings
- OK (50): 300-400 words between headings
- Problem (0): > 400 words between headings

---

### `analyzeFleschReadingEase(content)`

Calculates Flesch Reading Ease score (Indonesian adapted).

**Module**: `src/analysis/analyzers/readability/flesch-reading-ease.js`

**Parameters**:
- `content` (string): Post content (HTML)

**Returns**: `AnalyzerResult`

**Details Object**:
```javascript
{
  score: number,              // 0-100
  readabilityLevel: string,   // e.g., "Easy to read"
  sentenceCount: number,
  wordCount: number,
  syllableCount: number
}
```

**Scoring** (informational only, weight = 0%):
- Good (100): 60-100 (easy to read)
- OK (50): 40-60 (moderate difficulty)
- Problem (0): < 40 (difficult to read)

**Formula**:
```
206.835 - 1.015(words/sentences) - 0.684(syllables/words)
```

---

## Web Worker API

### Message Protocol

**Main Thread → Worker**:

```typescript
interface WorkerMessage {
  type: 'ANALYZE';
  payload: {
    content: string;
    title: string;
    description: string;
    slug: string;
    keyword: string;
    directAnswer: string;
    schemaType: string;
  };
}
```

**Worker → Main Thread**:

```typescript
interface WorkerResponse {
  type: 'ANALYSIS_COMPLETE';
  payload: {
    seoResults: Array<AnalyzerResult>;
    readabilityResults: Array<AnalyzerResult>;
    seoScore: number;
    readabilityScore: number;
    wordCount: number;
    sentenceCount: number;
    paragraphCount: number;
    fleschScore: number;
    keywordDensity: number;
    analysisTimestamp: number;
    error?: string;
  };
}
```

### Usage Example

```typescript
// Create worker
const worker = new Worker('./analysis-worker.ts', { type: 'module' });

// Send analysis request
worker.postMessage({
  type: 'ANALYZE',
  payload: {
    content: '<p>Your content...</p>',
    title: 'Your Title',
    description: 'Your description',
    slug: 'your-slug',
    keyword: 'your keyword',
    directAnswer: 'Direct answer...',
    schemaType: 'Article'
  }
});

// Listen for results
worker.addEventListener('message', (event) => {
  if (event.data.type === 'ANALYSIS_COMPLETE') {
    const results = event.data.payload;
    console.log('SEO Score:', results.seoScore);
    console.log('Readability Score:', results.readabilityScore);
  }
});

// Handle errors
worker.addEventListener('error', (error) => {
  console.error('Worker error:', error);
});
```

---

## React Hooks

### `useAnalysis()`

Main hook for triggering and managing content analysis.

**Module**: `src/gutenberg/hooks/useAnalysis.ts`

**Parameters**: None

**Returns**: `void` (updates Redux store)

**Usage**:
```typescript
import { useAnalysis } from './hooks/useAnalysis';

function Sidebar() {
  // Automatically triggers analysis on content changes
  useAnalysis();
  
  return <div>...</div>;
}
```

**Behavior**:
- Subscribes to contentSnapshot from Redux store
- Triggers analysis via Web Worker
- Updates Redux store with results
- Handles errors gracefully
- Cleans up on unmount

---

### `useContentSync()`

Syncs content from core/editor to meowseo/data store.

**Module**: `src/gutenberg/hooks/useContentSync.ts`

**Parameters**: None

**Returns**: `void` (updates Redux store)

**Usage**:
```typescript
import { useContentSync } from './hooks/useContentSync';

function Sidebar() {
  // Syncs content with 800ms debounce
  useContentSync();
  
  return <div>...</div>;
}
```

**Behavior**:
- Reads from core/editor store
- Applies 800ms debounce
- Dispatches to meowseo/data store
- Cleans up timeout on unmount

---

### `useEntityPropBinding(key)`

Binds to WordPress postmeta field.

**Module**: `src/gutenberg/hooks/useEntityPropBinding.ts`

**Parameters**:
- `key` (string): Postmeta key (e.g., '_meowseo_focus_keyword')

**Returns**: `[string, (value: string) => void]`

**Usage**:
```typescript
import { useEntityPropBinding } from './hooks/useEntityPropBinding';

function FocusKeywordInput() {
  const [keyword, setKeyword] = useEntityPropBinding('_meowseo_focus_keyword');
  
  return (
    <TextControl
      value={keyword}
      onChange={setKeyword}
    />
  );
}
```

---

## Redux Store

### Store Name

`'meowseo/data'`

### State Structure

```typescript
interface MeowSEOState {
  seoScore: number;
  readabilityScore: number;
  analysisResults: Array<AnalyzerResult>;
  readabilityResults: Array<AnalyzerResult>;
  wordCount: number;
  sentenceCount: number;
  paragraphCount: number;
  fleschScore: number;
  keywordDensity: number;
  analysisTimestamp: number | null;
  activeTab: 'general' | 'social' | 'schema' | 'advanced';
  isAnalyzing: boolean;
  contentSnapshot: ContentSnapshot;
}
```

### Actions

#### `setAnalysisResults(...)`

Updates analysis results in store.

**Parameters**:
```typescript
setAnalysisResults(
  seoResults: Array<AnalyzerResult>,
  readabilityResults: Array<AnalyzerResult>,
  seoScore: number,
  readabilityScore: number,
  wordCount: number,
  sentenceCount: number,
  paragraphCount: number,
  fleschScore: number,
  keywordDensity: number,
  analysisTimestamp: number
)
```

**Usage**:
```typescript
import { useDispatch } from '@wordpress/data';
import { setAnalysisResults } from './store/actions';

const dispatch = useDispatch('meowseo/data');

dispatch(setAnalysisResults(
  seoResults,
  readabilityResults,
  85,  // seoScore
  72,  // readabilityScore
  1250,  // wordCount
  45,  // sentenceCount
  8,  // paragraphCount
  68,  // fleschScore
  1.2,  // keywordDensity
  Date.now()
));
```

#### `setAnalyzing(isAnalyzing)`

Sets analyzing status.

**Parameters**:
- `isAnalyzing` (boolean): Whether analysis is in progress

**Usage**:
```typescript
dispatch(setAnalyzing(true));
```

#### `updateContentSnapshot(snapshot)`

Updates content snapshot.

**Parameters**:
- `snapshot` (ContentSnapshot): Content snapshot object

#### `setActiveTab(tab)`

Changes active tab.

**Parameters**:
- `tab` (string): Tab name ('general', 'social', 'schema', 'advanced')

### Selectors

#### `getSeoScore()`

Returns SEO score (0-100).

**Usage**:
```typescript
import { useSelect } from '@wordpress/data';

const seoScore = useSelect(
  (select) => select('meowseo/data').getSeoScore(),
  []
);
```

#### `getReadabilityScore()`

Returns readability score (0-100).

#### `getAnalysisResults()`

Returns SEO analyzer results array.

#### `getReadabilityResults()`

Returns readability analyzer results array.

#### `getWordCount()`

Returns word count.

#### `getSentenceCount()`

Returns sentence count.

#### `getParagraphCount()`

Returns paragraph count.

#### `getFleschScore()`

Returns Flesch Reading Ease score.

#### `getKeywordDensity()`

Returns keyword density percentage.

#### `getAnalysisTimestamp()`

Returns analysis timestamp.

#### `getIsAnalyzing()`

Returns analyzing status (boolean).

#### `getContentSnapshot()`

Returns content snapshot object.

#### `getActiveTab()`

Returns active tab name.

---

## Type Definitions

### `AnalyzerResult`

```typescript
interface AnalyzerResult {
  id: string;
  type: 'good' | 'ok' | 'problem';
  message: string;
  score: number;
  weight: number;
  details?: Record<string, any>;
}
```

### `ContentSnapshot`

```typescript
interface ContentSnapshot {
  title: string;
  content: string;
  excerpt: string;
  focusKeyword: string;
  permalink: string;
  postType: string;
}
```

### `AnalysisPayload`

```typescript
interface AnalysisPayload {
  content: string;
  title: string;
  description: string;
  slug: string;
  keyword: string;
  directAnswer: string;
  schemaType: string;
}
```

### `AnalysisResults`

```typescript
interface AnalysisResults {
  seoResults: Array<AnalyzerResult>;
  readabilityResults: Array<AnalyzerResult>;
  seoScore: number;
  readabilityScore: number;
  wordCount: number;
  sentenceCount: number;
  paragraphCount: number;
  fleschScore: number;
  keywordDensity: number;
  analysisTimestamp: number;
  error?: string;
}
```

---

## Error Handling

### Analyzer Errors

All analyzers include try-catch blocks:

```javascript
try {
  results.push(analyzeFeature(data));
} catch (error) {
  results.push({
    id: 'feature-name',
    type: 'problem',
    message: 'Error analyzing feature',
    score: 0,
    weight: 0.08,
    details: { error: error.message }
  });
}
```

### Web Worker Errors

Worker errors return fallback results:

```typescript
worker.addEventListener('error', (error) => {
  console.error('Worker error:', error);
  
  // Return fallback scores
  dispatch(setAnalysisResults(
    [], [], 0, 0, 0, 0, 0, 0, 0, Date.now()
  ));
});
```

### Redux Errors

Redux update failures are logged and skipped:

```typescript
try {
  dispatch(setAnalysisResults(...));
} catch (error) {
  console.error('Failed to dispatch results:', error);
  // Continue without blocking
}
```

---

## Performance Metrics

### Target Metrics

- **Analysis Time**: 1-2 seconds from debounce trigger
- **UI Blocking**: 0ms (runs in Web Worker)
- **Memory Usage**: < 50MB for analysis
- **Debounce Delay**: 800ms

### Monitoring

```javascript
// Add performance markers
performance.mark('analysis-start');
const result = analyzeContent(data);
performance.mark('analysis-end');

performance.measure('analysis', 'analysis-start', 'analysis-end');
const measure = performance.getEntriesByName('analysis')[0];
console.log(`Analysis took ${measure.duration}ms`);
```

---

## Version History

### v1.0.0 (Current)

- Initial release
- 16 analyzers (11 SEO + 5 Readability)
- Web Worker architecture
- Indonesian language support
- Redux store integration
- React hooks and components

---

## Additional Resources

- [Developer Guide](./DEVELOPER_GUIDE.md)
- [User Guide](./USER_GUIDE.md)
- [Requirements Document](../.kiro/specs/readability-keyword-analysis-engine/requirements.md)
- [Design Document](../.kiro/specs/readability-keyword-analysis-engine/design.md)
