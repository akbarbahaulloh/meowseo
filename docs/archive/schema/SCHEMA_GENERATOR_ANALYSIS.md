# Analisis Schema Generator RankMath untuk Adaptasi ke MeowSEO

## Executive Summary

Setelah menganalisa RankMath Free dan Pro, saya menemukan bahwa schema generator mereka memiliki arsitektur yang sangat baik dengan fitur-fitur berikut:

### RankMath Free:
- ✅ Schema builder di metabox post/page editor
- ✅ Multiple schema types per post (tapi hanya 1 primary)
- ✅ Schema blocks (FAQ, HowTo, TOC)
- ✅ Shortcode support untuk schema
- ✅ Auto-generated schema (Article, Product, etc.)
- ✅ Variable replacement system

### RankMath Pro (Fitur Tambahan):
- ✅ **Schema Templates** - Custom Post Type untuk reusable schemas
- ✅ **Display Conditions** - Conditional logic untuk menampilkan schema
- ✅ **Multi-schema per post** - Unlimited schemas per post
- ✅ **Schema for Taxonomies** - Schema untuk category/tag pages
- ✅ **Advanced schema types** - Dataset, FactCheck, Movie, dll
- ✅ **Video schema auto-detection**

---

## Arsitektur RankMath Schema

### 1. **Database Structure**

```
wp_postmeta:
├── rank_math_schema_Article (meta_key)
│   └── Serialized array dengan struktur:
│       ├── @type: "Article"
│       ├── headline: "%title%"
│       ├── description: "%excerpt%"
│       ├── metadata:
│       │   ├── title: "Article"
│       │   ├── type: "template" | "custom"
│       │   ├── shortcode: "s-abc123"
│       │   ├── isPrimary: true
│       │   ├── name: "%title%"
│       │   └── description: "%excerpt%"
│       └── ... (schema properties)
│
├── rank_math_schema_Product
├── rank_math_schema_Recipe
└── rank_math_shortcode_schema_s-abc123 (shortcut untuk query cepat)
    └── meta_id dari schema
```

**Pro Version menambahkan:**
```
wp_posts (post_type: rank_math_schema):
├── Post Title: "Product Schema Template"
├── Post Meta:
│   └── rank_math_schema_Product:
│       ├── @type: "Product"
│       ├── metadata:
│       │   ├── displayConditions: [...]
│       │   ├── isTemplate: true
│       │   └── ...
│       └── ... (schema properties)
```

### 2. **File Structure**

```
includes/modules/schema/
├── class-schema.php          # Main module class
├── class-admin.php            # Admin UI & metabox
├── class-jsonld.php           # JSON-LD output generator
├── class-db.php               # Database operations
├── class-frontend.php         # Frontend rendering
├── class-blocks.php           # Gutenberg blocks
├── class-snippet-shortcode.php # Shortcode handler
├── interface-snippet.php      # Interface untuk snippet classes
│
├── snippets/                  # Auto-generated schemas
│   ├── class-article.php
│   ├── class-product.php
│   ├── class-breadcrumbs.php
│   ├── class-website.php
│   └── ...
│
├── shortcode/                 # Shortcode implementations
│   ├── recipe.php
│   ├── product.php
│   ├── event.php
│   └── ...
│
├── blocks/                    # Gutenberg blocks
│   ├── faq/
│   ├── howto/
│   └── schema/
│
├── assets/
│   ├── js/
│   │   ├── schema-gutenberg.js    # React app untuk schema builder
│   │   └── schema-template.js     # Pro: Template editor
│   └── css/
│       └── schema.css
│
└── views/
    └── metabox-options.php
```

**Pro Version menambahkan:**
```
includes/modules/schema/
├── class-post-type.php        # Schema Template CPT
├── class-display-conditions.php # Conditional logic
├── class-taxonomy.php         # Schema untuk taxonomies
├── class-ajax.php             # AJAX handlers
├── class-rest.php             # REST API endpoints
├── class-parser.php           # Schema parser
└── class-video.php            # Video schema detection
```

### 3. **Schema Builder UI (React)**

RankMath menggunakan **React** untuk schema builder dengan komponen:

```javascript
// Schema Builder Components
SchemaBuilder
├── SchemaList (sidebar)
│   ├── SchemaItem (draggable)
│   └── AddSchemaButton
│
└── SchemaEditor (main panel)
    ├── SchemaTypeSelector
    ├── SchemaFields (dynamic based on type)
    │   ├── TextField
    │   ├── TextareaField
    │   ├── ImageField
    │   ├── DateField
    │   ├── RatingField
    │   └── RepeaterField
    │
    ├── SchemaPreview (JSON-LD preview)
    └── SchemaValidation (Google validator)
```

### 4. **Schema Types Supported**

**Free Version:**
- Article (BlogPosting, NewsArticle)
- Book
- Course
- Event
- JobPosting
- Local Business
- Music
- Person
- Product
- Recipe
- Restaurant
- Review
- Service
- Software Application
- Video
- FAQ (via block)
- HowTo (via block)

**Pro Version Adds:**
- Dataset
- FactCheck
- Movie
- Podcast Episode
- ClaimReview
- + Multi-schema support

