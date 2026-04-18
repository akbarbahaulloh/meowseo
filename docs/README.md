# MeowSEO Analysis Engine Documentation

Welcome to the MeowSEO Analysis Engine documentation! This directory contains comprehensive guides for developers and users.

## Documentation Overview

### 📘 [User Guide](./USER_GUIDE.md)
**For content creators and WordPress users**

Learn how to use MeowSEO to optimize your content for search engines and readers. Includes:
- Getting started guide
- Understanding scores and metrics
- Step-by-step optimization process
- Indonesian language support
- Tips and best practices
- Troubleshooting

**Start here if you're**: Writing content, optimizing posts, or learning about SEO

---

### 🔧 [Developer Guide](./DEVELOPER_GUIDE.md)
**For developers working with the analysis engine**

Technical guide for extending and customizing the analysis engine. Includes:
- Architecture overview
- Analyzer interface specification
- Adding new analyzers
- Scoring system details
- Indonesian language features
- Web Worker architecture
- Code examples
- Testing strategies
- Performance considerations

**Start here if you're**: Adding features, customizing analyzers, or integrating with other systems

---

### 📚 [API Documentation](./API_DOCUMENTATION.md)
**Complete API reference**

Detailed API documentation for all functions, hooks, and components. Includes:
- Analysis Engine API
- Utility functions
- All 16 analyzers (SEO + Readability)
- Web Worker protocol
- React hooks
- Redux store
- Type definitions

**Start here if you're**: Looking up function signatures, understanding data structures, or integrating programmatically

---

## Quick Links

