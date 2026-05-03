# MeowSEO Schema Generator Module

Advanced Schema.org markup generator with visual builder for WordPress.

## Features

- ✅ **10 Schema Types** - Article, Product, Recipe, Event, Local Business, FAQ, HowTo, Review, Video, Course
- ✅ **Variable System** - 30+ dynamic variables (%title%, %author%, etc.)
- ✅ **JSON-LD Output** - Automatic @graph structure
- ✅ **Global Entities** - Website, Organization, Breadcrumbs, WebPage
- ✅ **Shortcode Support** - `[meowseo_schema id="s-abc123"]`
- ✅ **Multi-Schema Support** - Multiple schemas per post/page
- ✅ **Extensible** - Easy to add custom schema types
- ✅ **Performance** - Static caching, efficient queries

## Installation

The module is automatically loaded by MeowSEO. No additional installation required.

## Usage

### 1. Programmatic Usage

#### Save a Schema

```php
use MeowSEO\Modules\Schema\Schema_DB;

$schema_data = [
    '@type' => 'Article',
    'headline' => 'My Article Title',
    'description' => 'Article description',
    'author' => [
        '@type' => 'Person',
        'name' => 'John Doe',
    ],
    'metadata' => [
        'title' => 'Article',
        'isPrimary' => true,
    ],
];

$meta_id = Schema_DB::save_schema( $post_id, $schema_data );
```

#### Get Schemas

```php
// Get all schemas for a post
$schemas = Schema_DB::get_schemas( $post_id );

// Get single schema
$schema = Schema_DB::get_schema( $post_id, 'schema-123' );

// Get by shortcode
$result = Schema_DB::get_schema_by_shortcode( 's-abc123' );
```

#### Delete Schema

```php
// Delete single schema
Schema_DB::delete_schema( $post_id, 'schema-123' );

// Delete all schemas
Schema_DB::delete_all_schemas( $post_id );
```

### 2. Variable Replacement

```php
use MeowSEO\Modules\Schema\Schema_Variables;

$post = get_post( 123 );

// Replace variables
$text = Schema_Variables::replace( '%title% by %author%', $post );
// Output: "My Post Title by John Doe"

// Date formatting
$date = Schema_Variables::replace( '%date(Y-m-d\TH:i:sP)%', $post );
// Output: "2026-05-03T20:41:00+07:00"

// Get available variables
$variables = Schema_Variables::get_available_variables();
```

### 3. Create Custom Schema Type

```php
use MeowSEO\Modules\Schema\Schema_Type;

class Custom_Schema extends Schema_Type {
    
    public function __construct() {
        $this->type = 'Custom';
        $this->label = __( 'Custom Schema', 'meowseo' );
        $this->description = __( 'A custom schema type', 'meowseo' );
        $this->icon = 'admin-generic';
    }
    
    public function get_fields(): array {
        return [
            'name' => [
                'type' => 'text',
                'label' => __( 'Name', 'meowseo' ),
                'default' => '%title%',
                'required' => true,
            ],
            'description' => [
                'type' => 'textarea',
                'label' => __( 'Description', 'meowseo' ),
                'default' => '%excerpt%',
            ],
        ];
    }
}

// Register the schema type
add_action( 'meowseo_schema_types_loaded', function() {
    $custom = new Custom_Schema();
    $custom->register();
});
```

### 4. Shortcode Usage

```
[meowseo_schema id="s-abc123"]
```

## Available Schema Types

### 1. Article
- Headline, description, author, publisher
- Date published/modified
- Article body, keywords, section
- Word count

### 2. Product
- Name, description, image, brand
- SKU, MPN, GTIN
- Offers (price, currency, availability)
- Aggregate rating, reviews

### 3. Recipe
- Name, description, image, author
- Prep time, cook time, total time
- Ingredients, instructions
- Nutrition information
- Aggregate rating, video

### 4. Event
- Name, description, image
- Start/end date, status, attendance mode
- Location (physical or virtual)
- Organizer, performers
- Ticket offers

### 5. Local Business
- 30+ business types (Restaurant, Store, Hotel, etc.)
- Name, description, logo, contact info
- Address, geo coordinates
- Opening hours, price range
- Aggregate rating, social profiles

### 6. FAQ
- Question and answer pairs
- Repeater field for multiple Q&A
- Automatic FAQPage schema
- Perfect for help pages

### 7. HowTo
- Step-by-step instructions
- Supplies and tools lists
- Time estimates (prep, perform, total)
- Images and videos per step

### 8. Review
- Item being reviewed (Product, Book, Movie, etc.)
- Rating with best/worst values
- Review body, author
- Positive and negative notes

### 9. Video
- Name, description, thumbnail
- Upload date, duration
- Content URL, embed URL
- Transcript, view count
- Video segments/chapters

### 10. Course
- Course name, description, provider
- Course code, educational level
- Course instances (schedule, instructor, location)
- Pricing (free, paid, subscription)
- Learning outcomes, prerequisites
- Ratings and reviews

## Field Types

- `text` - Single line text input
- `textarea` - Multi-line text input
- `number` - Numeric input
- `url` - URL input
- `email` - Email input
- `date` - Date picker
- `datetime` - Date and time picker
- `time` - Time picker
- `image` - Image selector
- `select` - Dropdown select
- `group` - Nested fields
- `repeater` - Repeatable fields
- `hidden` - Hidden field

