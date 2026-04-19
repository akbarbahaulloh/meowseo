# MeowSEO — Classic Editor Compatibility Roadmap

> This document covers everything needed to bring the Classic Editor experience
> to full parity with the Gutenberg sidebar **and** with what Yoast SEO Premium
> and RankMath Pro offer in their own classic editor meta boxes.

---

## Current State

The classic editor meta box (`includes/modules/meta/class-classic-editor.php`)
currently renders a single flat form with **5 fields only:**

| Field | Input type | Saved to |
|---|---|---|
| SEO Title | `<input type="text">` | `_meowseo_title` |
| Meta Description | `<textarea>` | `_meowseo_description` |
| Focus Keyword | `<input type="text">` | `_meowseo_focus_keyword` |
| Canonical URL | `<input type="text">` | `_meowseo_canonical` |
| Robots | Two checkboxes | `_meowseo_robots_noindex`, `_meowseo_robots_nofollow` |

No tabs. No analysis. No social fields. No schema. No preview. No AI.

---

## What Yoast & RankMath Provide in Classic Editor

Both competitors render the **exact same feature set** in classic editor as in
Gutenberg — they just use a PHP-rendered tabbed meta box driven by JavaScript
instead of a React sidebar.

**Yoast SEO Premium classic editor meta box has:**
- Tabbed navigation: SEO | Readability | Social | Schema
- Real-time SERP preview (Google result simulation)
- Character counters on title (60 chars) and description (155 chars)
- All SEO analysis checks rendered live (via AJAX)
- All readability checks rendered live
- Open Graph fields + image picker
- Twitter Card fields + image picker
- Schema type selector + form

**RankMath Pro classic editor meta box has:**
- Tabbed navigation: General | Advanced | Social | Schema
- Real-time SERP preview
- Character counters
- Multiple focus keywords (up to 5)
- All SEO + readability analysis checks
- Open Graph + Twitter fields
- Schema type selector + JSON-LD form
- AI generation buttons (title, description)
- Internal link suggestions panel

---

## Gap: What Classic Editor Is Missing in MeowSEO

### Tab 1 — General (partially exists, incomplete)
| Feature | Status | Notes |
|---|---|---|
| SEO Title field | ✅ exists | Missing character counter |
| Meta Description field | ✅ exists | Missing character counter |
| Focus Keyword field | ✅ exists | Single keyword only |
| SERP Preview | ❌ missing | No visual Google result preview |
| Character counters | ❌ missing | No red/orange/green threshold feedback |
| Direct Answer field | ❌ missing | Exists in Gutenberg sidebar, not here |
| SEO analysis score | ❌ missing | The 11-check analysis engine never runs |
| Per-check analysis results | ❌ missing | Red/orange/green per check |
| Readability score | ❌ missing | The 5-check readability analysis never runs |
| Per-check readability results | ❌ missing | |
| AI generate title button | ❌ missing | AI module exists but not wired to classic editor |
| AI generate description button | ❌ missing | |
| Internal link suggestions | ❌ missing | REST endpoint exists, not wired up |

### Tab 2 — Social (entirely missing)
| Feature | Status | Notes |
|---|---|---|
| Open Graph title | ❌ missing | `_meowseo_og_title` postmeta registered, not exposed |
| Open Graph description | ❌ missing | `_meowseo_og_description` |
| Open Graph image | ❌ missing | `_meowseo_og_image_id` |
| OG image picker (media library) | ❌ missing | |
| Twitter Card title | ❌ missing | `_meowseo_twitter_title` |
| Twitter Card description | ❌ missing | `_meowseo_twitter_description` |
| Twitter Card image | ❌ missing | `_meowseo_twitter_image_id` |
| Use OG data for Twitter toggle | ❌ missing | `_meowseo_use_og_for_twitter` |
| Social preview simulation | ❌ missing | |

### Tab 3 — Schema (entirely missing)
| Feature | Status | Notes |
|---|---|---|
| Schema type selector | ❌ missing | `_meowseo_schema_type` postmeta registered, not exposed |
| Article schema fields | ❌ missing | |
| FAQ schema fields | ❌ missing | |
| HowTo schema fields | ❌ missing | |
| LocalBusiness schema fields | ❌ missing | |
| Product schema fields | ❌ missing | |
| Speakable toggle | ❌ missing | |

