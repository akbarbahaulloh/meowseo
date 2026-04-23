# Summary: Penambahan Dropdown Model Gemini

## Masalah
User harus mengisi model version secara manual dengan mengetik, yang merepotkan karena banyak pilihan model Gemini yang tersedia.

## Solusi
Menambahkan dropdown select khusus untuk provider Google Gemini yang berisi 11 model pilihan yang relevan untuk text generation.

## Implementasi

### 1. PHP Backend (class-ai-settings.php)
**Fungsi `render_profile_item()`** - Baris 735-760:
```php
<?php if ( $provider === 'gemini' ) : ?>
    <select name="ai_profiles[...][model]" class="meowseo-model-select">
        <option value="gemini-3-flash-preview">Gemini 3 Flash Preview (Latest)</option>
        <!-- 10 model lainnya -->
    </select>
<?php else : ?>
    <input type="text" name="ai_profiles[...][model]" class="meowseo-model-input">
<?php endif; ?>
```

### 2. JavaScript Dynamic Switching (class-ai-settings.php)
**Event handler** - Baris 590-650:
- Mendeteksi perubahan provider
- Jika Gemini: Replace input → dropdown
- Jika bukan Gemini: Replace dropdown → input
- Update default value sesuai provider

### 3. Model Gemini yang Tersedia
**Latest (Recommended):**
- gemini-3-flash-preview
- gemini-3.1-pro-preview  
- gemini-3.1-flash-lite-preview

**Stable:**
- gemini-2.5-pro
- gemini-2.5-flash
- gemini-2.5-flash-lite
- gemini-2.0-flash
- gemini-2.0-flash-lite

**Alias (Auto-update):**
- gemini-pro-latest
- gemini-flash-latest
- gemini-flash-lite-latest

## Fitur
✅ Dropdown otomatis untuk Gemini
✅ Input text untuk provider lain (OpenAI, Anthropic, dll)
✅ Dynamic switching saat ganti provider
✅ Backward compatible dengan profile lama
✅ Default value per provider

## Testing Checklist
- [ ] Buka Settings > AI
- [ ] Tambah profile baru
- [ ] Pilih "Google Gemini" → Harus muncul dropdown
- [ ] Pilih model dari dropdown
- [ ] Ganti ke "OpenAI" → Harus berubah ke input text
- [ ] Ganti kembali ke "Google Gemini" → Harus kembali ke dropdown
- [ ] Save settings
- [ ] Reload page → Model yang dipilih harus tersimpan

## File yang Diubah
1. `meowseo/includes/modules/ai/class-ai-settings.php`
   - Line 735-760: Conditional rendering dropdown/input
   - Line 590-650: JavaScript dynamic switching

## Tidak Ada Breaking Changes
- Profile lama tetap berfungsi
- Model custom tetap bisa digunakan
- Provider lain tidak terpengaruh
- CSS sudah support select element