### For Users
- [Getting Started](./USER_GUIDE.md#getting-started)
- [Understanding Scores](./USER_GUIDE.md#understanding-your-scores)
- [Optimization Guide](./USER_GUIDE.md#content-optimization-guide)
- [Indonesian Support](./USER_GUIDE.md#indonesian-language-support)
- [Troubleshooting](./USER_GUIDE.md#troubleshooting)

### For Developers
- [Architecture](./DEVELOPER_GUIDE.md#architecture)
- [Adding Analyzers](./DEVELOPER_GUIDE.md#adding-new-analyzers)
- [Scoring System](./DEVELOPER_GUIDE.md#scoring-system)
- [Code Examples](./DEVELOPER_GUIDE.md#code-examples)
- [Testing](./DEVELOPER_GUIDE.md#testing)

### API Reference
- [Analysis Engine](./API_DOCUMENTATION.md#analysis-engine)
- [Utility Functions](./API_DOCUMENTATION.md#utility-functions)
- [SEO Analyzers](./API_DOCUMENTATION.md#seo-analyzers)
- [Readability Analyzers](./API_DOCUMENTATION.md#readability-analyzers)
- [React Hooks](./API_DOCUMENTATION.md#react-hooks)
- [Redux Store](./API_DOCUMENTATION.md#redux-store)

---

## Feature Overview

### Analysis Engine

The MeowSEO Analysis Engine provides real-time content analysis with:

**16 Specialized Analyzers**:
- 11 SEO analyzers for search optimization
- 5 Readability analyzers for content clarity
- 1 Informational analyzer (Flesch Reading Ease)

**Key Features**:
- ⚡ Real-time analysis (800ms debounce)
- 🚀 Non-blocking (Web Worker architecture)
- 🌏 Indonesian language support
- 📊 Weighted scoring system
- 🎯 Actionable recommendations

### SEO Analyzers (11 total)

1. **Keyword in Title** (8% weight) - Title optimization
2. **Keyword in Description** (7% weight) - Meta description
3. **Keyword in First Paragraph** (8% weight) - Content introduction
4. **Keyword Density** (9% weight) - Keyword usage balance
5. **Keyword in Headings** (8% weight) - Content structure
6. **Keyword in Slug** (7% weight) - URL optimization
7. **Image Alt Analysis** (8% weight) - Image optimization
8. **Internal Links** (8% weight) - Site structure
9. **Outbound Links** (7% weight) - Content credibility
10. **Content Length** (9% weight) - Content depth
11. **Direct Answer** (6% weight) - AI Overview optimization
12. **Schema Markup** (5% weight) - Rich results

### Readability Analyzers (5 total)

1. **Sentence Length** (20% weight) - Sentence clarity
2. **Paragraph Length** (20% weight) - Visual readability
3. **Passive Voice** (20% weight) - Writing clarity
4. **Transition Words** (20% weight) - Content flow
5. **Subheading Distribution** (20% weight) - Content structure
6. **Flesch Reading Ease** (0% weight) - Informational only

### Indonesian Language Support

Specialized features for Indonesian content:

- **Stemming**: Handles morphological variations (me-, di-, ber-, ter-, pe-, -an, -kan, -i, -nya)
- **Passive Voice**: Detects Indonesian patterns (di-, ter-, ke-an)
- **Transition Words**: Comprehensive Indonesian transition word list
- **Abbreviations**: Preserves Indonesian abbreviations (dr., prof., dll., dst., dsb.)
- **Syllable Counting**: Indonesian-adapted algorithm for Flesch score

---

## Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Gutenberg Editor                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  useContentSync Hook (800ms debounce)                      │
│           ↓                                                 │
│  useAnalysis Hook                                          │
│           ↓                                                 │
│  Web Worker (analysis-worker.ts)                           │
│           ↓                                                 │
│  Analysis Engine (analysis-engine.js)                      │
│           ↓                                                 │
│  16 Analyzers (11 SEO + 5 Readability)                    │
│           ↓                                                 │
│  Redux Store (meowseo/data)                                │
│           ↓                                                 │
│  UI Components (ContentScoreWidget, ReadabilityScorePanel) │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Key Components

**Content Sync Layer**:
- `useContentSync` hook: Syncs content from core/editor with 800ms debounce
- Single source of truth for content data

**Analysis Layer**:
- `useAnalysis` hook: Triggers analysis via Web Worker
- `analysis-worker.ts`: Runs analysis in separate thread
- `analysis-engine.js`: Orchestrates all analyzers

**Storage Layer**:
- Redux store (`meowseo/data`): Centralized state management
- Stores scores, results, and metadata

**Presentation Layer**:
- `ContentScoreWidget`: Displays scores and analyzer results
- `ReadabilityScorePanel`: Detailed readability metrics
- `AnalyzerResultItem`: Individual analyzer result display

---

## Getting Help

### Documentation

1. **User Guide**: For content optimization and usage
2. **Developer Guide**: For technical implementation
3. **API Documentation**: For function reference

### Additional Resources

- [Requirements Document](../.kiro/specs/readability-keyword-analysis-engine/requirements.md)
- [Design Document](../.kiro/specs/readability-keyword-analysis-engine/design.md)
- [Tasks Document](../.kiro/specs/readability-keyword-analysis-engine/tasks.md)

### Code Examples

See the [Developer Guide](./DEVELOPER_GUIDE.md#code-examples) for practical examples:
- Using analysis results in components
- Accessing specific analyzer results
- Creating custom utility functions
- Integrating with AI generation

---

## Contributing

### Adding Documentation

When adding new features:

1. Update relevant documentation files
2. Add code examples
3. Update API reference
4. Include usage examples
5. Document any breaking changes

### Documentation Standards

- Use clear, concise language
- Include code examples
- Provide context and rationale
- Link to related documentation
- Keep examples up-to-date

---

## Version History

### v1.0.0 (Current)

**Initial Release**:
- Complete documentation suite
- User guide with optimization strategies
- Developer guide with architecture details
- API documentation with all functions
- Indonesian language support documentation

---

## License

This documentation is part of the MeowSEO WordPress plugin.

---

## Support

For questions or issues:
1. Check relevant documentation
2. Review code examples
3. Consult API reference
4. Check requirements and design documents

---

**Last Updated**: 2024
**Documentation Version**: 1.0.0