### Tab 4 — Advanced (partially exists, incomplete)
| Feature | Status | Notes |
|---|---|---|
| Canonical URL | ✅ exists | |
| Robots noindex | ✅ exists | |
| Robots nofollow | ✅ exists | |
| GSC submit button | ❌ missing | REST endpoint exists, not wired |
| GSC last submitted date | ❌ missing | `_meowseo_gsc_last_submit` not shown |

---

## Implementation Plan

### Architecture Decision

The classic editor does not load React or the WordPress block editor packages.
The meta box must be built with **PHP-rendered HTML + vanilla JavaScript**
(WordPress admin already loads jQuery — use it for AJAX calls).

All data operations must go through MeowSEO's existing REST API, which is
already fully implemented. No new PHP business logic is needed — only the UI.

```
Classic Editor Meta Box (PHP renders HTML)
        │
        ├── jQuery reads/writes fields
        │
        └── REST API calls (existing endpoints)
                ├── GET  /meowseo/v1/meta/{post_id}       → read saved meta
                ├── POST /meowseo/v1/meta/{post_id}       → save meta
                ├── GET  /meowseo/v1/analysis/{post_id}   → SEO analysis
                ├── POST /meowseo/v1/ai/generate          → AI generation
                └── POST /meowseo/v1/gsc/submit           → GSC submit
```

Because the form still submits via `save_post`, REST calls are used **only**
for live features (analysis, AI, GSC). Field values are saved the normal PHP
`$_POST` way on post save — same as today.

---

## Milestone 1 — Tab Structure + General Tab Complete

**Files to change:**
- `includes/modules/meta/class-classic-editor.php` — add tab nav + enqueue JS/CSS
- `assets/js/classic-editor.js` *(new)* — tab switching, character counters, SERP preview
- `assets/css/classic-editor.css` *(new)* — tab styles, preview styles

**Work items:**

#### 1.1 Tab Navigation
Replace the current flat form with a tabbed layout.

```html
<div id="meowseo-tabs">
  <nav>
    <button data-tab="general"   class="active">General</button>
    <button data-tab="social">Social</button>
    <button data-tab="schema">Schema</button>
    <button data-tab="advanced">Advanced</button>
  </nav>
  <div id="meowseo-tab-general">  ... </div>
  <div id="meowseo-tab-social">   ... </div>
  <div id="meowseo-tab-schema">   ... </div>
  <div id="meowseo-tab-advanced"> ... </div>
</div>
```

Tab switching is pure JS — show/hide `<div>` panels on button click.
Active tab stored in `localStorage` so it persists across page loads.

#### 1.2 Character Counters
On every keystroke in the title and description fields, update a counter `span`.
Apply CSS class `meowseo-ok` / `meowseo-warn` / `meowseo-bad` based on length.

| Field | OK | Warning | Bad |
|---|---|---|---|
| SEO Title | 30–60 chars | < 30 or 61–70 | > 70 |
| Meta Description | 120–155 chars | < 120 or 156–170 | > 170 |

#### 1.3 SERP Preview
A static HTML simulation of a Google result that updates in real time as the
user types in the title and description fields.

```html
<div class="meowseo-serp-preview">
  <div class="serp-url">example.com › slug</div>
  <div class="serp-title" id="serp-title-preview">Post title here</div>
  <div class="serp-desc"  id="serp-desc-preview">Description here...</div>
</div>
```

JS reads the title/description inputs on `input` event and updates the preview
spans. Truncates at 60/155 chars with ellipsis.

#### 1.4 Direct Answer Field
Add hidden `<input type="hidden" name="meowseo_direct_answer">` with a visible
textarea above the SERP preview. Save via `$_POST` in `save_meta()`.
Postmeta key: `_meowseo_direct_answer` (already registered in `Gutenberg_Assets`).

---

## Milestone 2 — SEO & Readability Analysis

**Files to change:**
- `assets/js/classic-editor.js` — add AJAX analysis trigger
- `includes/modules/meta/class-classic-editor.php` — render analysis placeholder HTML
- `includes/class-rest-api.php` — verify analysis endpoint accepts `post_id` param

**Work items:**

#### 2.1 Analysis Trigger
Fire analysis via REST after a 1-second debounce on any field change, or on an
explicit "Run Analysis" button click.

```js
function runAnalysis() {
  const postId = meowseoClassic.postId;
  jQuery.get(
    meowseoClassic.restUrl + '/analysis/' + postId,
    { nonce: meowseoClassic.nonce }
  ).done(function(data) {
    renderAnalysisResults(data);
  });
}
```

#### 2.2 Analysis Results Panel
Render each check as a row with colored dot:

```html
<div class="meowseo-check meowseo-good">
  <span class="dot"></span> Focus keyword in SEO title
</div>
<div class="meowseo-check meowseo-bad">
  <span class="dot"></span> Focus keyword in meta description
</div>
```

Show composite score (0–100) as a colored badge at the top of the panel.

#### 2.3 Readability Results Panel
Same approach as SEO analysis — render the 5 readability checks with
colored dots and composite score.

#### 2.4 Localize Script Data
In `enqueue_editor_scripts()`, call `wp_localize_script()` with:

```php
wp_localize_script( 'meowseo-classic-editor', 'meowseoClassic', [
    'postId'  => get_the_ID(),
    'nonce'   => wp_create_nonce( 'wp_rest' ),
    'restUrl' => rest_url( 'meowseo/v1' ),
] );
```

---

## Milestone 3 — Social Tab

**Files to change:**
- `includes/modules/meta/class-classic-editor.php` — render Social tab HTML + save fields
- `assets/js/classic-editor.js` — image picker via `wp.media`

**Work items:**

#### 3.1 Open Graph Fields

```html
<!-- Tab: Social → Facebook -->
<div class="meowseo-field">
  <label>OG Title</label>
  <input type="text" name="meowseo_og_title" value="<?php echo esc_attr($og_title); ?>" />
</div>
<div class="meowseo-field">
  <label>OG Description</label>
  <textarea name="meowseo_og_description"><?php echo esc_textarea($og_desc); ?></textarea>
</div>
<div class="meowseo-field">
  <label>OG Image</label>
  <input type="hidden" name="meowseo_og_image_id" value="<?php echo esc_attr($og_image_id); ?>" />
  <img id="meowseo-og-preview" src="..." style="max-width:200px" />
  <button type="button" id="meowseo-og-pick">Select Image</button>
  <button type="button" id="meowseo-og-remove">Remove</button>
</div>
```

#### 3.2 Twitter Card Fields
Same structure as OG. Add a "Use OG data for Twitter" checkbox — when checked,
disable the Twitter fields and copy OG values on save.

#### 3.3 Media Library Picker
Use `wp.media()` API (available in the classic editor admin) to open the
media library and write the selected attachment ID to the hidden input.
This requires `wp_enqueue_media()` in the PHP enqueue method.

#### 3.4 Save Social Fields in `save_meta()`
Add to the string fields map:
- `meowseo_og_title` → `_meowseo_og_title`
- `meowseo_og_description` → `_meowseo_og_description`
- `meowseo_og_image_id` → `_meowseo_og_image_id` (absint)
- `meowseo_twitter_title` → `_meowseo_twitter_title`
- `meowseo_twitter_description` → `_meowseo_twitter_description`
- `meowseo_twitter_image_id` → `_meowseo_twitter_image_id` (absint)
- `meowseo_use_og_for_twitter` → `_meowseo_use_og_for_twitter` (boolean)

---

## Milestone 4 — Schema Tab

**Files to change:**
- `includes/modules/meta/class-classic-editor.php` — schema type selector + conditional fields
- `assets/js/classic-editor.js` — show/hide schema field groups on type change

**Work items:**

#### 4.1 Schema Type Selector

```html
<div class="meowseo-field">
  <label>Schema Type</label>
  <select name="meowseo_schema_type" id="meowseo-schema-type">
    <option value="">None</option>
    <option value="Article">Article</option>
    <option value="FAQPage">FAQ Page</option>
    <option value="HowTo">HowTo</option>
    <option value="LocalBusiness">Local Business</option>
    <option value="Product">Product</option>
  </select>
</div>
```

#### 4.2 Conditional Field Groups
Each schema type has a `<div class="meowseo-schema-fields" data-type="Article">` block
that is hidden by default. JS shows the correct group when the select value changes.

**Article fields:** Article type (Article / NewsArticle / BlogPosting)

**FAQ fields:** Repeating question+answer pairs.
Store as JSON in `_meowseo_schema_config`. Add/remove pairs with JS.

**HowTo fields:** Name, description, repeating steps (name + text).
Store as JSON in `_meowseo_schema_config`.

**LocalBusiness fields:** Business name, type, address, phone, hours.

**Product fields:** Name, description, SKU, price, currency, availability.

#### 4.3 Save Schema Config
`meowseo_schema_config` → `_meowseo_schema_config`.
The value is already JSON-sanitized by `sanitize_schema_config()` in
`Gutenberg_Assets`. Call the same logic from `save_meta()` or use
`wp_json_encode(json_decode(...))`.