### 5. **Key Features**

#### A. **Variable Replacement System**
```php
// Variables yang bisa digunakan:
%title%
%excerpt%
%date(Y-m-d\TH:i:sP)%
%modified(Y-m-d\TH:i:sP)%
%name% (author name)
%post_author%
%keywords%
%sitename%
%sep%
// ... dan banyak lagi
```

#### B. **Schema Inheritance**
```
Global Entities (selalu ada):
├── Website
├── Organization/Person (Publisher)
└── WebPage

Post-specific:
├── Article/Product/Recipe (primary schema)
├── Breadcrumbs
├── PrimaryImage
└── Author (ProfilePage)
```

#### C. **Conditional Schema Output**
```php
// Free: Basic conditions
- is_singular()
- is_front_page()
- has_block('rank-math/faq-block')
- post_password_required()

// Pro: Advanced Display Conditions
- Post Type
- Taxonomy
- Specific Posts/Pages
- User Roles
- Date Range
- Custom PHP conditions
```

---

## Perbandingan dengan MeowSEO Saat Ini

### MeowSEO Current State:

```
includes/helpers/
├── class-schema-builder.php   # Basic schema builder
├── class-abstract-schema-node.php
└── schema-nodes/
    ├── class-article-node.php
    ├── class-product-node.php
    ├── class-faq-node.php
    ├── class-breadcrumb-node.php
    ├── class-organization-node.php
    ├── class-webpage-node.php
    └── class-website-node.php
```

**Kekurangan MeowSEO:**
- ❌ Tidak ada UI builder untuk schema
- ❌ Tidak ada multi-schema support
- ❌ Tidak ada schema templates
- ❌ Tidak ada shortcode support
- ❌ Tidak ada Gutenberg blocks
- ❌ Schema hardcoded, tidak flexible
- ❌ Tidak ada variable replacement
- ❌ Tidak ada schema preview/validation

**Kelebihan MeowSEO:**
- ✅ Clean OOP architecture
- ✅ Modular design
- ✅ Already has basic schema types

---

## Rekomendasi Implementasi untuk MeowSEO

### Phase 1: Foundation (Free Version Features)

#### 1.1. Database & Storage
```php
// Struktur meta yang akan digunakan:
_meowseo_schema_Article
_meowseo_schema_Product
_meowseo_schema_Recipe
_meowseo_shortcode_schema_{id}

// Format data:
[
    '@type' => 'Article',
    'headline' => '%title%',
    'description' => '%excerpt%',
    'metadata' => [
        'title' => 'Article',
        'type' => 'template',
        'shortcode' => 's-abc123',
        'isPrimary' => true,
    ],
    // ... schema properties
]
```

**File baru:**
```
includes/modules/schema/
├── class-schema-module.php        # Main module
├── class-schema-admin.php         # Admin UI
├── class-schema-db.php            # Database operations
├── class-schema-jsonld.php        # JSON-LD generator
├── class-schema-frontend.php      # Frontend output
└── class-schema-rest.php          # REST API
```

#### 1.2. Schema Builder UI (React)

**Lokasi:**
```
includes/modules/schema/assets/
├── js/
│   ├── schema-builder.jsx         # Main React app
│   ├── components/
│   │   ├── SchemaList.jsx
│   │   ├── SchemaEditor.jsx
│   │   ├── SchemaFields.jsx
│   │   └── SchemaPreview.jsx
│   └── schema-builder.js          # Compiled bundle
└── css/
    └── schema-builder.css
```

**Build Process:**
```bash
# Menggunakan @wordpress/scripts
npm install @wordpress/scripts --save-dev
npm run build
```

#### 1.3. Schema Types

**Refactor existing schema nodes:**
```
includes/modules/schema/types/
├── class-schema-type.php          # Base class
├── class-article-schema.php
├── class-product-schema.php
├── class-recipe-schema.php
├── class-event-schema.php
├── class-local-business-schema.php
└── ...
```

**Setiap schema type memiliki:**
```php
class Article_Schema extends Schema_Type {
    public function get_type() {
        return 'Article';
    }
    
    public function get_fields() {
        return [
            'headline' => [
                'type' => 'text',
                'label' => 'Headline',
                'default' => '%title%',
                'required' => true,
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => '%excerpt%',
            ],
            // ...
        ];
    }
    
    public function generate( $data ) {
        // Generate JSON-LD
    }
}
```

#### 1.4. Variable Replacement

**File baru:**
```php
includes/modules/schema/class-schema-variables.php

class Schema_Variables {
    public static function replace( $text, $post = null ) {
        // Replace %title%, %excerpt%, etc.
    }
    
    public static function get_available_variables() {
        return [
            '%title%' => 'Post Title',
            '%excerpt%' => 'Post Excerpt',
            '%date(format)%' => 'Post Date',
            // ...
        ];
    }
}
```

#### 1.5. Metabox Integration

**Integrasi dengan metabox yang sudah ada:**
```javascript
// Di Classic Editor metabox
<div id="meowseo-schema-builder"></div>

// Di Gutenberg sidebar
wp.plugins.registerPlugin('meowseo-schema', {
    render: SchemaBuilderPanel
});
```

