# Testing Guide: Gemini Model Dropdown

## Pre-requisites
- WordPress installation dengan MeowSEO plugin aktif
- Akses ke WordPress Admin
- Browser dengan JavaScript enabled
- (Optional) Gemini API key untuk test connection

## Test Cases

### ✅ Test 1: Initial Render - Gemini Profile
**Objective:** Verify dropdown renders for existing Gemini profiles

**Steps:**
1. Login ke WordPress Admin
2. Navigate to MeowSEO → Settings → AI tab
3. Jika sudah ada profile Gemini, lihat field "Model Version"

**Expected Result:**
- Field "Model Version" adalah dropdown (select)
- Dropdown berisi 11 pilihan model
- Model yang tersimpan terpilih (selected)

**Pass Criteria:** ✅ Dropdown muncul dengan model yang benar

---

### ✅ Test 2: Initial Render - Non-Gemini Profile
**Objective:** Verify input text renders for non-Gemini providers

**Steps:**
1. Lihat profile dengan provider OpenAI, Anthropic, atau lainnya
2. Perhatikan field "Model Version"

**Expected Result:**
- Field "Model Version" adalah input text
- Placeholder menunjukkan default model
- Value adalah model yang tersimpan

**Pass Criteria:** ✅ Input text muncul dengan placeholder yang benar

---

### ✅ Test 3: Add New Profile - Gemini
**Objective:** Verify dropdown works for new profiles

**Steps:**
1. Klik button "Add New AI Profile"
2. Isi "Profile Label" (e.g., "My Gemini")
3. Pilih "AI Provider" → "Google Gemini"
4. Perhatikan field "Model Version"

**Expected Result:**
- Field "Model Version" adalah dropdown
- Default value adalah "gemini-3-flash-preview"
- Semua 11 model tersedia

**Pass Criteria:** ✅ Dropdown muncul dengan default value

---

### ✅ Test 4: Dynamic Switch - Gemini to OpenAI
**Objective:** Verify field switches from dropdown to input

**Steps:**
1. Buat profile baru atau edit existing
2. Pilih "AI Provider" → "Google Gemini"
3. Verify dropdown muncul
4. Ganti "AI Provider" → "OpenAI"
5. Perhatikan field "Model Version"

**Expected Result:**
- Field berubah dari dropdown ke input text
- Value berubah ke default OpenAI: "gpt-4o"
- Placeholder menunjukkan "gpt-4o"

**Pass Criteria:** ✅ Field berubah dengan smooth, value update

---

### ✅ Test 5: Dynamic Switch - OpenAI to Gemini
**Objective:** Verify field switches from input to dropdown

**Steps:**
1. Buat profile baru atau edit existing
2. Pilih "AI Provider" → "OpenAI"
3. Verify input text muncul
4. Ganti "AI Provider" → "Google Gemini"
5. Perhatikan field "Model Version"

**Expected Result:**
- Field berubah dari input text ke dropdown
- Value berubah ke default Gemini: "gemini-3-flash-preview"
- Dropdown berisi 11 model

**Pass Criteria:** ✅ Field berubah dengan smooth, dropdown populated

---

### ✅ Test 6: Save Profile - Gemini with Dropdown
**Objective:** Verify selected model saves correctly

**Steps:**
1. Buat profile baru dengan provider "Google Gemini"
2. Isi Profile Label dan API Key
3. Pilih model dari dropdown (e.g., "Gemini 3.1 Pro Preview")
4. Check "Active"
5. Klik "Save Changes"
6. Reload page

**Expected Result:**
- Settings saved successfully
- Profile muncul dengan model yang dipilih
- Dropdown menunjukkan model yang tersimpan (selected)

**Pass Criteria:** ✅ Model tersimpan dan terpilih setelah reload

---

### ✅ Test 7: Multiple Profiles - Mixed Providers
**Objective:** Verify multiple profiles work correctly

**Steps:**
1. Buat 3 profiles:
   - Profile 1: Google Gemini
   - Profile 2: OpenAI
   - Profile 3: Google Gemini
2. Verify field types untuk masing-masing
3. Save all profiles
4. Reload page

**Expected Result:**
- Profile 1: Dropdown dengan Gemini models
- Profile 2: Input text dengan OpenAI model
- Profile 3: Dropdown dengan Gemini models
- Semua tersimpan dengan benar

**Pass Criteria:** ✅ Semua profiles render dengan field type yang benar

---

### ✅ Test 8: Model Selection - All Options
**Objective:** Verify all 11 models can be selected

