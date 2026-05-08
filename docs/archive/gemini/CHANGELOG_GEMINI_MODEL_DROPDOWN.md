# Changelog: Gemini Model Dropdown

## Perubahan
Mengganti input text untuk Model Version menjadi dropdown select khusus untuk provider Google Gemini.

## Model Gemini yang Tersedia
Dropdown ini berisi model-model Gemini yang relevan untuk text generation:

### Latest Models (Recommended)
- **Gemini 3 Flash Preview** - Model terbaru dengan kecepatan tinggi
- **Gemini 3.1 Pro Preview** - Model SOTA dengan reasoning mendalam
- **Gemini 3.1 Flash Lite Preview** - Model paling cost-efficient

### Stable Models
- **Gemini 2.5 Pro** - Advanced reasoning model
- **Gemini 2.5 Flash** - Hybrid reasoning model dengan 1M token context
- **Gemini 2.5 Flash-Lite** - Model kecil dan cost-effective
- **Gemini 2.0 Flash** - Second generation multimodal model
- **Gemini 2.0 Flash-Lite** - Second generation small model

### Alias Models (Auto-update)
- **Gemini Pro Latest** - Alias ke model Pro terbaru
- **Gemini Flash Latest** - Alias ke model Flash terbaru
- **Gemini Flash-Lite Latest** - Alias ke model Flash-Lite terbaru

## Model yang Tidak Disertakan
Model-model berikut tidak disertakan karena bukan untuk text generation:
- Deep Research models (untuk research tasks)
- Image generation models (Gemini Flash Image, Pro Image)
- TTS models (Text-to-Speech)
- Live models (Audio-to-audio)
- Robotics models

## Fitur
1. **Dropdown otomatis untuk Gemini**: Ketika memilih provider "Google Gemini", field Model Version akan berubah menjadi dropdown
2. **Input text untuk provider lain**: Provider selain Gemini tetap menggunakan input text
3. **Dynamic switching**: Ketika mengganti provider, field model akan otomatis berubah antara dropdown dan input text
4. **Default value**: Setiap provider memiliki default model yang sesuai

## File yang Diubah
- `meowseo/includes/modules/ai/class-ai-settings.php`
  - Menambahkan conditional rendering untuk dropdown Gemini
  - Update JavaScript untuk handle dynamic switching

## Testing
1. Buka Settings > AI
2. Tambah profile baru
3. Pilih provider "Google Gemini" - harus muncul dropdown
4. Ganti ke provider lain (OpenAI, Anthropic, dll) - harus berubah ke input text
5. Ganti kembali ke Gemini - harus kembali ke dropdown

## Kompatibilitas
- Backward compatible dengan profile yang sudah ada
- Model yang tidak ada di dropdown tetap bisa digunakan (jika user punya profile lama)
- Provider lain tidak terpengaruh
