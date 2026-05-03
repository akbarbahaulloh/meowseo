/**
 * Synonym & Word Form Recognition Analyzer
 *
 * @module analysis/analyzers/seo/synonym-word-form
 */

export function analyzeSynonymWordForm( content ) {
	// MeowSEO uses an Indonesian stemmer by default to recognize word forms.
	// For this indicator, we just output the requested positive reinforcement.
	return {
		id: 'synonym-word-form',
		type: 'good',
		message: 'Synonym & Word Form Recognition: Write more natural, flowing content.',
		score: 100,
		weight: 0.0, // Informational
		details: {},
	};
}

export default analyzeSynonymWordForm;
