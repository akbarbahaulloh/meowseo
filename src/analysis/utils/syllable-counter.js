/**
 * Syllable Counter
 *
 * Counts syllables in Indonesian words based on vowel groups
 * and diphthong patterns.
 *
 * Used for Flesch Reading Ease calculation adapted for Indonesian.
 *
 * @module syllable-counter
 */

/**
 * Indonesian vowels
 */
const VOWELS = [ 'a', 'e', 'i', 'o', 'u', 'y' ];

/**
 * Indonesian diphthongs (two vowels that form a single syllable)
 */
const DIPHTHONGS = [ 'ai', 'au', 'ei', 'oi', 'ui', 'ey', 'oy' ];

/**
 * Counts syllables in an Indonesian word
 *
 * Algorithm:
 * 1. Count vowel groups (consecutive vowels = 1 syllable)
 * 2. Handle diphthongs as single syllables
 * 3. Minimum of 1 syllable per word
 *
 * @param {string} word - The word to count syllables in
 * @return {number} Number of syllables
 *
 * @example
 * countSyllables('buku') // returns 2 (bu-ku)
 * countSyllables('membaca') // returns 3 (mem-ba-ca)
 * countSyllables('air') // returns 1 (diphthong 'ai')
 * countSyllables('saudara') // returns 3 (sau-da-ra, 'au' is diphthong)
 */
export function countSyllables( word ) {
	if ( ! word || typeof word !== 'string' ) {
		return 0;
	}

	// Convert to lowercase for consistent processing
	const normalized = word.toLowerCase().trim();

	if ( normalized.length === 0 ) {
		return 0;
	}

	let syllableCount = 0;
	let i = 0;

	while ( i < normalized.length ) {
		const char = normalized[ i ];

		// Check if current character is a vowel
		if ( VOWELS.includes( char ) ) {
			// Check if this is part of a diphthong
			const twoChars = normalized.substring( i, i + 2 );

			if ( DIPHTHONGS.includes( twoChars ) ) {
				// This is a diphthong - count as 1 syllable
				syllableCount++;
				i += 2; // Skip both characters
			} else {
				// This is a single vowel - count as 1 syllable
				syllableCount++;
				i++;

				// Skip consecutive vowels that form the same syllable
				// But check if they form a diphthong first
				while (
					i < normalized.length &&
					VOWELS.includes( normalized[ i ] )
				) {
					const nextDiph = normalized.substring( i - 1, i + 1 );
					if ( DIPHTHONGS.includes( nextDiph ) ) {
						// This forms a new diphthong, don't skip
						break;
					}
					// Check if current and next form a diphthong
					const currentDiph = normalized.substring( i, i + 2 );
					if ( DIPHTHONGS.includes( currentDiph ) ) {
						// This is a new syllable (diphthong)
						syllableCount++;
						i += 2;
						break;
					}
					// Just another vowel in the same group, skip it
					i++;
				}
			}
		} else {
			// Not a vowel, move to next character
			i++;
		}
	}

	// Every word has at least 1 syllable
	return Math.max( syllableCount, 1 );
}
