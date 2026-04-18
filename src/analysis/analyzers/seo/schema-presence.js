/**
 * Schema Presence Analyzer
 *
 * Checks if Schema Type is configured for rich results.
 *
 * @module analysis/analyzers/seo/schema-presence
 */

/**
 * Valid schema types for rich results
 */
const VALID_SCHEMA_TYPES = [
	'Article',
	'NewsArticle',
	'BlogPosting',
	'FAQPage',
	'HowTo',
	'Recipe',
	'Product',
	'LocalBusiness',
	'Event',
	'Course',
	'JobPosting',
	'Movie',
	'Book',
	'SoftwareApplication',
	'VideoObject',
	'AudioObject',
];

/**
 * Analyzes Schema Type presence
 *
 * @param {string} schemaType - The Schema Type field value
 * @return {Object} Analyzer result with id, type, message, score, weight, and details
 *
 * @example
 * analyzeSchemaPresence('Article')
 * // Returns: { id: 'schema-presence', type: 'good', message: 'Schema type configured (Article)', score: 100, weight: 0.05, details: { schemaType: 'Article' } }
 */
export function analyzeSchemaPresence( schemaType ) {
	// Handle missing or empty schema type
	if ( ! schemaType || schemaType.trim() === '' ) {
		return {
			id: 'schema-presence',
			type: 'problem',
			message: 'Configure a schema type for rich results in search',
			score: 0,
			weight: 0.05,
			details: {
				schemaType: '',
			},
		};
	}

	const normalizedSchemaType = schemaType.trim();
	const isValidSchema = VALID_SCHEMA_TYPES.includes( normalizedSchemaType );

	// Determine status
	// Good: Schema Type set to valid type
	// Problem: Schema Type missing or invalid
	let type, message, score;

	if ( isValidSchema ) {
		type = 'good';
		message = `Schema type configured (${ normalizedSchemaType })`;
		score = 100;
	} else {
		// Accept any non-empty schema type as ok (might be custom)
		type = 'ok';
		message = `Schema type set to "${ normalizedSchemaType }". Consider using a standard schema type for better rich results.`;
		score = 50;
	}

	return {
		id: 'schema-presence',
		type,
		message,
		score,
		weight: 0.05,
		details: {
			schemaType: normalizedSchemaType,
		},
	};
}

export default analyzeSchemaPresence;
