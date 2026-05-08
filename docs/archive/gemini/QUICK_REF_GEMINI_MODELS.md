# Quick Reference: Gemini Models

## Model Comparison Table

| Model | Type | Speed | Quality | Cost | Context | Best For |
|-------|------|-------|---------|------|---------|----------|
| **Gemini 3 Flash Preview** | Latest | ⚡⚡⚡ | ⭐⭐⭐ | 💰 | All | General use, speed |
| **Gemini 3.1 Pro Preview** | Latest | ⚡⚡ | ⭐⭐⭐⭐⭐ | 💰💰💰 | ≤200K: $2/$12<br>>200K: $4/$18 | Max quality, reasoning |
| **Gemini 3.1 Flash Lite Preview** | Latest | ⚡⚡⚡ | ⭐⭐ | 💰 | All | High volume, cost-efficient |
| **Gemini 2.5 Pro** | Stable | ⚡⚡ | ⭐⭐⭐⭐ | 💰💰 | ≤200K: $1.25/$10<br>>200K: $2.50/$15 | Advanced reasoning |
| **Gemini 2.5 Flash** | Stable | ⚡⚡⚡ | ⭐⭐⭐ | 💰 | 1M tokens | Long context |
| **Gemini 2.5 Flash-Lite** | Stable | ⚡⚡⚡ | ⭐⭐ | 💰 | All | Scale usage |
| **Gemini 2.0 Flash** | Stable | ⚡⚡⚡ | ⭐⭐⭐ | 💰 | All | Multimodal |
| **Gemini 2.0 Flash-Lite** | Stable | ⚡⚡⚡ | ⭐⭐ | 💰 | All | Cost-effective |
| **Gemini Pro Latest** | Alias | ⚡⚡ | ⭐⭐⭐⭐⭐ | 💰💰 | Variable | Auto-update Pro |
| **Gemini Flash Latest** | Alias | ⚡⚡⚡ | ⭐⭐⭐ | 💰 | Variable | Auto-update Flash |
| **Gemini Flash-Lite Latest** | Alias | ⚡⚡⚡ | ⭐⭐ | 💰 | Variable | Auto-update Lite |

**Legend:**
- Speed: ⚡ (1-3 lightning bolts)
- Quality: ⭐ (1-5 stars)
- Cost: 💰 (1-3 money bags)

---

## Pricing Quick Reference

### Text Generation (per 1M tokens)

| Model | Input | Output |
|-------|-------|--------|
| Gemini 3 Flash Preview | $0.50 | $3.00 |
| Gemini 3.1 Pro Preview (≤200K) | $2.00 | $12.00 |
| Gemini 3.1 Pro Preview (>200K) | $4.00 | $18.00 |
| Gemini 3.1 Flash Lite Preview | $0.25 | $1.50 |
| Gemini 2.5 Pro (≤200K) | $1.25 | $10.00 |
| Gemini 2.5 Pro (>200K) | $2.50 | $15.00 |
| Gemini 2.5 Flash | $0.30 | $2.50 |
| Gemini 2.5 Flash-Lite | $0.10 | $0.40 |
| Gemini 2.0 Flash | $0.10 | $0.40 |
| Gemini 2.0 Flash-Lite | $0.075 | $0.30 |

---

## Use Case Recommendations

### 🎯 SEO Metadata Generation
**Recommended:** Gemini 3 Flash Preview
- Fast response time
- Good quality for short content
- Cost-effective

### 📝 Long-form Content Analysis
**Recommended:** Gemini 2.5 Flash
- 1M token context window
- Can analyze entire articles
- Balanced cost/quality

### 💎 Premium Quality Output
**Recommended:** Gemini 3.1 Pro Preview
- Best reasoning capabilities
- Highest quality output
- Worth the cost for important content

### 💰 High Volume / Budget Conscious
**Recommended:** Gemini 3.1 Flash Lite Preview
- Lowest cost
- Still good quality
- Perfect for bulk operations

### 🔄 Always Stay Updated
**Recommended:** Gemini Flash Latest
- Auto-updates to newest Flash model
- No manual updates needed
- Good balance of features

---

## Model Selection Flowchart

