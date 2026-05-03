# Schema Generator - Executive Summary

## 🎯 Tujuan
Mengadaptasi schema generator RankMath ke MeowSEO dengan UI builder yang intuitif dan fitur multi-schema seperti RankMath Pro.

## 📊 Analisis RankMath

### RankMath Free
- Schema builder di metabox dengan UI React
- Support 15+ schema types
- Variable replacement (%title%, %excerpt%, dll)
- FAQ & HowTo blocks
- Shortcode support

### RankMath Pro (Fitur Unggulan)
- **Schema Templates** - CPT untuk reusable schemas
- **Multi-schema per post** - Unlimited schemas
- **Display Conditions** - Conditional logic
- **Taxonomy schema** - Schema untuk category/tag

## 🏗️ Arsitektur RankMath

### Database
```
wp_postmeta:
- rank_math_schema_Article
- rank_math_schema_Product
- rank_math_shortcode_schema_{id}

wp_posts (Pro):
- post_type: rank_math_schema (Templates)
```

### File Structure
```
includes/modules/schema/
├── class-schema.php          # Main
├── class-admin.php           # UI
├── class-jsonld.php          # Output
├── class-db.php              # Database
├── snippets/                 # Auto schemas
├── blocks/                   # Gutenberg
└── assets/js/                # React UI
```

### UI Components (React)
```
SchemaBuilder
├── SchemaList (sidebar)
│   └── SchemaItem (draggable)
└── SchemaEditor (main)
    ├── SchemaTypeSelector
    ├── SchemaFields (dynamic)
    └── SchemaPreview
```

## 💡 Rekomendasi untuk MeowSEO

### Phase 1: Foundation (4 weeks)
✅ **Database & Storage**
- Gunakan post meta seperti RankMath
- Format: `_meowseo_schema_{Type}`

✅ **Schema Builder UI (React)**
- Build dengan @wordpress/scripts
- Komponen: SchemaList, SchemaEditor, SchemaFields
- Integrasi dengan Classic Editor & Gutenberg

✅ **Schema Types**
- Refactor existing schema nodes
- Support 10+ types: Article, Product, Recipe, Event, dll
- Dynamic field generation

✅ **Variable Replacement**
- %title%, %excerpt%, %date%, dll
- Custom variable support

### Phase 2: Pro Features (2 weeks)
✅ **Schema Templates**
- Custom Post Type: `meowseo_schema`
- Reusable schemas dengan display conditions

✅ **Multi-Schema Support**
- Multiple schemas per post
- Primary schema designation

✅ **Display Conditions**
- Post type, taxonomy, specific posts
- Date range, user roles

### Phase 3: Blocks & Shortcodes (2 weeks)
✅ **Gutenberg Blocks**
- FAQ Block
- HowTo Block
- Schema Block

✅ **Shortcodes**
- [meowseo-schema id="..."]
- [meowseo-recipe]
- [meowseo-faq]

## 🎨 Key Features

### 1. Visual Schema Builder
```
┌─────────────────────────────────────┐
│ Schema List          │ Schema Editor│
│ ─────────────        │              │
│ ✓ Article (Primary)  │ Type: Article│
│   Product            │              │
│   Review             │ Headline:    │
│                      │ [%title%]    │
│ + Add Schema         │              │
│                      │ Description: │
│                      │ [%excerpt%]  │
│                      │              │
│                      │ [Preview]    │
└─────────────────────────────────────┘
```

### 2. Smart Defaults
- Auto-detect content type
- Pre-fill fields dengan variables
- AI suggestions (leverage existing AI module)

### 3. Schema Templates
```
Templates:
├── Product Schema (WooCommerce)
├── Recipe Schema (Food Blog)
├── Event Schema (Event Site)
└── Article Schema (News Site)

Display Conditions:
- Show on: Product Category = "Electronics"
- Exclude: Specific products
```

### 4. Multi-Schema Example
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Article",
      "headline": "Best Laptops 2024",
      "isPrimary": true
    },
    {
      "@type": "Product",
      "name": "MacBook Pro",
      "offers": {...}
    },
    {
      "@type": "Review",
      "reviewRating": {...}
    }
  ]
}
```

## 📈 Benefits

### For Users
- ✅ Easy to use visual builder
- ✅ No coding required
- ✅ Real-time preview
- ✅ Google validation integration
- ✅ Reusable templates

### For Developers
- ✅ Clean OOP architecture
- ✅ Extensible schema types
- ✅ Hook system for customization
- ✅ REST API support

### For SEO
- ✅ Rich snippets in search results
- ✅ Better CTR
- ✅ Enhanced SERP appearance
- ✅ Voice search optimization

## 🚀 Implementation Timeline

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| Phase 1: Foundation | 4 weeks | Schema builder UI, basic types, variables |
| Phase 2: Pro Features | 2 weeks | Templates, multi-schema, conditions |
| Phase 3: Blocks | 2 weeks | FAQ/HowTo blocks, shortcodes |
| **Total** | **8 weeks** | **Full schema generator system** |

## 💰 Resource Requirements

- 1 Backend Developer (PHP) - Full time
- 1 Frontend Developer (React) - Full time
- 1 QA Tester - Part time

## 🎯 Success Metrics

- [ ] 10+ schema types supported
- [ ] Visual builder working in Classic & Gutenberg
- [ ] Multi-schema support
- [ ] Schema templates with display conditions
- [ ] 100% Google validation pass rate
- [ ] User satisfaction > 90%

## 🔑 Key Differentiators from RankMath

### What Makes MeowSEO Better:
1. **AI-Powered Suggestions** - Auto-generate schema from content
2. **Simpler UI** - Less overwhelming for beginners
3. **Better WooCommerce Integration** - Seamless product schema
4. **Smart Defaults** - Intelligent auto-fill
5. **Modern Design** - Clean, intuitive interface

### What We Adopt from RankMath:
1. Schema Templates (Pro feature)
2. Multi-schema support (Pro feature)
3. Variable replacement system
4. Display conditions (Pro feature)
5. React-based UI builder

## 📝 Next Actions

1. ✅ **Approve** this analysis
2. ⏳ **Create wireframes** for UI design
3. ⏳ **Setup React build** environment
4. ⏳ **Start Phase 1** implementation
5. ⏳ **Weekly reviews** with stakeholders

## 📚 Documentation

- Full Analysis: `SCHEMA_GENERATOR_ANALYSIS.md`
- Code Examples: `docs/schema/code-examples/`
- UI Mockups: `docs/schema/mockups/`
- API Documentation: `docs/schema/api/`

---

**Prepared by:** Kiro AI  
**Date:** May 3, 2026  
**Status:** Ready for Review
