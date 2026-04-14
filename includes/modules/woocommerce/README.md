# WooCommerce Module

## Overview

The WooCommerce module provides SEO enhancements specific to WooCommerce product pages. It is conditionally loaded only when WooCommerce is active (`class_exists('WooCommerce')`).

## Features

### 1. Product Post Type SEO Support (Requirement 12.1)

The Meta module already registers SEO meta fields for all public post types, including WooCommerce products. This means product edit screens automatically have access to:

- SEO title (`meowseo_title`)
- Meta description (`meowseo_description`)
- Robots directive (`meowseo_robots`)
- Canonical URL (`meowseo_canonical`)
- Focus keyword (`meowseo_focus_keyword`)
- Schema type override (`meowseo_schema_type`)
- Social meta fields

No additional code is needed in the WooCommerce module for this functionality.

### 2. Product Schema Output (Requirement 12.2)

The Schema_Builder helper class already includes a `build_product()` method that generates Product JSON-LD with:

- Product name
- Description
- SKU
- Offers (price, currency, availability)
- Aggregate rating (when reviews exist)

The Schema module automatically detects when a post type is `product` and WooCommerce is active, then includes the Product schema in the output.

### 3. Sitemap Product Filtering (Requirement 12.3)

The WooCommerce module adds a filter to exclude out-of-stock products from sitemaps when the option is enabled:

```php
add_filter( 'meowseo_sitemap_posts', array( $this, 'filter_sitemap_products' ), 10, 2 );
```

The filter checks the `woocommerce_exclude_out_of_stock` option and uses WooCommerce's `is_in_stock()` method to determine product availability.

### 4. SEO Score Columns (Requirement 12.4)

The module adds an SEO score column to the WooCommerce product list table in the admin:

- Displays a color-coded indicator (red/orange/green)
- Shows the numeric score (0-100)
- Uses the Meta module's `get_seo_analysis()` method
- Column is sortable

## Implementation Details

### Module Structure

```
includes/modules/woocommerce/
├── class-woocommerce.php  # Main module class
└── README.md              # This file
```

### Conditional Loading

The Module_Manager checks for WooCommerce before instantiating the module:

```php
// Special handling for WooCommerce module - only load if WooCommerce is active.
if ( 'woocommerce' === $module_id && ! class_exists( 'WooCommerce' ) ) {
    return false;
}
```

### Hooks Registered

1. **Product List Table Columns**
   - `manage_product_posts_columns` - Adds SEO score column
   - `manage_product_posts_custom_column` - Renders SEO score content
   - `manage_edit-product_sortable_columns` - Makes column sortable

2. **Sitemap Filtering**
   - `meowseo_sitemap_posts` - Filters products based on stock status

### Options

The module uses the following option:

- `woocommerce_exclude_out_of_stock` (boolean) - When true, out-of-stock products are excluded from sitemaps

This option should be exposed in the plugin settings UI when WooCommerce is active (Requirement 2.5).

## Integration Points

### Meta Module

The WooCommerce module depends on the Meta module for:
- SEO analysis computation
- Access to `get_seo_analysis()` method

### Schema Module

The Schema module automatically handles Product schema when:
- Post type is `product`
- WooCommerce is active
- Uses Schema_Builder's `build_product()` method

### Sitemap Module

The Sitemap_Generator applies the `meowseo_sitemap_posts` filter, allowing the WooCommerce module to filter products based on stock status.

## Requirements Validation

- ✅ **Requirement 12.1**: Meta module extends to product post type (automatic via `get_post_types()`)
- ✅ **Requirement 12.2**: Product JSON-LD output (Schema_Builder's `build_product()` method)
- ✅ **Requirement 12.3**: Sitemap filtering for out-of-stock products
- ✅ **Requirement 12.4**: SEO score columns in product list table

## Performance Considerations

- Module is only loaded when WooCommerce is active
- SEO score computation in list table uses cached analysis when available
- Sitemap filtering uses WooCommerce's native `is_in_stock()` method
- No additional database queries beyond what Meta and Schema modules already perform

## Future Enhancements

Potential future additions (not in current requirements):

- Bulk SEO score analysis for products
- Product-specific SEO recommendations
- Integration with WooCommerce product variations
- Schema support for product bundles and grouped products