---

## Milestone 5 — Advanced Tab + AI + GSC

**Files to change:**
- `includes/modules/meta/class-classic-editor.php` — add Advanced tab fields
- `assets/js/classic-editor.js` — AI buttons + GSC submit

**Work items:**

#### 5.1 Advanced Tab — Missing Fields

Add to the existing canonical + robots fields:

```html
<!-- GSC Submit -->
<div class="meowseo-field">
  <button type="button" id="meowseo-gsc-submit">Submit to Google</button>
  <span id="meowseo-gsc-status">
    Last submitted: <?php echo esc_html($gsc_last_submit ?: 'Never'); ?>
  </span>
</div>
```

JS fires `POST /meowseo/v1/gsc/submit` with the post ID and updates the status span.

#### 5.2 AI Generation Buttons

Add buttons next to the SEO Title and Meta Description fields:

```html
<label>SEO Title
  <button type="button" class="meowseo-ai-btn" data-target="meowseo_title"
          data-action="title">✨ Generate</button>
</label>
<input type="text" name="meowseo_title" id="meowseo_title" value="..." />
```

On click, fire `POST /meowseo/v1/ai/generate` with `{ post_id, type: 'title' }`.
Write the response into the input and update the SERP preview.
Show a spinner while waiting. Disable the button during the request.

The AI module's REST endpoint is already implemented in
`includes/modules/ai/class-ai-rest.php` — no new PHP needed.

#### 5.3 Enqueue `wp_enqueue_media()`
Required for the image picker in the Social tab. Call it only on post edit screens.

```php
public function enqueue_editor_scripts( string $hook ): void {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'meowseo-classic-editor', ... );
    wp_enqueue_style(  'meowseo-classic-editor', ... );
    wp_localize_script( 'meowseo-classic-editor', 'meowseoClassic', [...] );
}
```

Register this via `add_action( 'admin_enqueue_scripts', ... )` in `Classic_Editor::init()`.

---

## File Structure After All Milestones

```
includes/modules/meta/
└── class-classic-editor.php      ← extended with tabs + all fields

assets/
├── js/
│   └── classic-editor.js         ← new: tab switching, counters, preview,
│                                     AJAX analysis, AJAX AI, media picker,
│                                     schema field toggling, GSC submit
└── css/
    └── classic-editor.css        ← new: tab nav, SERP preview, analysis dots,
                                      social image preview, schema forms
```

No new PHP modules. No new REST endpoints. All backend logic is already
in place — this is entirely a UI/UX build on top of the existing REST API.

---

## Milestone Summary

| Milestone | Deliverable | Effort |
|---|---|---|
| **M1** | Tabs + character counters + SERP preview + Direct Answer field | Small |
| **M2** | SEO & Readability analysis panel (AJAX) | Medium |
| **M3** | Social tab — OG + Twitter fields + image picker | Medium |
| **M4** | Schema tab — type selector + conditional field groups | Medium |
| **M5** | AI generation buttons + GSC submit button | Small |

---

## Parity Checklist (after all milestones)

| Feature | Gutenberg | Classic Editor (target) |
|---|---|---|
| SEO Title + counter | ✅ | ✅ M1 |
| Meta Description + counter | ✅ | ✅ M1 |
| SERP Preview | ✅ | ✅ M1 |
| Focus Keyword | ✅ | ✅ exists |
| Direct Answer | ✅ | ✅ M1 |
| SEO Analysis (11 checks) | ✅ | ✅ M2 |
| Readability Analysis (5 checks) | ✅ | ✅ M2 |
| OG Title / Description / Image | ✅ | ✅ M3 |
| Twitter Title / Description / Image | ✅ | ✅ M3 |
| Use OG for Twitter toggle | ✅ | ✅ M3 |
| Schema type selector | ✅ | ✅ M4 |
| Article schema fields | ✅ | ✅ M4 |
| FAQ schema fields | ✅ | ✅ M4 |
| HowTo schema fields | ✅ | ✅ M4 |
| LocalBusiness schema fields | ✅ | ✅ M4 |
| Product schema fields | ✅ | ✅ M4 |
| Canonical URL | ✅ | ✅ exists |
| Robots noindex / nofollow | ✅ | ✅ exists |
| AI Generate Title | ✅ | ✅ M5 |
| AI Generate Description | ✅ | ✅ M5 |
| GSC Submit button | ✅ | ✅ M5 |
