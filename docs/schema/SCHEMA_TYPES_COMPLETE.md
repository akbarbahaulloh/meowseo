# MeowSEO Schema Types - Complete List

## Overview
MeowSEO now has **18 schema types** matching RankMath's offerings, providing comprehensive structured data support for various content types.

## All Available Schema Types

### 1. **Article** ✅
- **Icon**: media-document
- **Use Case**: Blog posts, news articles, investigative reports
- **Key Fields**: headline, description, author, publisher, datePublished, dateModified
- **File**: `class-article-schema.php`

### 2. **Product** ✅
- **Icon**: products
- **Use Case**: E-commerce products, WooCommerce integration
- **Key Fields**: name, description, image, brand, offers, aggregateRating, sku
- **File**: `class-product-schema.php`

### 3. **Recipe** ✅
- **Icon**: carrot
- **Use Case**: Food blogs, cooking websites
- **Key Fields**: name, description, ingredients, instructions, cookTime, nutrition
- **File**: `class-recipe-schema.php`

### 4. **Event** ✅
- **Icon**: calendar
- **Use Case**: Conferences, concerts, webinars, meetups
- **Key Fields**: name, startDate, endDate, location, organizer, offers
- **File**: `class-event-schema.php`

### 5. **Local Business** ✅
- **Icon**: store
- **Use Case**: Physical businesses, stores, offices
- **Key Fields**: name, address, telephone, openingHours, geo, priceRange
- **File**: `class-local-business-schema.php`

### 6. **Video** ✅
- **Icon**: video-alt3
- **Use Case**: Video content, YouTube embeds, video tutorials
- **Key Fields**: name, description, thumbnailUrl, uploadDate, duration, contentUrl
- **File**: `class-video-schema.php`

### 7. **Course** ✅
- **Icon**: welcome-learn-more
- **Use Case**: Online courses, educational content, training programs
- **Key Fields**: name, description, provider, hasCourseInstance, offers
- **File**: `class-course-schema.php`

### 8. **FAQ** ✅
- **Icon**: editor-help
- **Use Case**: FAQ pages, Q&A sections
- **Key Fields**: mainEntity (array of questions and answers)
- **File**: `class-faq-schema.php`

### 9. **Review** ✅
- **Icon**: star-filled
- **Use Case**: Product reviews, service reviews, business reviews
- **Key Fields**: itemReviewed, reviewRating, author, reviewBody
- **File**: `class-review-schema.php`

### 10. **HowTo** ✅
- **Icon**: list-view
- **Use Case**: Tutorials, guides, step-by-step instructions
- **Key Fields**: name, description, step, totalTime, tool, supply
- **File**: `class-howto-schema.php`

---

## NEW SCHEMA TYPES ADDED (Phase 5)

### 11. **Book** ✅ NEW
- **Icon**: book
- **Use Case**: Book reviews, author pages, bookstores, libraries
- **Key Fields**: 
  - name (book title)
  - author (Person/Organization)
  - isbn
  - bookFormat (Hardcover, Paperback, EBook, Audiobook)
  - numberOfPages
  - publisher
  - datePublished
  - bookEdition
  - inLanguage
  - aggregateRating
- **File**: `class-book-schema.php`

### 12. **Music** ✅ NEW
- **Icon**: format-audio
- **Use Case**: Music albums, songs, artist pages, music streaming
- **Key Fields**:
  - name (song/album name)
  - byArtist (Person/MusicGroup)
  - inAlbum
  - duration
  - genre
  - datePublished
  - recordLabel
  - isrcCode
  - aggregateRating
- **File**: `class-music-schema.php`

### 13. **Movie** ✅ NEW
- **Icon**: format-video
- **Use Case**: Movie reviews, film databases, cinema websites
- **Key Fields**:
  - name (movie title)
  - director (Person)
  - actor (array of Person)
  - datePublished
  - duration
  - genre
  - contentRating
  - trailer (VideoObject)
  - aggregateRating
- **File**: `class-movie-schema.php`

### 14. **Person** ✅ NEW
- **Icon**: admin-users
- **Use Case**: Author pages, team member profiles, biography pages
- **Key Fields**:
  - name
  - jobTitle
  - worksFor (Organization)
  - email
  - telephone
  - address
  - birthDate
  - nationality
  - sameAs (social media links)
- **File**: `class-person-schema.php`

### 15. **Restaurant** ✅ NEW
- **Icon**: food
- **Use Case**: Restaurant websites, food establishments, dining guides
- **Key Fields**:
  - name
  - address (PostalAddress)
  - telephone
  - servesCuisine
  - priceRange
  - menu (URL)
  - acceptsReservations
  - openingHours
  - aggregateRating
  - geo (GeoCoordinates)
- **File**: `class-restaurant-schema.php`

### 16. **Software Application** ✅ NEW
- **Icon**: desktop
- **Use Case**: App landing pages, software products, mobile apps
- **Key Fields**:
  - name
  - applicationCategory (GameApplication, BusinessApplication, etc.)
  - operatingSystem
  - downloadUrl
  - installUrl
  - softwareVersion
  - fileSize
  - offers (price, currency)
  - aggregateRating
  - author (developer)
  - screenshot (array)
- **File**: `class-software-application-schema.php`

### 17. **Job Posting** ✅ NEW
- **Icon**: businessman
- **Use Case**: Career pages, job boards, recruitment websites
- **Key Fields**:
  - title (job title)
  - description
  - datePosted
  - validThrough
  - employmentType (FULL_TIME, PART_TIME, CONTRACTOR, etc.)
  - hiringOrganization (Organization)
  - jobLocation (Place with PostalAddress)
  - baseSalary (MonetaryAmount)
  - jobBenefits
  - experienceRequirements
  - educationRequirements
  - skills
  - qualifications
