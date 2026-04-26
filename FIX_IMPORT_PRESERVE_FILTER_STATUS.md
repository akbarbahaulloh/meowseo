# Fix: Preserve Filter Status After Import

## Masalah

Ketika melakukan bulk import pada halaman import SEO:
1. User memilih filter "Pending"
2. User mencentang beberapa post
3. User klik "Import from Yoast SEO" atau "Import from RankMath"
4. Halaman refresh dan **kembali ke filter "All"** ❌

**Harapan:** Halaman seharusnya kembali ke filter yang sama (dalam contoh ini "Pending") setelah import selesai.

## Akar Masalah

Di file `includes/modules/import/class-import-admin.php`, method `process_bulk_actions()` melakukan redirect setelah import berhasil, tetapi **tidak menyertakan parameter `status`** dalam URL redirect.

### Kode Sebelum Fix:

```php
$redirect = \add_query_arg( array(
    'page'        => 'meowseo-import',
    'tab'         => $tab,
    'imported'    => $result['imported'],
    'errors'      => $result['errors'],
    'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
    'notice_type' => 'posts',
), \admin_url( 'admin.php' ) );
```

Parameter `status` (yang ada di `$_GET['status']`) tidak disertakan, sehingga setelah redirect, filter kembali ke default "all".

## Solusi

Menambahkan logika untuk **preserve parameter `status`** dari URL sebelumnya ke URL redirect.

### File yang Diubah:

**`includes/modules/import/class-import-admin.php`**

#### 1. Handle Posts & Media Import (baris ~57-88)

**Sebelum:**
```php
if ( $importer ) {
    $post_ids = array_map( 'intval', $_POST['post'] );
    $result   = $importer->import_postmeta( $post_ids );
    $redirect = \add_query_arg( array(
        'page'        => 'meowseo-import',
        'tab'         => $tab,
        'imported'    => $result['imported'],
        'errors'      => $result['errors'],
        'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
        'notice_type' => 'posts',
    ), \admin_url( 'admin.php' ) );
    \wp_safe_redirect( $redirect );
    exit;
}
```

**Sesudah:**
```php
if ( $importer ) {
    $post_ids = array_map( 'intval', $_POST['post'] );
    $result   = $importer->import_postmeta( $post_ids );
    
    // Preserve the current status filter.
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
    \wp_safe_redirect( $redirect );
    exit;
}
```

#### 2. Handle Terms Import (baris ~92-123)

Perubahan yang sama diterapkan untuk import terms.

**Sesudah:**
```php
if ( $importer ) {
    $term_ids = array_map( 'intval', $_POST['term'] );
    $result   = $importer->import_termmeta( $term_ids );
    
    // Preserve the current status filter.
    $redirect_args = array(
        'page'        => 'meowseo-import',
        'tab'         => 'terms',
        'imported'    => $result['imported'],
        'errors'      => $result['errors'],
        'plugin_name' => rawurlencode( $importer->get_plugin_name() ),
        'notice_type' => 'terms',
    );
    
    // Add status parameter if it was set.
    if ( isset( $_GET['status'] ) ) {
        $redirect_args['status'] = \sanitize_text_field( $_GET['status'] );
    }
    
    $redirect = \add_query_arg( $redirect_args, \admin_url( 'admin.php' ) );
    \wp_safe_redirect( $redirect );
    exit;
}
```

## Hasil Setelah Fix

✅ Setelah import dari filter "Pending", halaman kembali ke filter "Pending"

✅ Setelah import dari filter "Imported", halaman kembali ke filter "Imported"

✅ Setelah import dari filter "All", halaman kembali ke filter "All"

✅ User experience lebih baik karena tidak perlu klik filter lagi setelah import

## Testing

### Skenario 1: Import dari Filter Pending
1. Buka halaman import: `wp-admin/admin.php?page=meowseo-import&tab=posts`
2. Klik filter "Pending"
3. Centang beberapa post
4. Pilih "Import from Yoast SEO" dari dropdown Bulk Actions
5. Klik "Apply"
6. **Verifikasi:** Halaman refresh dan masih di filter "Pending" ✅

### Skenario 2: Import dari Filter Imported
1. Klik filter "Imported"
2. Centang beberapa post (untuk re-import)
3. Pilih "Import from RankMath" dari dropdown Bulk Actions
4. Klik "Apply"
5. **Verifikasi:** Halaman refresh dan masih di filter "Imported" ✅

### Skenario 3: Import dari Filter All
1. Klik filter "All"
2. Centang beberapa post
3. Import
4. **Verifikasi:** Halaman refresh dan masih di filter "All" ✅

### Skenario 4: Import Terms
1. Buka tab "Categories & Tags"
2. Klik filter "Pending"
3. Centang beberapa terms
4. Import
5. **Verifikasi:** Halaman refresh dan masih di filter "Pending" ✅

## Catatan Teknis

- Parameter `status` diambil dari `$_GET['status']` (bukan `$_POST`) karena form action menggunakan method POST, tapi filter status ada di URL (GET parameter)
- Sanitasi dilakukan dengan `\sanitize_text_field()` untuk keamanan
- Jika `status` tidak ada di URL (user di filter "All" atau default), maka parameter tidak ditambahkan, sehingga tetap default ke "all"