### Phase 2: Advanced Features (Pro-like)

#### 2.1. Schema Templates (Custom Post Type)

```php
// Register CPT
register_post_type('meowseo_schema', [
    'label' => 'Schema Templates',
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => 'meowseo',
    'supports' => ['title'],
    'capability_type' => 'page',
]);
```

#### 2.2. Display Conditions

```php
includes/modules/schema/class-display-conditions.php

class Display_Conditions {
    public function check( $conditions, $context ) {
        // Check if schema should be displayed
        // Based on post type, taxonomy, specific posts, etc.
    }
}
```

#### 2.3. Multi-Schema Support

```javascript
// UI untuk menambah multiple schemas
<SchemaList>
    <SchemaItem type="Article" isPrimary={true} />
    <SchemaItem type="Product" isPrimary={false} />
    <SchemaItem type="Review" isPrimary={false} />
    <AddSchemaButton />
</SchemaList>
```

#### 2.4. Schema for Taxonomies

```php
// Support schema untuk category/tag pages
includes/modules/schema/class-taxonomy-schema.php
```

### Phase 3: Blocks & Shortcodes

#### 3.1. Gutenberg Blocks

```
includes/modules/schema/blocks/
├── faq/
│   ├── block.json
│   ├── edit.js
│   └── save.js
├── howto/
└── schema/
```

#### 3.2. Shortcodes

```php
[meowseo-schema id="s-abc123"]
[meowseo-recipe]
[meowseo-faq]
```

---

## Implementation Roadmap

### Sprint 1 (2 weeks): Foundation
- [ ] Create schema module structure
- [ ] Implement Schema_DB class
- [ ] Create basic schema types (Article, Product, Recipe)
- [ ] Implement variable replacement system

### Sprint 2 (2 weeks): UI Builder
- [ ] Setup React build process
- [ ] Create SchemaBuilder React app
- [ ] Implement SchemaList component
- [ ] Implement SchemaEditor component
- [ ] Implement field types (text, textarea, image, date, rating)

### Sprint 3 (2 weeks): Integration
- [ ] Integrate with Classic Editor metabox
- [ ] Integrate with Gutenberg sidebar
- [ ] Implement JSON-LD output
- [ ] Add schema preview
- [ ] Add Google validation link

### Sprint 4 (1 week): Testing & Polish
- [ ] Test all schema types
- [ ] Test variable replacement
- [ ] Test JSON-LD output
- [ ] Fix bugs
- [ ] Write documentation

### Sprint 5 (2 weeks): Pro Features
- [ ] Implement Schema Templates CPT
- [ ] Implement Display Conditions
- [ ] Implement Multi-schema support
- [ ] Implement Taxonomy schema

### Sprint 6 (2 weeks): Blocks & Shortcodes
- [ ] Create FAQ block
- [ ] Create HowTo block
- [ ] Implement shortcode system
- [ ] Test blocks & shortcodes

---

## Technical Decisions

### 1. **React vs Vue vs Vanilla JS**
**Recommendation: React**
- WordPress Gutenberg uses React
- Better ecosystem & community
- Easier to find developers
- @wordpress/scripts makes it easy

### 2. **Storage: Post Meta vs Custom Table**
**Recommendation: Post Meta**
- Easier to implement
- Better compatibility with WordPress
- Easier to export/import
- RankMath uses this approach

### 3. **Schema Validation**
**Recommendation: Client-side + Server-side**
- Client: Real-time validation in UI
- Server: Validation before save
- Link to Google Rich Results Test

### 4. **Variable System**
**Recommendation: Custom implementation**
- Similar to RankMath's approach
- Support for custom variables
- Support for date formatting
- Support for conditional variables

---

## Key Differences from RankMath

### What to Keep Different:
1. **Simpler UI** - Less overwhelming for beginners
2. **Better defaults** - Smart auto-fill based on content
3. **AI Integration** - Use existing AI module to suggest schema
4. **Better WooCommerce integration** - Leverage existing product module

### What to Adopt:
1. **Schema Templates** - Very useful feature
2. **Multi-schema support** - Essential for complex pages
3. **Variable replacement** - Makes schemas dynamic
4. **Display conditions** - Powerful feature for templates

---

## Estimated Effort

- **Phase 1 (Foundation)**: 4 weeks
- **Phase 2 (Advanced)**: 2 weeks
- **Phase 3 (Blocks)**: 2 weeks
- **Total**: 8 weeks (2 months)

**Team Required:**
- 1 Backend Developer (PHP)
- 1 Frontend Developer (React)
- 1 QA Tester

---

## Next Steps

1. **Review & Approve** this analysis
2. **Create detailed wireframes** for UI
3. **Setup development environment**
4. **Start Sprint 1** implementation
5. **Weekly progress reviews**

---

## References

- RankMath Free: `d:\reference\seo-by-rank-math`
- RankMath Pro: `d:\reference\seo-by-rank-math-pro`
- Schema.org: https://schema.org
- Google Rich Results: https://developers.google.com/search/docs/appearance/structured-data
