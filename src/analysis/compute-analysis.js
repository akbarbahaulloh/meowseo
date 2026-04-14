/**
 * SEO Analysis Computation
 *
 * Pure function that computes SEO and readability scores.
 * Called by ContentSyncHook to analyze post content.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

/**
 * Compute SEO and readability analysis
 *
 * @param {Object} data Analysis data
 * @param {string} data.content Post content (HTML)
 * @param {string} data.title   Post title
 * @param {string} data.excerpt Post excerpt
 * @param {string} data.slug    Post slug
 * @param {string} data.keyword Focus keyword
 * @return {Object} Analysis result with seoScore, seoChecks, readabilityScore, readabilityChecks
 */
export function computeAnalysis( { content, title, excerpt, slug, keyword } ) {
	// Compute SEO checks
	const seoChecks = computeSeoChecks( { content, title, excerpt, slug, keyword } );
	const seoScore = computeScore( seoChecks );

	// Compute readability checks
	const readabilityChecks = computeReadabilityChecks( content );
	const readabilityScore = computeScore( readabilityChecks );

	return {
		seoScore,
		seoChecks,
		readabilityScore,
		readabilityChecks,
	};
}

/**
 * Compute SEO checks
 *
 * @param {Object} data Analysis data
 * @return {Array} Array of check result objects
 */
function computeSeoChecks( { content, title, excerpt, slug, keyword } ) {
	const checks = [];

	// If no keyword, all checks fail
	if ( ! keyword || keyword.trim() === '' ) {
		return [
			{ id: 'keyword_in_title', label: 'Focus keyword in SEO title', pass: false },
			{ id: 'keyword_in_description', label: 'Focus keyword in meta description', pass: false },
			{ id: 'keyword_in_first_paragraph', label: 'Focus keyword in first paragraph', pass: false },
			{ id: 'keyword_in_headings', label: 'Focus keyword in H2/H3 headings', pass: false },
			{ id: 'keyword_in_slug', label: 'Focus keyword in URL slug', pass: false },
			{ id: 'description_length', label: 'Meta description length (50-160 chars)', pass: false },
			{ id: 'title_length', label: 'SEO title length (30-60 chars)', pass: false },
		];
	}

	const keywordLower = keyword.toLowerCase();

	// Check 1: Keyword in title
	checks.push( {
		id: 'keyword_in_title',
		label: 'Focus keyword in SEO title',
		pass: containsKeyword( title, keywordLower ),
	} );

	// Check 2: Keyword in description (use excerpt as proxy for meta description)
	const description = excerpt || stripHtml( content ).substring( 0, 155 );
	checks.push( {
		id: 'keyword_in_description',
		label: 'Focus keyword in meta description',
		pass: containsKeyword( description, keywordLower ),
	} );

	// Check 3: Keyword in first paragraph
	const firstParagraph = extractFirstParagraph( content );
	checks.push( {
		id: 'keyword_in_first_paragraph',
		label: 'Focus keyword in first paragraph',
		pass: containsKeyword( firstParagraph, keywordLower ),
	} );

	// Check 4: Keyword in H2/H3 headings
	const headings = extractHeadings( content );
	checks.push( {
		id: 'keyword_in_headings',
		label: 'Focus keyword in H2/H3 headings',
		pass: headings.some( ( heading ) => containsKeyword( heading, keywordLower ) ),
	} );

	// Check 5: Keyword in slug
	checks.push( {
		id: 'keyword_in_slug',
		label: 'Focus keyword in URL slug',
		pass: containsKeyword( slug, keywordLower ),
	} );

	// Check 6: Meta description length (50-160 chars)
	const descLength = description.length;
	checks.push( {
		id: 'description_length',
		label: 'Meta description length (50-160 chars)',
		pass: descLength >= 50 && descLength <= 160,
	} );

	// Check 7: Title length (30-60 chars)
	const titleLength = title.length;
	checks.push( {
		id: 'title_length',
		label: 'SEO title length (30-60 chars)',
		pass: titleLength >= 30 && titleLength <= 60,
	} );

	return checks;
}

/**
 * Compute readability checks
 *
 * @param {string} content Post content (HTML)
 * @return {Array} Array of check result objects
 */
function computeReadabilityChecks( content ) {
	const text = stripHtml( content );
	const checks = [];

	// Check 1: Average sentence length ≤ 20 words
	const avgSentenceLength = getAverageSentenceLength( text );
	checks.push( {
		id: 'sentence_length',
		label: 'Average sentence length ≤ 20 words',
		pass: avgSentenceLength <= 20,
	} );

	// Check 2: Paragraph length ≤ 150 words
	const maxParagraphLength = getMaxParagraphLength( content );
	checks.push( {
		id: 'paragraph_length',
		label: 'Paragraph length ≤ 150 words',
		pass: maxParagraphLength <= 150,
	} );

	// Check 3: Transition word usage ≥ 30% of sentences
	const transitionPercentage = getTransitionWordPercentage( text );
	checks.push( {
		id: 'transition_words',
		label: 'Transition word usage ≥ 30% of sentences',
		pass: transitionPercentage >= 30,
	} );

	// Check 4: Passive voice usage ≤ 10%
	const passivePercentage = getPassiveVoicePercentage( text );
	checks.push( {
		id: 'passive_voice',
		label: 'Passive voice usage ≤ 10%',
		pass: passivePercentage <= 10,
	} );

	return checks;
}

