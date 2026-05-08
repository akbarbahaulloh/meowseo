# Perbaikan AI Settings - MeowSEO

## 📋 Masalah yang Diperbaiki

### 1. ❌ API Key Langsung Disensor
**Masalah**: Saat menambahkan API key baru, key langsung disensor dengan `****` sehingga user tidak bisa melihat apa yang diketik.

**Solusi**: 
- API key baru ditampilkan penuh (tidak disensor) saat input
- Setelah disimpan, baru ditampilkan sebagian: `AIza...xyz1` (4 karakter awal + ... + 4 karakter akhir)
- Ditambahkan hint text untuk memberi tahu user bahwa key akan disembunyikan setelah disimpan

### 2. ❌ Model Version Tidak Ada Default
**Masalah**: Field "Model Version" kosong, user harus tahu model apa yang harus diisi untuk setiap provider.

**Solusi**:
- Ditambahkan default model untuk setiap provider:
  - **Gemini**: `gemini-3-flash-preview`
  - **OpenAI**: `gpt-4o`
  - **Anthropic**: `claude-3-5-sonnet-20241022`
  - **Imagen**: `imagen-4.0-generate-001`
  - **DALL-E**: `dall-e-3`
  - **DeepSeek**: `deepseek-chat`
  - **GLM**: `glm-4-plus`
  - **Qwen**: `qwen-max`
- Model auto-fill saat provider dipilih
- Ditampilkan hint text dengan default model di bawah input

### 3. ❌ Error "Pengecekan Kuki Gagal"
**Masalah**: Saat klik "Test Connection", muncul error "Nonce verification failed" (Pengecekan kuki gagal).

**Solusi**:
- Memperbaiki urutan prioritas pengambilan nonce di JavaScript
- Menambahkan `credentials: 'same-origin'` pada fetch request
- Menambahkan error handling yang lebih baik
- Menambahkan console warning jika nonce tidak ditemukan

---

## 🔧 Perubahan yang Dilakukan

### File 1: `includes/modules/ai/class-ai-settings.php`

#### Perubahan 1: Method `render_profile_item()`

**Sebelum**:
```php
// API key langsung disensor
$display_key = $profile['api_key'] ?? '';
if ( ! empty( $display_key ) && strpos( $display_key, '...' ) === false ) {
    $display_key = substr( $display_key, 0, 4 ) . '...' . substr( $display_key, -4 );
}
?>
<input type="password" name="..." value="<?php echo esc_attr( $display_key ); ?>">
```

**Sesudah**:
```php
// Default models untuk setiap provider
$default_models = array(
    'gemini'    => 'gemini-3-flash-preview',
    'openai'    => 'gpt-4o',
    'anthropic' => 'claude-3-5-sonnet-20241022',
    // ... dll
);

// Handle API key display
$api_key = $profile['api_key'] ?? '';
$is_encrypted = ! empty( $api_key ) && ( strpos( $api_key, '...' ) !== false || strlen( $api_key ) > 50 );

// If encrypted/saved, show partial key
if ( $is_encrypted ) {
    $display_key = substr( $api_key, 0, 4 ) . '...' . substr( $api_key, -4 );
} else {
    // Show full key for new input (not yet saved)
    $display_key = $api_key;
}
?>
<input type="text" name="..." value="<?php echo esc_attr( $display_key ); ?>" data-is-encrypted="<?php echo $is_encrypted ? '1' : '0'; ?>">
<p class="description">
    <?php if ( $is_encrypted ) : ?>
        Key tersimpan. Masukkan key baru untuk mengubah.
    <?php else : ?>
        Key akan disembunyikan setelah disimpan.
    <?php endif; ?>
</p>
```

**Keuntungan**:
- ✅ User bisa melihat key yang baru diinput
- ✅ Key tersimpan tetap aman (hanya tampil sebagian)
- ✅ Ada feedback yang jelas untuk user

#### Perubahan 2: Model Version dengan Default

**Ditambahkan**:
```php
<input type="text" name="..." value="<?php echo esc_attr( $current_model ); ?>" placeholder="<?php echo esc_attr( $default_model ); ?>">
<p class="description">
    Default: <code><?php echo esc_html( $default_model ); ?></code>
</p>
```

**Keuntungan**:
- ✅ User tahu model apa yang harus digunakan
- ✅ Auto-fill saat provider dipilih
- ✅ Hint text menampilkan default model

#### Perubahan 3: JavaScript Auto-fill Model