- **File**: `class-job-posting-schema.php`

### 18. **Service** ✅ NEW
- **Icon**: admin-tools
- **Use Case**: Service pages, business offerings, professional services
- **Key Fields**:
  - name
  - description
  - provider (Organization/Person)
  - serviceType
  - category
  - areaServed (array of locations)
  - availableChannel (ServiceChannel)
  - offers (price, currency)
  - hoursAvailable (OpeningHoursSpecification)
  - aggregateRating
  - termsOfService
- **File**: `class-service-schema.php`

---

## Schema Type Comparison with RankMath

| Schema Type | MeowSEO | RankMath Free | RankMath Pro |
|-------------|---------|---------------|--------------|
| Article | ✅ | ✅ | ✅ |
| Product | ✅ | ✅ | ✅ |
| Recipe | ✅ | ✅ | ✅ |
| Event | ✅ | ✅ | ✅ |
| Local Business | ✅ | ✅ | ✅ |
| Video | ✅ | ✅ | ✅ |
| Course | ✅ | ✅ | ✅ |
| FAQ | ✅ | ✅ | ✅ |
| Review | ✅ | ✅ | ✅ |
| HowTo | ✅ | ✅ | ✅ |
| **Book** | ✅ | ✅ | ✅ |
| **Music** | ✅ | ✅ | ✅ |
| **Movie** | ✅ | ❌ | ✅ |
| **Person** | ✅ | ✅ | ✅ |
| **Restaurant** | ✅ | ✅ | ✅ |
| **Software Application** | ✅ | ✅ | ✅ |
| **Job Posting** | ✅ | ✅ | ✅ |
| **Service** | ✅ | ✅ | ✅ |
| Dataset | ❌ | ❌ | ✅ |
| FactCheck | ❌ | ❌ | ✅ |
| Podcast Episode | ❌ | ❌ | ✅ |

**Summary**: MeowSEO now has **18 schema types**, matching RankMath Free and covering most common use cases. RankMath Pro has 3 additional advanced types (Dataset, FactCheck, Podcast Episode) which can be added in future updates if needed.

---

## Technical Implementation

### File Structure
```
meowseo/includes/modules/schema/types/
├── class-article-schema.php
├── class-product-schema.php
├── class-recipe-schema.php
├── class-event-schema.php
├── class-local-business-schema.php
├── class-video-schema.php
├── class-course-schema.php
├── class-faq-schema.php
├── class-review-schema.php
├── class-howto-schema.php
├── class-book-schema.php          ← NEW
├── class-music-schema.php         ← NEW
├── class-movie-schema.php         ← NEW
├── class-person-schema.php        ← NEW
├── class-restaurant-schema.php    ← NEW
├── class-software-application-schema.php ← NEW
├── class-job-posting-schema.php   ← NEW
└── class-service-schema.php       ← NEW
```

### Auto-Registration
All schema types are automatically registered via the `meowseo_schema_types_loaded` action hook:

```php
add_action( 'meowseo_schema_types_loaded', function() {
    $schema = new Schema_Type_Class();
    $schema->register();
} );
```

### React UI Integration
The React schema builder automatically detects all registered schema types and displays them in the UI with:
- Schema type selector dropdown
- Dynamic field rendering based on schema type
- Field validation
- JSON-LD preview
- Google Rich Results Test link

---

## Field Types Supported

Each schema type uses various field types:

1. **text** - Single line text input
2. **textarea** - Multi-line text input
3. **url** - URL input with validation
4. **email** - Email input with validation
5. **number** - Numeric input
6. **date** - Date picker
7. **time** - Time picker
8. **select** - Dropdown selection
9. **image** - Image uploader
10. **group** - Nested object fields
11. **repeater** - Array of items (can be repeated)

---

## Variable Replacement System

All schema types support dynamic variables:

- `%title%` - Post title
- `%excerpt%` - Post excerpt
- `%content%` - Post content
- `%featured_image%` - Featured image URL
- `%author%` - Author name
- `%author_url%` - Author URL
- `%date(format)%` - Post date with custom format
- `%modified(format)%` - Modified date with custom format
- `%url%` - Post URL
- `%sitename%` - Site name
- `%sep%` - Separator

---

## Next Steps

### Immediate
- ✅ All 18 schema types created
- ✅ React app rebuilt with new types
- ✅ Files compiled successfully

### Testing Required
1. Test each new schema type in WordPress admin
2. Verify JSON-LD output for each type
3. Validate with Google Rich Results Test
4. Test variable replacement
5. Test field validation

### Future Enhancements
1. Add Dataset schema (Pro feature)
2. Add FactCheck schema (Pro feature)
3. Add Podcast Episode schema (Pro feature)
4. Add schema templates (reusable schemas)
5. Add display conditions (conditional logic)
6. Add multi-schema support (multiple schemas per post)

---

## Documentation

For detailed implementation guide, see:
- `EXECUTIVE_SUMMARY.md` - High-level overview
- `IMPLEMENTATION_PROGRESS.md` - Phase-by-phase progress
- `SCHEMA_GENERATOR_ANALYSIS.md` - RankMath analysis and comparison

---

**Last Updated**: Phase 5 Complete
**Total Schema Types**: 18
**Status**: ✅ Ready for Testing
