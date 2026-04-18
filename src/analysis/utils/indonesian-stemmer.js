/**
 * Indonesian Stemmer
 *
 * Handles morphological variations in Indonesian language by removing
 * common prefixes and suffixes to find the base word form.
 *
 * Supports:
 * - Prefixes: me-, di-, ber-, ter-, pe-
 * - Suffixes: -an, -kan, -i, -nya
 * - Prefix-suffix combinations
 *
 * @module indonesian-stemmer
 */

/**
 * Stems an Indonesian word by removing prefixes and suffixes
 *
 * @param {string} word - The word to stem
 * @return {string} The stemmed base form of the word
 *
 * @example
 * stemWord('membuat') // returns 'buat'
 * stemWord('dibuat') // returns 'buat'
 * stemWord('pembuatan') // returns 'buat'
 */
export function stemWord( word ) {
	if ( ! word || typeof word !== 'string' ) {
		return '';
	}

	// Convert to lowercase for consistent processing
	let stem = word.toLowerCase().trim();

	// Minimum word length to avoid over-stemming
	if ( stem.length < 4 ) {
		return stem;
	}

	// Store original for fallback
	const original = stem;

	// Remove prefixes (order matters - try longer prefixes first)
	stem = removePrefixes( stem );

	// Remove suffixes
	stem = removeSuffixes( stem );

	// If stemming resulted in too short a word, return original
	if ( stem.length < 3 ) {
		return original;
	}

	return stem;
}

/**
 * Removes Indonesian prefixes from a word
 *
 * @param {string} word - The word to process
 * @return {string} Word with prefix removed
 */
function removePrefixes( word ) {
	let stem = word;

	// me- prefix variations
	// me- + word starting with consonant (membuat → buat)
	if ( stem.startsWith( 'me' ) ) {
		const withoutMe = stem.substring( 2 );
		if ( withoutMe.length >= 3 ) {
			// Handle me- + nasal insertion (membeli → beli, menjual → jual)
			if (
				withoutMe.startsWith( 'm' ) ||
				withoutMe.startsWith( 'n' ) ||
				withoutMe.startsWith( 'ng' ) ||
				withoutMe.startsWith( 'ny' )
			) {
				if ( withoutMe.startsWith( 'ng' ) ) {
					stem = withoutMe.substring( 2 );
				} else if ( withoutMe.startsWith( 'ny' ) ) {
					stem = withoutMe.substring( 2 );
				} else {
					stem = withoutMe.substring( 1 );
				}
			} else {
				stem = withoutMe;
			}
		}
	}

	// di- prefix (dibuat → buat)
	else if ( stem.startsWith( 'di' ) && stem.length > 4 ) {
		stem = stem.substring( 2 );
	}

	// ber- prefix (berjalan → jalan)
	else if ( stem.startsWith( 'ber' ) && stem.length > 5 ) {
		stem = stem.substring( 3 );
	}

	// ter- prefix (terbuat → buat)
	else if ( stem.startsWith( 'ter' ) && stem.length > 5 ) {
		stem = stem.substring( 3 );
	}

	// pe- prefix (pembuat → buat)
	else if ( stem.startsWith( 'pe' ) ) {
		const withoutPe = stem.substring( 2 );
		if ( withoutPe.length >= 3 ) {
			// Handle pe- + nasal insertion
			if (
				withoutPe.startsWith( 'm' ) ||
				withoutPe.startsWith( 'n' ) ||
				withoutPe.startsWith( 'ng' ) ||
				withoutPe.startsWith( 'ny' )
			) {
				if ( withoutPe.startsWith( 'ng' ) ) {
					stem = withoutPe.substring( 2 );
				} else if ( withoutPe.startsWith( 'ny' ) ) {
					stem = withoutPe.substring( 2 );
				} else {
					stem = withoutPe.substring( 1 );
				}
			} else {
				stem = withoutPe;
			}
		}
	}

	return stem;
}

/**
 * Removes Indonesian suffixes from a word
 *
 * @param {string} word - The word to process
 * @return {string} Word with suffix removed
 */
function removeSuffixes( word ) {
	let stem = word;

	// -kan suffix (buatkan → buat)
	if ( stem.endsWith( 'kan' ) && stem.length > 5 ) {
		stem = stem.substring( 0, stem.length - 3 );
	}

	// -an suffix (pembuatan → pembuat, jalan → jalan)
	else if ( stem.endsWith( 'an' ) && stem.length > 5 ) {
		stem = stem.substring( 0, stem.length - 2 );
	}

	// -i suffix (buati → buat, jadi → jadi)
	else if ( stem.endsWith( 'i' ) && stem.length > 4 ) {
		stem = stem.substring( 0, stem.length - 1 );
	}

	// -nya suffix (bukunya → buku)
	else if ( stem.endsWith( 'nya' ) && stem.length > 5 ) {
		stem = stem.substring( 0, stem.length - 3 );
	}

	return stem;
}
