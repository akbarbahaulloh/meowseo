# Import System Manual Testing Checklist

## Prerequisites
- [ ] WordPress installation (5.0+)
- [ ] MeowSEO plugin installed and activated
- [ ] Test site with sample content

## Test 1: Yoast SEO Import

### Setup
- [ ] Install Yoast SEO Premium plugin
- [ ] Create 10 test posts with Yoast SEO data:
  - [ ] Custom titles
  - [ ] Custom descriptions
  - [ ] Focus keywords
  - [ ] Canonical URLs
  - [ ] Robots meta (noindex/nofollow)
  - [ ] Open Graph titles and descriptions
  - [ ] Twitter titles and descriptions
- [ ] Create 5 categories with Yoast termmeta
- [ ] Create 5 tags with Yoast termmeta
- [ ] Configure Yoast settings (separator, homepage title/description, title patterns)
- [ ] Create 3 redirects using Yoast redirect manager

### Import Process
- [ ] Navigate to MeowSEO > Import
- [ ] Verify Yoast SEO is detected
- [ ] Click "Import from Yoast SEO"
- [ ] Verify import starts successfully
- [ ] Monitor progress bar
- [ ] Wait for completion

### Verification
- [ ] Check import summary shows correct counts:
  - [ ] Posts imported: 10
  - [ ] Terms imported: 10
  - [ ] Options imported: >0
  - [ ] Redirects imported: 3
  - [ ] Errors: 0 (or acceptable number)

- [ ] Verify postmeta for sample post:
  - [ ] `_meowseo_title` matches Yoast title
  - [ ] `_meowseo_description` matches Yoast description
  - [ ] `_meowseo_focus_keyword` matches Yoast focus keyword
  - [ ] `_meowseo_canonical_url` matches Yoast canonical
  - [ ] `_meowseo_robots_noindex` matches Yoast noindex
  - [ ] `_meowseo_robots_nofollow` matches Yoast nofollow
  - [ ] `_meowseo_og_title` matches Yoast OG title
  - [ ] `_meowseo_og_description` matches Yoast OG description
  - [ ] `_meowseo_twitter_title` matches Yoast Twitter title
  - [ ] `_meowseo_twitter_description` matches Yoast Twitter description

- [ ] Verify termmeta for sample category:
  - [ ] `_meowseo_title` matches Yoast term title
  - [ ] `_meowseo_description` matches Yoast term description

- [ ] Verify options:
  - [ ] Separator matches
  - [ ] Homepage title matches
  - [ ] Homepage description matches
  - [ ] Title patterns imported

- [ ] Verify redirects:
  - [ ] All 3 redirects exist in MeowSEO
  - [ ] Source URLs match
  - [ ] Target URLs match
  - [ ] Redirect types match (301/302/307/410)

## Test 2: RankMath Import

### Setup
- [ ] Deactivate Yoast SEO
- [ ] Install RankMath Pro plugin
- [ ] Create 10 test posts with RankMath data:
  - [ ] Custom titles
  - [ ] Custom descriptions
  - [ ] Focus keywords (comma-separated: "keyword1, keyword2")
  - [ ] Canonical URLs
  - [ ] Robots meta (array with noindex/nofollow)
  - [ ] Facebook titles and descriptions
  - [ ] Twitter titles and descriptions
- [ ] Create 5 categories with RankMath termmeta
- [ ] Create 5 tags with RankMath termmeta
- [ ] Configure RankMath settings
- [ ] Create 3 redirects using RankMath redirect manager

### Import Process
- [ ] Navigate to MeowSEO > Import
- [ ] Verify RankMath is detected
- [ ] Click "Import from RankMath"
- [ ] Verify import starts successfully
- [ ] Monitor progress bar
- [ ] Wait for completion

### Verification
- [ ] Check import summary shows correct counts
- [ ] Verify postmeta for sample post (same checks as Yoast)
- [ ] **Special verification for RankMath**:
  - [ ] Focus keyword properly split from comma-separated string
  - [ ] Robots array properly split into noindex/nofollow booleans
- [ ] Verify termmeta for sample category
- [ ] Verify options
- [ ] Verify redirects from rank_math_redirections table

## Test 3: Error Handling

### Setup
- [ ] Create test post with invalid data:
  - [ ] Empty title
  - [ ] Invalid UTF-8 characters in description
  - [ ] Malformed URL in canonical
- [ ] Add Yoast postmeta to this post

