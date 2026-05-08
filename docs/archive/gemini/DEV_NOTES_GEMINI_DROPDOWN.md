# Developer Notes: Gemini Model Dropdown Implementation

## Architecture Overview

### Component Structure
```
AI Settings Page
├── render_profile_item() [PHP]
│   ├── Conditional rendering based on $provider
│   ├── Gemini: <select> with 11 options
│   └── Others: <input type="text">
│
└── JavaScript Event Handler
    ├── Listen to .meowseo-provider-select change
    ├── Dynamic DOM manipulation
    └── Switch between <select> and <input>
```

## Code Locations

### PHP Backend
**File:** `meowseo/includes/modules/ai/class-ai-settings.php`

**Function:** `render_profile_item()`
- **Lines 735-760:** Conditional rendering logic
- **Logic:** `if ($provider === 'gemini')` → render select, else → render input

### JavaScript Frontend
**File:** `meowseo/includes/modules/ai/class-ai-settings.php` (inline script)

**Event Handler:** `.meowseo-provider-select change`
- **Lines 590-650:** Dynamic switching logic
- **Trigger:** When user changes provider dropdown
- **Action:** Replace input ↔ select based on provider

## Data Flow

```
User Action: Change Provider Dropdown
    ↓
JavaScript: Detect change event
    ↓
Check: provider === 'gemini' ?
    ↓
Yes → Replace <input> with <select> (geminiModelsHTML)
No  → Replace <select> with <input> (inputHTML)
    ↓
Update: Default model value
    ↓
Update: Description text
```

## Model List Definition

### PHP Array (Server-side rendering)
```php
$gemini_models = array(
    'gemini-3-flash-preview' => 'Gemini 3 Flash Preview (Latest)',
    'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview',
    // ... 9 more models
);
```

### JavaScript String (Client-side dynamic)
```javascript
var geminiModelsHTML = `
    <select name="ai_profiles[${index}][model]">
        <option value="gemini-3-flash-preview">Gemini 3 Flash Preview (Latest)</option>
        // ... 10 more options
    </select>
`;
```

**⚠️ Important:** Both lists must be kept in sync!

## Default Models Map

```javascript
var defaultModels = {
    'gemini': 'gemini-3-flash-preview',
    'openai': 'gpt-4o',
    'anthropic': 'claude-3-5-sonnet-20241022',
    'imagen': 'imagen-4.0-generate-001',
    'dalle': 'dall-e-3',
    'deepseek': 'deepseek-chat',
    'glm': 'glm-4-plus',
    'qwen': 'qwen-max'
};
```

## CSS Classes

### Existing (No changes needed)
- `.meowseo-profile-field` - Field container
- `.meowseo-provider-select` - Provider dropdown
- `.meowseo-model-input` - Model input (text)
- `.regular-text` - WordPress standard input width

### New
- `.meowseo-model-select` - Model dropdown (select)

**Note:** CSS already supports select elements, no additional styling needed.

## Edge Cases Handled

### 1. Profile with Custom Model
**Scenario:** User has `gemini-custom-model` in database
**Behavior:** Dropdown will not have this option, but value is preserved
**Solution:** User can switch to another provider and back to reset

### 2. New Profile Creation
**Scenario:** User clicks "Add New AI Profile"
**Behavior:** Template uses placeholder "INDEX" and "PROFILE_ID"
**Solution:** JavaScript replaces placeholders with actual values

### 3. Provider Switch Mid-Edit
**Scenario:** User changes provider before saving
**Behavior:** Model field switches type (input ↔ select)
**Solution:** JavaScript preserves or resets value based on provider

### 4. Backward Compatibility
**Scenario:** Existing profiles with old model names
**Behavior:** Old profiles load normally
**Solution:** Conditional rendering checks current provider

## Testing Scenarios

### Unit Tests (Manual)
1. ✅ Render Gemini profile → Should show select
2. ✅ Render OpenAI profile → Should show input
3. ✅ Switch Gemini → OpenAI → Should replace select with input
4. ✅ Switch OpenAI → Gemini → Should replace input with select
5. ✅ Save and reload → Should preserve selected model
6. ✅ Add new profile → Should work with template

### Integration Tests
1. ✅ PHP syntax check: `php -l class-ai-settings.php`
2. ✅ Load settings page → No JavaScript errors
3. ✅ Submit form → Data saves correctly
4. ✅ Test connection → Works with selected model

## Performance Considerations

### Server-side
- **Impact:** Minimal - Only adds conditional rendering
- **Cost:** ~11 extra `<option>` tags for Gemini profiles
- **Optimization:** Array is defined once per render

### Client-side
- **Impact:** Minimal - Event handler only fires on change
- **Cost:** ~500 bytes for geminiModelsHTML string
- **Optimization:** Template string is defined once

## Future Enhancements

### Potential Improvements
1. **Model Grouping:** Group models by category (Latest, Stable, Alias)
2. **Model Info:** Add tooltips with pricing and capabilities
3. **Dynamic Loading:** Fetch model list from API
4. **Other Providers:** Add dropdowns for OpenAI, Anthropic
5. **Model Search:** Add search/filter for large model lists

### API Integration
```javascript
// Future: Fetch models from Gemini API
fetch('https://generativelanguage.googleapis.com/v1/models')
    .then(response => response.json())
    .then(models => populateDropdown(models));
```

## Maintenance

### When to Update Model List

**Trigger:** Google releases new Gemini model

**Steps:**
1. Update PHP array in `render_profile_item()` (line ~740)
2. Update JavaScript string in event handler (line ~598)
3. Update default model if needed (line ~680)
4. Update documentation (CHANGELOG, FITUR, etc.)
5. Test all scenarios

**Files to Update:**
- `class-ai-settings.php` (2 locations)
- `CHANGELOG_GEMINI_MODEL_DROPDOWN.md`
- `FITUR_DROPDOWN_MODEL_GEMINI.md`

## Security Considerations

### Input Validation
- ✅ PHP: `esc_attr()` on all output
- ✅ PHP: `selected()` for option matching
- ✅ JS: Template literals with `${index}` (safe, numeric)
- ✅ Form: WordPress nonce validation (existing)

### XSS Prevention
- ✅ All user input is escaped
- ✅ No `eval()` or `innerHTML` with user data
- ✅ jQuery `.replaceWith()` with HTML string (safe)

## Debugging

### Common Issues

**Issue:** Dropdown not showing for Gemini
**Check:** `$provider === 'gemini'` condition
**Debug:** `var_dump($provider)` in PHP

**Issue:** JavaScript not switching
**Check:** Browser console for errors
**Debug:** `console.log(provider, index)` in JS

**Issue:** Model not saving
**Check:** Form name attribute matches
**Debug:** Check `$_POST['ai_profiles']` data

### Debug Mode
```php
// Add to render_profile_item() for debugging
error_log("Provider: $provider, Model: $current_model");
```

```javascript
// Add to event handler for debugging
console.log('Provider changed:', provider, 'Index:', index);
```

## Dependencies

### PHP
- WordPress 5.0+
- MeowSEO Options class
- MeowSEO AI_Provider_Manager class

### JavaScript
- jQuery (WordPress core)
- No external libraries

### CSS
- WordPress admin styles
- MeowSEO ai-settings.css (existing)

## Version History

**v1.0.0** (April 2026)
- Initial implementation
- 11 Gemini models
- Dynamic switching
- Backward compatible

---

**Last Updated:** April 23, 2026
**Maintainer:** MeowSEO Development Team