**Ditambahkan**:
```javascript
// Auto-fill model when provider changes
$(document).on('change', '.meowseo-provider-select', function() {
    var provider = $(this).val();
    var modelInput = $(this).closest('.meowseo-profile-item').find('.meowseo-model-input');
    
    if (modelInput.length && defaultModels[provider]) {
        var currentValue = modelInput.val().trim();
        if (!currentValue || Object.values(defaultModels).indexOf(currentValue) !== -1) {
            modelInput.val(defaultModels[provider]);
        }
    }
});

// Show API key on focus (for editing)
$(document).on('focus', '.meowseo-api-key-input', function() {
    var $input = $(this);
    var isEncrypted = $input.data('is-encrypted');
    
    if (isEncrypted && $input.val().indexOf('...') !== -1) {
        $input.val('');
        $input.attr('placeholder', 'Enter new API key to replace');
    }
});
```

**Keuntungan**:
- ✅ Model auto-fill saat ganti provider
- ✅ API key field dikosongkan saat focus (untuk edit)
- ✅ UX lebih baik

---

### File 2: `includes/modules/ai/assets/js/ai-settings.js`

#### Perubahan 1: Method `getNonce()`

**Sebelum**:
```javascript
getNonce: function() {
    // Try wp_nonce_field first
    let nonce = document.querySelector('input[name="_wpnonce"]');
    if (nonce) return nonce.value;
    
    // Try wp_localize_script
    if (typeof meowseoAISettings !== 'undefined' && meowseoAISettings.nonce) {
        return meowseoAISettings.nonce;
    }
    
    return '';
}
```

**Sesudah**:
```javascript
getNonce: function() {
    // Try wp_localize_script FIRST (highest priority)
    if (typeof meowseoAISettings !== 'undefined' && meowseoAISettings.nonce) {
        return meowseoAISettings.nonce;
    }

    // Try wp_nonce_field
    let nonce = document.querySelector('input[name="_wpnonce"]');
    if (nonce) return nonce.value;

    // Try REST API nonce meta tag
    const restNonce = document.querySelector('meta[name="wp-nonce"]');
    if (restNonce) return restNonce.getAttribute('content');

    // Try wpApiSettings (WordPress REST API)
    if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
        return wpApiSettings.nonce;
    }

    console.warn('MeowSEO AI Settings: No nonce found! Test connection may fail.');
    return '';
}
```

**Keuntungan**:
- ✅ Prioritas yang benar (wp_localize_script dulu)
- ✅ Lebih banyak fallback options
- ✅ Warning jika nonce tidak ditemukan

#### Perubahan 2: Method `testProviderConnection()`

**Ditambahkan**:
```javascript
fetch(this.config.testConnectionEndpoint, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.state.nonce,
    },
    credentials: 'same-origin', // PENTING untuk cookie-based auth
    body: JSON.stringify({...}),
})
.then((response) => {
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
})
```

**Keuntungan**:
- ✅ `credentials: 'same-origin'` memastikan cookie dikirim
- ✅ Error handling yang lebih baik
- ✅ Menampilkan HTTP status code di error message

---

## 📊 Daftar Model Gemini yang Tersedia

Berdasarkan data dari AI Studio, berikut model-model Gemini yang bisa digunakan:

### Text Generation Models

| Model | ID | Keterangan |
|-------|-----|------------|
| **Gemini 3 Flash Preview** | `gemini-3-flash-preview` | ⭐ **DEFAULT** - Most intelligent, fast, with search & grounding |
| Gemini 3.1 Pro Preview | `gemini-3.1-pro-preview` | SOTA reasoning, powerful multimodal |
| Gemini 3.1 Flash Lite Preview | `gemini-3.1-flash-lite-preview` | Most cost-efficient |
| Gemini 2.5 Pro | `gemini-2.5-pro` | Advanced reasoning, coding |
| Gemini 2.5 Flash | `gemini-2.5-flash` | Hybrid reasoning, 1M context |
| Gemini 2.5 Flash-Lite | `gemini-2.5-flash-lite` | Smallest, most cost effective |
| Gemini 2.0 Flash | `gemini-2.0-flash` | Second generation multimodal |
| Gemini 2.0 Flash-Lite | `gemini-2.0-flash-lite` | Second generation small |
| Gemini Pro Latest | `gemini-pro-latest` | Alias to latest Pro model |
| Gemini Flash Latest | `gemini-flash-latest` | Alias to latest Flash model |

### Image Generation Models

| Model | ID | Keterangan |
|-------|-----|------------|
| Gemini 3.1 Flash Image Preview | `gemini-3.1-flash-image-preview` | Pro-level visual intelligence |
| Gemini 3 Pro Image Preview | `gemini-3-pro-image-preview` | State-of-the-art image generation |
| Gemini 2.5 Flash Image | `gemini-2.5-flash-image` | Previous generation image model |

### Specialized Models

