# Summary: Import Feature Fixes

Dokumen ini merangkum 2 bug fix yang telah dilakukan pada fitur Import SEO Data di MeowSEO.

---

## Fix #1: Filter Inconsistency (Imported Items Showing in Pending Filter)

### 🐛 Masalah
- Ketika filter "Pending" dipilih, masih muncul item dengan status "Imported" (hijau)
- Jumlah di filter "Imported" tidak sesuai dengan jumlah item yang sebenarnya sudah diimpor
- Contoh: 60 item sudah diimpor, tapi filter "Imported" hanya menampilkan 2 item

### 🔍 Akar Masalah
Inkonsistensi logika dalam menentukan status "Imported":
- **Counting & Filtering:** Hanya mengecek `_meowseo_title`
- **Display Status:** Mengecek `_meowseo_title` ATAU `_meowseo_description`

Akibatnya, post yang hanya punya `_meowseo_description` akan:
- Dihitung sebagai "pending" ❌
- Ditampilkan dengan label "Imported" ✅
- Muncul di filter "pending" dengan status hijau ❌

### ✅ Solusi
Menyamakan logika di semua tempat: **Item dianggap "imported" jika memiliki `_meowseo_title` ATAU `_meowseo_description`**

### 📝 File yang Diubah

#### `includes/modules/import/class-import-posts-table.php`

**1. Method `get_counts_by_seo_status()` - Query Counting**
```php
// BEFORE: Hanya mengecek _meowseo_title
$imported = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_meowseo_title'
    WHERE p.post_type IN ($placeholders) AND p.post_status != 'auto-draft'",
    ...$post_types
) );

// AFTER: Mengecek _meowseo_title OR _meowseo_description
$imported = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type IN ($placeholders) 
    AND p.post_status != 'auto-draft'
    AND (pm.meta_key = '_meowseo_title' OR pm.meta_key = '_meowseo_description')",
    ...$post_types
) );
```

**2. Method `prepare_items()` - Filter Query**
```php
// BEFORE: Hanya mengecek satu meta key
if ( 'pending' === $status ) {
    $args['meta_query'] = array(
        array(
            'key'     => '_meowseo_title',
            'compare' => 'NOT EXISTS',
        ),
    );
}

// AFTER: Mengecek kedua meta key dengan relation AND/OR
if ( 'pending' === $status ) {
    $args['meta_query'] = array(
        'relation' => 'AND',
        array(
            'key'     => '_meowseo_title',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => '_meowseo_description',
            'compare' => 'NOT EXISTS',
        ),
    );
} elseif ( 'imported' === $status ) {
    $args['meta_query'] = array(
        'relation' => 'OR',
        array(
            'key'     => '_meowseo_title',
            'compare' => 'EXISTS',
        ),
        array(
            'key'     => '_meowseo_description',
            'compare' => 'EXISTS',
        ),
    );
}
```

#### `includes/modules/import/class-import-terms-table.php`

Perubahan yang sama diterapkan untuk terms:
- Method `get_term_counts_by_seo_status()` - Query counting
- Method `prepare_items()` - Filter query

### 🎯 Hasil
✅ Filter "Pending" hanya menampilkan item yang benar-benar pending  
✅ Filter "Imported" menampilkan semua item yang sudah diimpor  
✅ Jumlah count sesuai dengan item yang ditampilkan  
✅ Tidak ada lagi item "Imported" (hijau) yang muncul di filter "Pending"

---

## Fix #2: Filter Status Not Preserved After Import

### 🐛 Masalah
Setelah melakukan bulk import:
1. User memilih filter "Pending"
2. User mencentang beberapa post dan import
3. Halaman refresh dan **kembali ke filter "All"** ❌

**Harapan:** Halaman seharusnya kembali ke filter yang sama setelah import.

### 🔍 Akar Masalah
Method `process_bulk_actions()` melakukan redirect setelah import, tetapi **tidak menyertakan parameter `status`** dalam URL redirect.

### ✅ Solusi
Menambahkan logika untuk preserve parameter `status` dari URL sebelumnya ke URL redirect.

### 📝 File yang Diubah

#### `includes/modules/import/class-import-admin.php`

**1. Handle Posts & Media Import**
```php
// BEFORE: Tidak ada parameter status
$redirect = \add_query_arg( array(
    'page'        => 'meowseo-import',
    'tab'         => $tab,
    'imported'    => $result['imported'],
    'errors'      => $result['errors'],
    'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
    'notice_type' => 'posts',
), \admin_url( 'admin.php' ) );

// AFTER: Preserve status parameter
$redirect_args = array(
    'page'        => 'meowseo-import',
    'tab'         => $tab,
    'imported'    => $result['imported'],
    'errors'      => $result['errors'],
    'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
    'notice_type' => 'posts',
);

// Add status parameter if it was set.
if ( isset( $_GET['status'] ) ) {
    $redirect_args['status'] = \sanitize_text_field( $_GET['status'] );
}

$redirect = \add_query_arg( $redirect_args, \admin_url( 'admin.php' ) );
```

**2. Handle Terms Import**

Perubahan yang sama diterapkan untuk import terms.

### 🎯 Hasil
✅ Setelah import dari filter "Pending", halaman kembali ke filter "Pending"  
✅ Setelah import dari filter "Imported", halaman kembali ke filter "Imported"  
✅ Setelah import dari filter "All", halaman kembali ke filter "All"  
✅ User experience lebih baik - tidak perlu klik filter lagi setelah import

---

## Testing Checklist

### Fix #1: Filter Inconsistency
- [ ] Buka halaman import posts
- [ ] Klik filter "All" - lihat total item
- [ ] Klik filter "Pending" - pastikan hanya item "Pending" (abu-abu) yang muncul
- [ ] Klik filter "Imported" - pastikan hanya item "Imported" (hijau) yang muncul
- [ ] Verifikasi: All = Pending + Imported
- [ ] Ulangi untuk tab "Media" dan "Categories & Tags"

### Fix #2: Preserve Filter Status
- [ ] Buka halaman import posts
- [ ] Klik filter "Pending"
- [ ] Centang beberapa post
- [ ] Import from Yoast SEO
- [ ] Verifikasi: Halaman masih di filter "Pending" setelah refresh
- [ ] Ulangi untuk filter "Imported" dan "All"
- [ ] Ulangi untuk tab "Media" dan "Categories & Tags"

---

## Files Modified

1. ✅ `includes/modules/import/class-import-posts-table.php`
   - Method `get_counts_by_seo_status()`
   - Method `prepare_items()`

2. ✅ `includes/modules/import/class-import-terms-table.php`
   - Method `get_term_counts_by_seo_status()`
   - Method `prepare_items()`

3. ✅ `includes/modules/import/class-import-admin.php`
   - Method `process_bulk_actions()` - Posts & Media section
   - Method `process_bulk_actions()` - Terms section

---

## Related Documentation

- `FIX_IMPORT_FILTER_INCONSISTENCY.md` - Detail lengkap Fix #1
- `FIX_IMPORT_PRESERVE_FILTER_STATUS.md` - Detail lengkap Fix #2

---

**Tanggal:** 2026-04-26  
**Status:** ✅ Completed  
**Impact:** Meningkatkan akurasi filtering dan user experience pada fitur Import SEO Data
