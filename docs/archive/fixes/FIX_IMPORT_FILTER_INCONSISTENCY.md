# Fix: Import Filter Inconsistency

## Masalah

Pada halaman import meta SEO dari Yoast/RankMath ke MeowSEO, terdapat inkonsistensi dalam logika filtering status "Pending" dan "Imported":

### Gejala:
1. Ketika filter "Pending" dipilih, masih muncul item dengan status "Imported" (hijau)
2. Jumlah di filter "Imported" tidak sesuai dengan jumlah item yang sebenarnya sudah diimpor
3. Contoh: 60 item sudah diimpor, tapi filter "Imported" hanya menampilkan 2 item

### Akar Masalah:

Terdapat **inkonsistensi logika** di 3 tempat berbeda dalam kode:

1. **Method `get_counts_by_seo_status()`** - Menghitung "imported" hanya berdasarkan `_meowseo_title`
2. **Method `column_seo_status()`** - Menampilkan status "Imported" jika ada `_meowseo_title` **ATAU** `_meowseo_description`
3. **Method `prepare_items()`** - Filter query hanya mengecek `_meowseo_title`

**Akibatnya:** Post yang hanya memiliki `_meowseo_description` (tanpa `_meowseo_title`) akan:
- ❌ Dihitung sebagai "pending" (karena tidak punya `_meowseo_title`)
- ✅ Ditampilkan sebagai "Imported" (karena punya `_meowseo_description`)
- ❌ Muncul di filter "pending" dengan label "Imported" (SALAH!)

## Solusi

Menyamakan logika di semua tempat agar konsisten: **Sebuah post/term dianggap "imported" jika memiliki `_meowseo_title` ATAU `_meowseo_description`**

### File yang Diubah:

#### 1. `includes/modules/import/class-import-posts-table.php`

**A. Method `get_counts_by_seo_status()` (baris ~72-93)**

Sebelum:
```php
// Imported count: posts that have _meowseo_title meta.
$imported = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_meowseo_title'
    WHERE p.post_type IN ($placeholders) AND p.post_status != 'auto-draft'",
    ...$post_types
) );
```

Sesudah:
```php
// Imported count: posts that have _meowseo_title OR _meowseo_description meta.
$imported = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type IN ($placeholders) 
    AND p.post_status != 'auto-draft'
    AND (pm.meta_key = '_meowseo_title' OR pm.meta_key = '_meowseo_description')",
    ...$post_types
) );
```

**B. Method `prepare_items()` - Filter Query (baris ~153-162)**

Sebelum:
```php
if ( 'pending' === $status ) {
    $args['meta_query'] = array(
        array(
            'key'     => '_meowseo_title',
            'compare' => 'NOT EXISTS',
        ),
    );
} elseif ( 'imported' === $status ) {
    $args['meta_query'] = array(
        array(
            'key'     => '_meowseo_title',
            'compare' => 'EXISTS',
        ),
    );
}
```

Sesudah:
```php
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

#### 2. `includes/modules/import/class-import-terms-table.php`

**A. Method `get_term_counts_by_seo_status()` (baris ~64-88)**

Sebelum:
```php
// Imported count: terms that have _meowseo_title termmeta.
$imported = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT tt.term_id)
    FROM {$wpdb->term_taxonomy} tt
    INNER JOIN {$wpdb->termmeta} tm ON tt.term_id = tm.term_id AND tm.meta_key = '_meowseo_title'
    WHERE tt.taxonomy IN ($in_tax)"
);
```

Sesudah:
```php
// Imported count: terms that have _meowseo_title OR _meowseo_description termmeta.
$imported = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT tt.term_id)
    FROM {$wpdb->term_taxonomy} tt
    INNER JOIN {$wpdb->termmeta} tm ON tt.term_id = tm.term_id
    WHERE tt.taxonomy IN ($in_tax)
    AND (tm.meta_key = '_meowseo_title' OR tm.meta_key = '_meowseo_description')"
);
```

**B. Method `prepare_items()` - Filter Query (baris ~157-168)**

Perubahan sama seperti pada file posts table (menambahkan relation 'AND'/'OR' dan mengecek kedua meta key).

## Hasil Setelah Fix

✅ Filter "Pending" hanya menampilkan item yang **belum** memiliki `_meowseo_title` DAN `_meowseo_description`

✅ Filter "Imported" menampilkan item yang memiliki `_meowseo_title` ATAU `_meowseo_description`

✅ Jumlah count di tab filter sesuai dengan jumlah item yang ditampilkan

✅ Status "Imported" (hijau) tidak akan muncul di filter "Pending"

## Testing

Untuk memverifikasi fix ini:

1. Buka halaman import: `wp-admin/admin.php?page=meowseo-import&tab=posts`
2. Klik filter "All" - lihat total item
3. Klik filter "Pending" - pastikan hanya item dengan status "Pending" (abu-abu) yang muncul
4. Klik filter "Imported" - pastikan hanya item dengan status "Imported" (hijau) yang muncul
5. Verifikasi jumlah di tab sesuai: All = Pending + Imported

## Catatan

Method `column_seo_status()` **tidak perlu diubah** karena logikanya sudah benar:
```php
$is_imported = \get_post_meta( $item->ID, '_meowseo_title', true ) 
            || \get_post_meta( $item->ID, '_meowseo_description', true );
```

Ini sudah mengecek kedua meta key dengan operator OR, yang sesuai dengan definisi "imported" yang baru.
