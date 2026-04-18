/**
 * Analysis Analyzers
 *
 * Central export point for all content analyzers:
 * - SEO Analyzers (11 total): Keyword optimization, content structure, schema
 * - Readability Analyzers (6 total): Sentence/paragraph length, passive voice, transitions, headings, Flesch score
 *
 * @module analyzers
 */

// SEO Analyzers
export {
	analyzeKeywordInTitle,
	analyzeKeywordInDescription,
	analyzeKeywordInFirstParagraph,
	analyzeKeywordDensity,
	analyzeKeywordInHeadings,
	analyzeKeywordInSlug,
	analyzeImageAlt,
	analyzeInternalLinks,
	analyzeOutboundLinks,
	analyzeContentLength,
	analyzeDirectAnswer,
	analyzeSchemaPresence,
} from './seo/index.js';

// Readability Analyzers
export {
	analyzeSentenceLength,
	analyzeParagraphLength,
	analyzePassiveVoice,
	analyzeTransitionWords,
	analyzeSubheadingDistribution,
	analyzeFleschReadingEase,
} from './readability/index.js';