```
Start: Need to generate SEO metadata
    ↓
Is budget a concern?
    ↓
Yes → High volume?
    ↓
    Yes → Gemini 3.1 Flash Lite Preview
    No  → Gemini 3 Flash Preview
    ↓
No → Need max quality?
    ↓
    Yes → Gemini 3.1 Pro Preview
    No  → Long content (>10K words)?
        ↓
        Yes → Gemini 2.5 Flash
        No  → Gemini 3 Flash Preview
```

---

## Knowledge Cutoff Dates

| Model | Knowledge Cutoff |
|-------|-----------------|
| Gemini 3.x | January 2025 |
| Gemini 2.5.x | January 2025 |
| Gemini 2.0.x | August 2024 |

---

## Context Window Sizes

| Model | Max Context |
|-------|-------------|
| Gemini 2.5 Flash | 1M tokens |
| All others | Varies (typically 32K-128K) |

---

## Release Dates

| Model | Release Date |
|-------|--------------|
| Gemini 3 Flash Preview | Dec 17, 2025 |
| Gemini 3.1 Pro Preview | Feb 12, 2026 |
| Gemini 3.1 Flash Lite Preview | Feb 26, 2026 |
| Gemini 2.5 Pro | Jun 17, 2025 |
| Gemini 2.5 Flash | Jun 10, 2025 |
| Gemini 2.5 Flash-Lite | Jul 15, 2025 |
| Gemini 2.0 Flash | Feb 1, 2025 |
| Gemini 2.0 Flash-Lite | Feb 1, 2025 |

---

## Alias Models Behavior

### Gemini Pro Latest
- Currently points to: `gemini-3.1-pro-preview`
- Updates automatically when new Pro model released
- Use for: Always want best Pro model

### Gemini Flash Latest
- Currently points to: `gemini-3-flash-preview`
- Updates automatically when new Flash model released
- Use for: Always want best Flash model

### Gemini Flash-Lite Latest
- Currently points to: `gemini-2.5-flash-lite-preview-09-2025`
- Updates automatically when new Flash-Lite model released
- Use for: Always want best Lite model

---

## Quick Decision Matrix

| Priority | Model Choice |
|----------|--------------|
| Speed First | Gemini 3 Flash Preview |
| Quality First | Gemini 3.1 Pro Preview |
| Cost First | Gemini 3.1 Flash Lite Preview |
| Balance | Gemini 3 Flash Preview |
| Long Context | Gemini 2.5 Flash |
| Auto-Update | Gemini Flash Latest |
| Production Stable | Gemini 2.5 Flash |

---

## Common Scenarios

### Scenario 1: Blog Post SEO
**Input:** 1,500 word article
**Recommended:** Gemini 3 Flash Preview
**Why:** Fast, good quality, cost-effective

### Scenario 2: E-commerce Product Pages (Bulk)
**Input:** 1,000 products, 200 words each
**Recommended:** Gemini 3.1 Flash Lite Preview
**Why:** Lowest cost for high volume

### Scenario 3: Premium Content Site
**Input:** Long-form articles, high quality needed
**Recommended:** Gemini 3.1 Pro Preview
**Why:** Best quality, worth the investment

### Scenario 4: News Site (Daily Updates)
**Input:** Multiple articles daily
**Recommended:** Gemini Flash Latest
**Why:** Always updated, good balance

### Scenario 5: Academic/Research Content
**Input:** Long papers, complex analysis
**Recommended:** Gemini 2.5 Flash
**Why:** 1M token context, can handle long content

---

## Tips & Tricks

### 💡 Tip 1: Use Alias for Production
Use "Latest" aliases in production to automatically get improvements without manual updates.

### 💡 Tip 2: Test Before Committing
Test different models with your content to find the best fit for your use case.

### 💡 Tip 3: Monitor Costs
Track API usage and costs. Switch to Lite models if costs are high.

### 💡 Tip 4: Quality vs Speed
For time-sensitive content, prioritize Flash models. For evergreen content, use Pro models.

### 💡 Tip 5: Batch Processing
For bulk operations, use Flash Lite to minimize costs.

---

## Model API Names (for reference)

```
gemini-3-flash-preview
gemini-3.1-pro-preview
gemini-3.1-flash-lite-preview
gemini-2.5-pro
gemini-pro-latest
gemini-flash-latest
gemini-flash-lite-latest
gemini-2.5-flash
gemini-2.5-flash-lite
gemini-2.0-flash
gemini-2.0-flash-lite
```

---

**Last Updated:** April 23, 2026
**Source:** Google AI Studio Model Documentation