**Steps:**
1. Buat profile Gemini baru
2. Klik dropdown "Model Version"
3. Verify semua 11 model muncul:
   - Gemini 3 Flash Preview (Latest)
   - Gemini 3.1 Pro Preview
   - Gemini 3.1 Flash Lite Preview
   - Gemini 2.5 Pro
   - Gemini Pro Latest (Alias)
   - Gemini Flash Latest (Alias)
   - Gemini Flash-Lite Latest (Alias)
   - Gemini 2.5 Flash
   - Gemini 2.5 Flash-Lite
   - Gemini 2.0 Flash
   - Gemini 2.0 Flash-Lite
4. Pilih masing-masing model dan save

**Expected Result:**
- Semua 11 model tersedia
- Setiap model bisa dipilih
- Model tersimpan dengan benar

**Pass Criteria:** ✅ Semua model bisa dipilih dan tersimpan

---

### ✅ Test 9: Backward Compatibility - Old Profile
**Objective:** Verify old profiles still work

**Steps:**
1. Jika ada profile lama dengan model custom (e.g., "gemini-1.5-pro")
2. Load settings page
3. Perhatikan field "Model Version"

**Expected Result:**
- Dropdown muncul untuk Gemini
- Model custom tidak ada di dropdown
- Tapi value tetap tersimpan (bisa dilihat di database)

**Pass Criteria:** ✅ Profile lama tidak rusak, masih berfungsi

---

### ✅ Test 10: JavaScript Console - No Errors
**Objective:** Verify no JavaScript errors

**Steps:**
1. Buka browser DevTools (F12)
2. Go to Console tab
3. Load settings page
4. Perform actions: add profile, switch provider, save
5. Monitor console for errors

**Expected Result:**
- No JavaScript errors
- No warnings (or only WordPress standard warnings)
- All actions work smoothly

**Pass Criteria:** ✅ Console bersih, no critical errors

---

### ✅ Test 11: Form Submission - Data Integrity
**Objective:** Verify form data submits correctly

**Steps:**
1. Buat profile Gemini dengan model "gemini-3.1-pro-preview"
2. Buka browser DevTools → Network tab
3. Klik "Save Changes"
4. Inspect POST request payload

**Expected Result:**
- POST data contains: `ai_profiles[0][model] = gemini-3.1-pro-preview`
- Data structure correct
- No extra/missing fields

**Pass Criteria:** ✅ Form data structure benar

---

### ✅ Test 12: Test Connection - With Dropdown Model
**Objective:** Verify test connection works with selected model

**Steps:**
1. Buat profile Gemini dengan valid API key
2. Pilih model dari dropdown (e.g., "Gemini 3 Flash Preview")
3. Klik "Test Connection"
4. Wait for response

**Expected Result:**
- Connection test uses selected model
- Success message if API key valid
- Error message if API key invalid

**Pass Criteria:** ✅ Test connection works dengan model dari dropdown

---

## Browser Compatibility

Test di browser berikut:
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (if available)

## Mobile Responsive

Test di mobile view:
- [ ] Dropdown masih bisa diklik
- [ ] Options readable
- [ ] Form masih usable

## Performance Check

- [ ] Page load time < 2 seconds
- [ ] No lag saat switch provider
- [ ] Smooth dropdown interaction

## Regression Tests

Verify fitur lain tidak rusak:
- [ ] Provider status table masih update
- [ ] Test connection untuk provider lain masih works
- [ ] Generation settings masih berfungsi
- [ ] Image settings masih berfungsi

## Bug Report Template

Jika menemukan bug, gunakan template ini:

```
**Bug Title:** [Short description]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**


**Actual Result:**


**Browser:** [Chrome/Firefox/Safari]
**WordPress Version:** 
**MeowSEO Version:** 

**Screenshots:** [If applicable]

**Console Errors:** [If any]
```

## Test Results Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| Test 1: Initial Render - Gemini | ⬜ | |
| Test 2: Initial Render - Non-Gemini | ⬜ | |
| Test 3: Add New Profile - Gemini | ⬜ | |
| Test 4: Switch Gemini → OpenAI | ⬜ | |
| Test 5: Switch OpenAI → Gemini | ⬜ | |
| Test 6: Save Profile | ⬜ | |
| Test 7: Multiple Profiles | ⬜ | |
| Test 8: All Model Options | ⬜ | |
| Test 9: Backward Compatibility | ⬜ | |
| Test 10: No JS Errors | ⬜ | |
| Test 11: Form Data Integrity | ⬜ | |
| Test 12: Test Connection | ⬜ | |

**Legend:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

**Tester:** _______________
**Date:** _______________
**Build:** _______________
