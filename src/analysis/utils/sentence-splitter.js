/**
 * Sentence Splitter
 *
 * Splits text into sentences while handling Indonesian abbreviations
 * and special punctuation patterns.
 *
 * Handles:
 * - Terminal punctuation: . ! ?
 * - Indonesian abbreviations: dr., prof., dll., dst., dsb., yg., dg.
 * - Ellipsis: ...
 *
 * @module sentence-splitter
 */

/**
 * Indonesian abbreviations that should not trigger sentence splits
 */
const INDONESIAN_ABBREVIATIONS = [
	'dr', // doctor
	'prof', // professor
	'dll', // dan lain-lain (etc.)
	'dst', // dan seterusnya (and so on)
	'dsb', // dan sebagainya (and so forth)
	'yg', // yang (that/which)
	'dg', // dengan (with)
	'dkk', // dan kawan-kawan (and colleagues)
	'sdr', // saudara (mr/ms)
	'no', // nomor (number)
	'hal', // halaman (page)
	'hlm', // halaman (page)
	'th', // tahun (year)
	'tgl', // tanggal (date)
];

/**
 * Splits text into sentences
 *
 * @param {string} text - The text to split into sentences
 * @return {string[]} Array of sentences
 *
 * @example
 * splitSentences('Ini kalimat pertama. Ini kalimat kedua.')
 * // returns ['Ini kalimat pertama.', 'Ini kalimat kedua.']
 *
 * splitSentences('Dr. Smith adalah dokter. Dia bekerja di rumah sakit.')
 * // returns ['Dr. Smith adalah dokter.', 'Dia bekerja di rumah sakit.']
 */
export function splitSentences( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return [];
	}

	// Normalize whitespace
	const normalized = text.trim().replace( /\s+/g, ' ' );

	// Protect abbreviations by temporarily replacing them
	const protectedText = protectAbbreviations( normalized );

	// Split on sentence terminators (., !, ?)
	// But handle ellipsis (...) specially
	const sentences = [];
	let currentSentence = '';

	for ( let i = 0; i < protectedText.length; i++ ) {
		const char = protectedText[ i ];
		const nextChar = protectedText[ i + 1 ];
		const prevChar = protectedText[ i - 1 ];

		currentSentence += char;

		// Check for sentence terminators
		if ( char === '.' || char === '!' || char === '?' ) {
			// Check if this is part of ellipsis (...)
			if (
				char === '.' &&
				nextChar === '.' &&
				protectedText[ i + 2 ] === '.'
			) {
				// This is an ellipsis, add all three dots and check if sentence ends after
				currentSentence += nextChar + protectedText[ i + 2 ];
				i += 2;

				// Check if there's a sentence terminator after ellipsis
				const afterEllipsis = protectedText[ i + 1 ];
				if ( afterEllipsis && /[.!?]/.test( afterEllipsis ) ) {
					// There's another terminator, continue
					continue;
				} else if ( afterEllipsis && /\s/.test( afterEllipsis ) ) {
					// Space after ellipsis, check if next char is uppercase (new sentence)
					let j = i + 1;
					while (
						j < protectedText.length &&
						/\s/.test( protectedText[ j ] )
					) {
						j++;
					}
					if (
						j < protectedText.length &&
						/[A-Z]/.test( protectedText[ j ] )
					) {
						// New sentence starts, end current sentence
						const sentence = currentSentence.trim();
						if ( sentence.length > 0 ) {
							sentences.push( restoreAbbreviations( sentence ) );
						}
						currentSentence = '';
						i = j - 1;
					}
				}
				continue;
			}

			// Check if this is a protected abbreviation marker
			if ( char === '.' && nextChar === '§' ) {
				// This is a protected abbreviation, continue
				continue;
			}

			// This is a sentence terminator
			// Skip any following whitespace
			let j = i + 1;
			while (
				j < protectedText.length &&
				/\s/.test( protectedText[ j ] )
			) {
				j++;
			}

			// Add the sentence (trimmed)
			const sentence = currentSentence.trim();
			if ( sentence.length > 0 ) {
				sentences.push( restoreAbbreviations( sentence ) );
			}

			// Reset for next sentence
			currentSentence = '';
			i = j - 1; // -1 because loop will increment
		}
	}

	// Add any remaining text as the last sentence
	const lastSentence = currentSentence.trim();
	if ( lastSentence.length > 0 ) {
		sentences.push( restoreAbbreviations( lastSentence ) );
	}

	return sentences;
}

/**
 * Protects abbreviations from being split by marking them
 *
 * @param {string} text - Text to process
 * @return {string} Text with protected abbreviations
 */
function protectAbbreviations( text ) {
	let protectedText = text;

	// Replace each abbreviation pattern with a protected version
	for ( const abbr of INDONESIAN_ABBREVIATIONS ) {
		// Match abbreviation followed by period (case-insensitive)
		const regex = new RegExp( `\\b${ abbr }\\.`, 'gi' );
		protectedText = protectedText.replace( regex, ( match ) => {
			// Replace the period with a special marker
			return match.substring( 0, match.length - 1 ) + '.§';
		} );
	}

	return protectedText;
}

/**
 * Restores protected abbreviations to their original form
 *
 * @param {string} text - Text with protected abbreviations
 * @return {string} Text with restored abbreviations
 */
function restoreAbbreviations( text ) {
	return text.replace( /\.§/g, '.' );
}