## Variables

### Post Variables
- `%title%` - Post title
- `%excerpt%` - Post excerpt
- `%content%` - Post content (stripped)
- `%author%` - Post author name
- `%date%` - Post publish date
- `%date(format)%` - Custom date format
- `%modified%` - Post modified date
- `%modified(format)%` - Custom modified date
- `%permalink%` - Post URL
- `%featured_image%` - Featured image URL
- `%post_id%` - Post ID
- `%post_type%` - Post type

### Site Variables
- `%sitename%` - Site name
- `%sitedesc%` - Site description
- `%siteurl%` - Site URL
- `%currentdate%` - Current date
- `%currentyear%` - Current year
- `%sep%` - Separator (|)

### Term Variables
- `%name%` - Term name
- `%description%` - Term description
- `%term_id%` - Term ID
- `%taxonomy%` - Taxonomy name
- `%slug%` - Term slug

### Author Variables
- `%name%` - Author display name
- `%first_name%` - Author first name
- `%last_name%` - Author last name
- `%author_url%` - Author archive URL

## Hooks & Filters

### Actions

```php
// After schema types are loaded
do_action( 'meowseo_schema_types_loaded' );

// Before JSON-LD output
do_action( 'meowseo_schema_json_ld', $data, $jsonld );
```

### Filters

```php
// Modify schema variables
apply_filters( 'meowseo_schema_variables', $variables, $object );

// Modify schema defaults
apply_filters( 'meowseo_schema_defaults', $defaults, $type, $object );

// Modify generated JSON-LD
apply_filters( 'meowseo_schema_generate', $jsonld, $data, $type, $object );

// Modify Website schema
apply_filters( 'meowseo_schema_website', $schema );

// Modify Organization schema
apply_filters( 'meowseo_schema_organization', $schema );

// Modify Breadcrumb schema
apply_filters( 'meowseo_schema_breadcrumb', $schema );

// Modify WebPage schema
apply_filters( 'meowseo_schema_webpage', $schema );

// Control global entities
apply_filters( 'meowseo_schema_add_website', true );
apply_filters( 'meowseo_schema_add_organization', true );
apply_filters( 'meowseo_schema_add_breadcrumbs', true );
apply_filters( 'meowseo_schema_add_webpage', true );
```

## JSON-LD Output

The module automatically outputs JSON-LD in the `<head>` section:

```html
<script type="application/ld+json" class="meowseo-schema">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "url": "https://example.com/",
      "name": "My Website"
    },
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "My Company",
      "url": "https://example.com/"
    },
    {
      "@type": "Article",
      "@id": "https://example.com/my-post/#article",
      "headline": "My Post Title",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      }
    }
  ]
}
</script>
```

## Performance

- **Static Caching** - Schemas are cached in memory
- **Efficient Queries** - Optimized database queries
- **Lazy Loading** - Components loaded only when needed
- **Minimal Overhead** - No impact on page load time

## Security

- **Sanitization** - All input is sanitized
- **Validation** - Schema data is validated
- **Capability Checks** - Proper permission checks
- **Nonce Verification** - CSRF protection

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- Classic Editor
- Gutenberg Editor
- WooCommerce (Product schema)
- Any theme

## Development

### File Structure

```
includes/modules/schema/
├── class-schema-module.php          # Main module
├── class-schema-db.php               # Database operations
├── class-schema-variables.php        # Variable replacement
├── class-schema-type.php             # Base schema type
├── class-schema-registry.php         # Type registry
├── class-schema-jsonld.php           # JSON-LD generator
├── class-schema-frontend.php         # Frontend handler
│
├── types/
│   ├── class-article-schema.php      # Article
│   ├── class-product-schema.php      # Product
│   ├── class-recipe-schema.php       # Recipe
│   ├── class-event-schema.php        # Event
│   ├── class-local-business-schema.php # Local Business
│   ├── class-faq-schema.php          # FAQ
│   ├── class-howto-schema.php        # HowTo
│   ├── class-review-schema.php       # Review
│   ├── class-video-schema.php        # Video
│   └── class-course-schema.php       # Course
│
├── assets/
│   ├── js/                           # JavaScript files
│   └── css/                          # CSS files
│
└── views/                            # Template files
```

### Adding a New Schema Type

1. Create a new file in `types/` folder
2. Extend `Schema_Type` class
3. Implement `get_fields()` method
4. Register on `meowseo_schema_types_loaded` action

Example:

```php
<?php
namespace MeowSEO\Modules\Schema\Types;

use MeowSEO\Modules\Schema\Schema_Type;

class My_Schema extends Schema_Type {
    
    public function __construct() {
        $this->type = 'MySchema';
        $this->label = __( 'My Schema', 'meowseo' );
        $this->description = __( 'Description', 'meowseo' );
        $this->icon = 'admin-generic';
    }
    
    public function get_fields(): array {
        return [
            // Define fields here
        ];
    }
}

add_action( 'meowseo_schema_types_loaded', function() {
    $schema = new My_Schema();
    $schema->register();
});
```

## Support

For issues and feature requests, please visit:
https://github.com/meowseo/meowseo

## License

GPL v2 or later

## Credits

Inspired by RankMath's schema generator architecture.