| Model | ID | Keterangan |
|-------|-----|------------|
| Deep Research Preview | `deep-research-preview-04-2026` | Long-running context gathering |
| Deep Research Max Preview | `deep-research-max-preview-04-2026` | Maximum search exhaustiveness |
| Gemini Robotics-ER 1.6 | `gemini-robotics-er-1.6-preview` | Embodied reasoning for robots |
| Gemini 3.1 Flash Live | `gemini-3.1-flash-live-preview` | Low-latency audio-to-audio |
| Gemini 3.1 Flash TTS | `gemini-3.1-flash-tts-preview` | Text-to-speech |

### Pricing (per 1M tokens)

| Model | Input | Output |
|-------|-------|--------|
| Gemini 3 Flash | $0.50 | $3.00 |
| Gemini 3.1 Pro | $2.00 | $12.00 |
| Gemini 3.1 Flash Lite | $0.25 | $1.50 |
| Gemini 2.5 Pro | $1.25 | $10.00 |
| Gemini 2.5 Flash | $0.30 | $2.50 |

---

## 🧪 Cara Testing

### 1. Test API Key Display

1. Buka **Settings > AI**
2. Klik **"Add New AI Profile"**
3. Pilih provider: **Google Gemini**
4. Masukkan API key: `AIzaSyABCDEFGHIJKLMNOPQRSTUVWXYZ1234567`
5. **Verifikasi**: Key terlihat penuh saat diketik ✅
6. Klik **"Save Changes"**
7. Refresh halaman
8. **Verifikasi**: Key sekarang tampil sebagai `AIza...4567` ✅

### 2. Test Model Auto-fill

1. Buka **Settings > AI**
2. Klik **"Add New AI Profile"**
3. **Verifikasi**: Model default `gemini-3-flash-preview` sudah terisi ✅
4. Ganti provider ke **OpenAI**
5. **Verifikasi**: Model berubah ke `gpt-4o` ✅
6. Ganti provider ke **Anthropic**
7. **Verifikasi**: Model berubah ke `claude-3-5-sonnet-20241022` ✅

### 3. Test Connection

1. Buka **Settings > AI**
2. Tambah profile dengan API key valid
3. Klik **"Test Connection"**
4. **Verifikasi**: Tidak ada error "Pengecekan kuki gagal" ✅
5. **Verifikasi**: Muncul status "Connection successful" atau error yang jelas ✅

### 4. Test Edit API Key

1. Buka profile yang sudah tersimpan (key tampil `AIza...4567`)
2. Klik pada field API Key
3. **Verifikasi**: Field dikosongkan, placeholder berubah ✅
4. Masukkan key baru
5. Save
6. **Verifikasi**: Key baru tersimpan dan tampil sebagian ✅

---

## ✅ Checklist Verifikasi

- [ ] API key baru terlihat penuh saat input
- [ ] API key tersimpan tampil sebagian (4 char + ... + 4 char)
- [ ] Hint text muncul di bawah API key field
- [ ] Model default terisi otomatis
- [ ] Model berubah saat ganti provider
- [ ] Hint text menampilkan default model
- [ ] Test connection tidak error "Pengecekan kuki gagal"
- [ ] Test connection menampilkan status yang jelas
- [ ] Edit API key: field dikosongkan saat focus
- [ ] Console tidak ada error JavaScript

---

## 🎯 Hasil yang Diharapkan

### Before (❌)
```
API Key: ****...****  (langsung disensor, tidak bisa lihat)
Model Version: [empty]  (tidak tahu harus isi apa)
Test Connection: ❌ Pengecekan kuki gagal
```

### After (✅)
```
API Key: AIzaSyABCDEFGHIJKLMNOPQRSTUVWXYZ1234567  (terlihat penuh)
         ↓ (setelah save)
API Key: AIza...4567  (tersimpan aman)
         Hint: Key tersimpan. Masukkan key baru untuk mengubah.

Model Version: gemini-3-flash-preview  (auto-fill)
               Hint: Default: gemini-3-flash-preview

Test Connection: ✅ Connection successful
```

---

## 📝 Catatan Tambahan

### Keamanan API Key

- API key **tidak disimpan dalam plain text** di database
- API key **dienkripsi** menggunakan `AI_Provider_Manager::encrypt_key()`
- Hanya ditampilkan sebagian (4 + ... + 4) setelah disimpan
- Full key hanya terlihat saat input baru (sebelum save)

### Model Version

- Jika field kosong, akan menggunakan default model
- User bisa override dengan model lain jika perlu
- Model list bisa dilihat di dokumentasi provider

### Nonce Verification

- Menggunakan WordPress REST API nonce (`wp_rest`)
- Nonce dikirim via header `X-WP-Nonce`
- Fallback ke multiple sources jika tidak ditemukan
- Warning di console jika nonce tidak tersedia

---

**Status**: ✅ **SELESAI**

**Testing**: Siap untuk ditest di environment WordPress!