/**
 * Compute score from checks
 *
 * @param {Array} checks Array of check result objects
 * @return {number} Score (0-100)
 */
function computeScore( checks ) {
	if ( checks.length === 0 ) {
		return 0;
	}

	const passingChecks = checks.filter( ( check ) => check.pass ).length;
	return Math.round( ( passingChecks / checks.length ) * 100 );
}

/**
 * Check if text contains keyword (case-insensitive)
 *
 * @param {string} text    Text to search
 * @param {string} keyword Keyword to find (already lowercase)
 * @return {boolean} True if keyword found
 */
function containsKeyword( text, keyword ) {
	if ( ! text || ! keyword ) {
		return false;
	}
	return text.toLowerCase().includes( keyword );
}

/**
 * Strip HTML tags from content
 *
 * @param {string} html HTML content
 * @return {string} Plain text
 */
function stripHtml( html ) {
	const div = document.createElement( 'div' );
	div.innerHTML = html;
	return div.textContent || div.innerText || '';
}

/**
 * Extract first paragraph from HTML content
 *
 * @param {string} html HTML content
 * @return {string} First paragraph text
 */
function extractFirstParagraph( html ) {
	const div = document.createElement( 'div' );
	div.innerHTML = html;
	const firstP = div.querySelector( 'p' );
	return firstP ? firstP.textContent || '' : '';
}

/**
 * Extract H2 and H3 headings from HTML content
 *
 * @param {string} html HTML content
 * @return {Array} Array of heading texts
 */
function extractHeadings( html ) {
	const div = document.createElement( 'div' );
	div.innerHTML = html;
	const headings = div.querySelectorAll( 'h2, h3' );
	return Array.from( headings ).map( ( h ) => h.textContent || '' );
}

/**
 * Get average sentence length in words
 *
 * @param {string} text Plain text
 * @return {number} Average sentence length
 */
function getAverageSentenceLength( text ) {
	if ( ! text || text.trim() === '' ) {
		return 0;
	}

	// Split into sentences (simple approach)
	const sentences = text.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 0 );
	if ( sentences.length === 0 ) {
		return 0;
	}

	// Count words in each sentence
	const totalWords = sentences.reduce( ( sum, sentence ) => {
		const words = sentence.trim().split( /\s+/ );
		return sum + words.length;
	}, 0 );

	return totalWords / sentences.length;
}

/**
 * Get maximum paragraph length in words
 *
 * @param {string} html HTML content
 * @return {number} Maximum paragraph length
 */
function getMaxParagraphLength( html ) {
	const div = document.createElement( 'div' );
	div.innerHTML = html;
	const paragraphs = div.querySelectorAll( 'p' );

	if ( paragraphs.length === 0 ) {
		return 0;
	}

	let maxLength = 0;
	paragraphs.forEach( ( p ) => {
		const text = p.textContent || '';
		const words = text.trim().split( /\s+/ );
		maxLength = Math.max( maxLength, words.length );
	} );

	return maxLength;
}

/**
 * Get transition word usage percentage
 *
 * @param {string} text Plain text
 * @return {number} Percentage (0-100)
 */
function getTransitionWordPercentage( text ) {
	if ( ! text || text.trim() === '' ) {
		return 0;
	}

	// Common transition words
	const transitionWords = [
		'however', 'therefore', 'furthermore', 'moreover', 'consequently',
		'nevertheless', 'meanwhile', 'additionally', 'similarly', 'likewise',
		'thus', 'hence', 'accordingly', 'besides', 'instead', 'otherwise',
		'finally', 'subsequently', 'previously', 'first', 'second', 'third',
		'also', 'although', 'because', 'since', 'while', 'whereas',
	];

	// Split into sentences
	const sentences = text.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 0 );
	if ( sentences.length === 0 ) {
		return 0;
	}

	// Count sentences with transition words
	const sentencesWithTransitions = sentences.filter( ( sentence ) => {
		const lowerSentence = sentence.toLowerCase();
		return transitionWords.some( ( word ) => lowerSentence.includes( word ) );
	} ).length;

	return ( sentencesWithTransitions / sentences.length ) * 100;
}

/**
 * Get passive voice usage percentage (simplified heuristic)
 *
 * @param {string} text Plain text
 * @return {number} Percentage (0-100)
 */
function getPassiveVoicePercentage( text ) {
	if ( ! text || text.trim() === '' ) {
		return 0;
	}

	// Split into sentences
	const sentences = text.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 0 );
	if ( sentences.length === 0 ) {
		return 0;
	}

	// Simple heuristic: look for "is/are/was/were/been/be" + past participle pattern
	const passivePatterns = [
		/\b(is|are|was|were|been|be)\s+\w+ed\b/i,
		/\b(is|are|was|were|been|be)\s+\w+en\b/i,
	];

	const passiveSentences = sentences.filter( ( sentence ) => {
		return passivePatterns.some( ( pattern ) => pattern.test( sentence ) );
	} ).length;

	return ( passiveSentences / sentences.length ) * 100;
}
