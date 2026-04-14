# Admin Interface Implementation

## Overview

This document describes the implementation of the MeowSEO admin interface and settings page (Task 18).

## Components Implemented

### 1. Admin Class (`includes/class-admin.php`)

**Purpose**: Manages the WordPress admin interface for MeowSEO settings.

**Key Features**:
- Registers top-level admin menu page under "MeowSEO"
- Renders React root element for settings UI
- Enqueues `meowseo-editor` asset handle (shared with Gutenberg sidebar)
- Localizes script with settings data and REST API configuration
- Verifies `manage_options` capability for all admin operations (Requirement 15.3)

**Hooks**:
- `admin_menu` - Registers admin menu page
- `admin_enqueue_scripts` - Loads assets only on settings page

### 2. Settings App (`src/settings/SettingsApp.js`)

**Purpose**: React-based settings UI for the admin page.

**Key Features**:
- Module enable/disable toggles for all available modules
- General settings (title separator, delete on uninstall)
- WooCommerce-specific settings (conditional, only when WooCommerce is active)
- Real-time validation and error handling
- Loading and saving states with user feedback
- REST API integration with nonce verification

**Settings Managed**:
- `enabled_modules` - Array of enabled module IDs
- `separator` - Title separator character (|, -, –, —, ·, •)
- `delete_on_uninstall` - Boolean flag for data deletion
- `woocommerce_exclude_out_of_stock` - WooCommerce-specific (conditional)

### 3. REST API Enhancements (`includes/class-rest-api.php`)

**Purpose**: Provides validated REST endpoints for settings management.

**Endpoints**:
- `GET /meowseo/v1/settings` - Retrieve all plugin settings
- `POST /meowseo/v1/settings` - Update plugin settings

**Security Features** (Requirements 15.2, 15.3):
- Nonce verification via `X-WP-Nonce` header
- `manage_options` capability check
- Comprehensive input validation
- Type checking and sanitization
- Enum validation for restricted values

**Validation Schema**:
- `enabled_modules` - Array of valid module IDs
- `separator` - Enum of allowed separator characters
- `default_social_image` - Integer (attachment ID)
- `delete_on_uninstall` - Boolean
- `has_regex_rules` - Boolean
- `woocommerce_exclude_out_of_stock` - Boolean (conditional)

### 4. Integration (`includes/class-plugin.php`)

**Changes**:
- Added `Admin` instance property
- Instantiates and boots `Admin` class in admin context only
- Ensures admin interface is only loaded when `is_admin()` is true

### 5. Entry Point (`src/index.js`)

**Changes**:
- Conditionally renders either Gutenberg sidebar or settings app
- Checks for `#meowseo-settings-root` element to determine context
- Shares same asset bundle for both editor and admin page

## Requirements Satisfied

### Requirement 2.3: Settings Validation
✅ Validates settings via REST API with nonce checks
✅ Type validation for all settings
✅ Enum validation for restricted values
✅ Array item validation for enabled modules
✅ Sanitization callbacks for all fields

### Requirement 2.4: Admin Menu and Settings Page
✅ Top-level admin menu page registered
✅ React-based settings UI rendered
✅ `meowseo-editor` asset handle loaded
✅ Settings data localized to JavaScript

### Requirement 2.5: WooCommerce-Specific Settings
✅ WooCommerce settings only shown when WooCommerce is active
✅ `woocommerce_exclude_out_of_stock` setting available
✅ Conditional schema validation for WooCommerce settings

### Requirement 15.2: Nonce Verification
✅ All mutation endpoints verify WordPress nonce
✅ Nonce passed via `X-WP-Nonce` header
✅ Invalid nonce returns 403 Forbidden

### Requirement 15.3: Capability Checks
✅ `manage_options` capability verified for all admin operations
✅ Settings page checks capability before rendering
✅ REST endpoints verify capability in permission callback

## Usage

### Admin Interface

1. Navigate to **MeowSEO** in the WordPress admin menu
2. Toggle modules on/off as needed
3. Configure general settings (separator, data deletion)
4. If WooCommerce is active, configure WooCommerce-specific settings
5. Click **Save Settings** to persist changes

### REST API

**Get Settings**:
```bash
curl -X GET \
  -H "X-WP-Nonce: {nonce}" \
  https://example.com/wp-json/meowseo/v1/settings
```

**Update Settings**:
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: {nonce}" \
  -d '{"enabled_modules":["meta","schema"],"separator":"|"}' \
  https://example.com/wp-json/meowseo/v1/settings
```

## File Structure

```
includes/
├── class-admin.php          # Admin interface class
├── class-plugin.php         # Updated with Admin integration
└── class-rest-api.php       # Enhanced with validation

src/
├── settings/
│   └── SettingsApp.js       # React settings UI
└── index.js                 # Updated entry point
```

## Testing Checklist

- [ ] Admin menu appears in WordPress admin
- [ ] Settings page loads without errors
- [ ] Module toggles work correctly
- [ ] Settings save successfully
- [ ] Nonce verification prevents unauthorized access
- [ ] Capability check prevents non-admin access
- [ ] WooCommerce settings appear only when WooCommerce is active
- [ ] Invalid settings are rejected with error messages
- [ ] Settings persist across page reloads

## Notes

- The settings UI shares the same asset bundle (`meowseo-editor`) as the Gutenberg sidebar for optimal performance
- All settings are stored in a single serialized array under `meowseo_options`
- Sensitive settings (e.g., GSC credentials) are excluded from REST API responses
- The admin interface only loads in admin context (`is_admin()`)
