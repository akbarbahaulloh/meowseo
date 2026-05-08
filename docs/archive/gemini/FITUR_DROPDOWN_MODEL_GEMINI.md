# 🎯 Fitur Baru: Dropdown Model Gemini

## Apa yang Berubah?

Sebelumnya, ketika mengatur API key Gemini, Anda harus **mengetik manual** nama model seperti `gemini-3-flash-preview`. Sekarang, untuk provider **Google Gemini**, field Model Version otomatis berubah menjadi **dropdown pilihan** yang mudah!

## Cara Menggunakan

### 1. Buka Settings AI
Pergi ke **WordPress Admin** → **MeowSEO** → **Settings** → **Tab AI**

### 2. Tambah atau Edit Profile
- Klik **"Add New AI Profile"** untuk profile baru
- Atau edit profile Gemini yang sudah ada

### 3. Pilih Provider "Google Gemini"
Ketika Anda memilih **"Google Gemini"** dari dropdown AI Provider, field **Model Version** akan otomatis berubah menjadi dropdown dengan 11 pilihan model.

### 4. Pilih Model dari Dropdown
Pilih model yang sesuai kebutuhan Anda:

#### 🚀 Latest Models (Recommended)
- **Gemini 3 Flash Preview** - Model terbaru, paling cepat
- **Gemini 3.1 Pro Preview** - SOTA reasoning, paling pintar
- **Gemini 3.1 Flash Lite Preview** - Paling hemat biaya

#### ⚡ Stable Models
- **Gemini 2.5 Pro** - Advanced reasoning
- **Gemini 2.5 Flash** - Hybrid reasoning, 1M token context
- **Gemini 2.5 Flash-Lite** - Kecil dan efisien
- **Gemini 2.0 Flash** - Second generation
- **Gemini 2.0 Flash-Lite** - Second gen small

#### 🔄 Alias Models (Auto-update)
- **Gemini Pro Latest** - Selalu update ke Pro terbaru
- **Gemini Flash Latest** - Selalu update ke Flash terbaru
- **Gemini Flash-Lite Latest** - Selalu update ke Flash-Lite terbaru

## Keuntungan

✅ **Tidak perlu menghafal** nama model yang panjang
✅ **Tidak ada typo** karena pilih dari dropdown
✅ **Lihat semua pilihan** model yang tersedia
✅ **Deskripsi jelas** untuk setiap model
✅ **Tetap bisa custom** untuk provider lain

## Provider Lain

Untuk provider selain Gemini (OpenAI, Anthropic, DeepSeek, dll), field Model Version tetap menggunakan **input text** seperti biasa, karena mereka punya model yang lebih sedikit dan lebih stabil.

## Dynamic Switching

Fitur ini **dinamis**! Jika Anda:
1. Pilih "Google Gemini" → Muncul dropdown
2. Ganti ke "OpenAI" → Berubah jadi input text
3. Ganti lagi ke "Google Gemini" → Kembali jadi dropdown

Semua otomatis! 🎉

## Backward Compatible

Profile lama yang sudah ada **tetap berfungsi normal**. Jika Anda punya model custom yang tidak ada di dropdown, model tersebut tetap akan digunakan.

## Tips Memilih Model

### Untuk Kecepatan & Biaya Rendah
→ Pilih **Gemini 3 Flash Preview** atau **Gemini 3.1 Flash Lite Preview**

### Untuk Kualitas Maksimal
→ Pilih **Gemini 3.1 Pro Preview**

### Untuk Selalu Update
→ Pilih **Gemini Flash Latest** (alias)

### Untuk Production Stable
→ Pilih **Gemini 2.5 Flash** atau **Gemini 2.5 Pro**

## FAQ

**Q: Apakah profile lama saya akan rusak?**
A: Tidak! Profile lama tetap berfungsi normal.

**Q: Bagaimana jika saya ingin model yang tidak ada di dropdown?**
A: Anda bisa edit manual di database atau gunakan provider lain dengan input text.

**Q: Apakah semua provider akan dapat dropdown?**
A: Saat ini hanya Gemini karena mereka punya banyak model. Provider lain tetap input text.

**Q: Model mana yang paling recommended?**
A: **Gemini 3 Flash Preview** untuk balance antara speed, quality, dan cost.

**Q: Apa bedanya model "Latest" dengan yang lain?**
A: Model "Latest" adalah alias yang otomatis update ke versi terbaru dari Google.

## Screenshot Lokasi

```
WordPress Admin
└── MeowSEO
    └── Settings
        └── AI Tab
            └── AI Profiles Section
                └── Add New AI Profile
                    ├── Profile Label: [input text]
                    ├── AI Provider: [dropdown] → Pilih "Google Gemini"
                    ├── API Key: [input text]
                    └── Model Version: [dropdown] ← FITUR BARU! 🎉
```

## Support

Jika ada masalah atau pertanyaan, silakan buka issue di repository atau hubungi developer.

---

**Dibuat:** April 2026
**Versi:** MeowSEO 1.0.0+
**Developer:** MeowSEO Team