### Import Process
- [ ] Run Yoast import
- [ ] Verify import completes (doesn't crash)

### Verification
- [ ] Check error log shows failed items
- [ ] Verify other posts still imported successfully
- [ ] Verify error messages are descriptive
- [ ] Verify error count in summary is accurate

## Test 4: Batch Processing

### Setup
- [ ] Create 500 test posts with Yoast data
- [ ] Use a script or bulk tool to speed up creation

### Import Process
- [ ] Run Yoast import
- [ ] Monitor progress bar updates
- [ ] Verify no PHP timeout errors
- [ ] Verify no memory exhaustion errors

### Verification
- [ ] All 500 posts imported successfully
- [ ] Progress bar showed accurate progress
- [ ] Import completed in reasonable time
- [ ] Sample posts have correct data

## Test 5: Import Cancellation

### Setup
- [ ] Create 200 test posts with Yoast data

### Import Process
- [ ] Start Yoast import
- [ ] Wait for progress to reach ~50%
- [ ] Click "Cancel Import" button
- [ ] Verify cancellation confirmation

### Verification
- [ ] Import status shows "cancelled"
- [ ] Progress saved at cancellation point
- [ ] No PHP errors
- [ ] Partial data imported correctly
- [ ] Can start new import after cancellation

## Test 6: Plugin Detection

### Test 6.1: No Plugins Installed
- [ ] Deactivate Yoast and RankMath
- [ ] Navigate to MeowSEO > Import
- [ ] Verify message: "No compatible plugins detected"

### Test 6.2: Yoast Only
- [ ] Activate Yoast SEO
- [ ] Navigate to MeowSEO > Import
- [ ] Verify Yoast SEO shown as available
- [ ] Verify RankMath not shown

### Test 6.3: RankMath Only
- [ ] Deactivate Yoast, activate RankMath
- [ ] Navigate to MeowSEO > Import
- [ ] Verify RankMath shown as available
- [ ] Verify Yoast not shown

### Test 6.4: Both Plugins
- [ ] Activate both Yoast and RankMath
- [ ] Navigate to MeowSEO > Import
- [ ] Verify both plugins shown as available
- [ ] Verify can import from either

## Test 7: Data Validation

### Setup
- [ ] Create test posts with various data types:
  - [ ] Valid URLs
  - [ ] Invalid URLs
  - [ ] Empty strings
  - [ ] Very long strings (>500 chars)
  - [ ] Special characters
  - [ ] HTML in meta fields
  - [ ] JavaScript in meta fields

### Import Process
- [ ] Run import
- [ ] Monitor for errors

### Verification
- [ ] Valid data imported correctly
- [ ] Invalid data rejected or sanitized
- [ ] No XSS vulnerabilities
- [ ] No SQL injection vulnerabilities
- [ ] HTML properly escaped
- [ ] JavaScript stripped or escaped

## Test 8: Large Dataset Performance

### Setup
- [ ] Create 5,000 test posts with Yoast data
- [ ] Create 100 categories with termmeta
- [ ] Create 100 tags with termmeta
- [ ] Create 50 redirects

### Import Process
- [ ] Run import
- [ ] Monitor server resources (CPU, memory)
- [ ] Time the import duration

### Verification
- [ ] Import completes successfully
- [ ] No timeouts
- [ ] No memory errors
- [ ] Reasonable performance (<5 minutes for 5k posts)
- [ ] All data imported correctly
- [ ] Server remains responsive during import

## Test 9: UI/UX

### Import Page
- [ ] Page loads quickly
- [ ] Layout is clean and intuitive
- [ ] Detected plugins clearly displayed
- [ ] Import buttons are prominent
- [ ] Help text is clear

### Progress Display
- [ ] Progress bar is visible
- [ ] Progress percentage updates
- [ ] Current phase displayed (posts/terms/options/redirects)
- [ ] Processed/total counts shown
- [ ] Cancel button is accessible

### Summary Display
- [ ] Summary is easy to read
- [ ] Counts are accurate
- [ ] Errors are clearly shown
- [ ] Error details are accessible
- [ ] Success message is clear
- [ ] Next steps are suggested

## Test 10: Edge Cases

### Test 10.1: Empty Database
- [ ] Fresh WordPress install
- [ ] No posts, no terms
- [ ] Run import
- [ ] Verify handles gracefully

### Test 10.2: Duplicate Import
- [ ] Run import once
- [ ] Run import again
- [ ] Verify handles duplicates correctly
- [ ] Verify no data corruption

### Test 10.3: Partial Data
- [ ] Posts with only some Yoast fields
- [ ] Posts with no Yoast data
- [ ] Mix of complete and partial data
- [ ] Verify imports what's available

### Test 10.4: Mixed Content
- [ ] Some posts with Yoast data
- [ ] Some posts with RankMath data
- [ ] Some posts with both
- [ ] Some posts with neither
- [ ] Verify correct handling

## Sign-off

### Test Results
- [ ] All tests passed
- [ ] Known issues documented
- [ ] Performance acceptable
- [ ] Security verified
- [ ] UX is intuitive

### Tester Information
- **Name**: _______________
- **Date**: _______________
- **Environment**: _______________
- **WordPress Version**: _______________
- **PHP Version**: _______________
- **Yoast Version**: _______________
- **RankMath Version**: _______________

### Notes
_Add any additional observations, issues, or recommendations here:_

---

### Approval
- [ ] Ready for production deployment
- [ ] Requires fixes before deployment

**Approved By**: _______________
**Date**: _______________

