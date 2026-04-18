/**
 * Unit tests for Transition Words Analyzer
 */

import { analyzeTransitionWords } from '../transition-words.js';

describe( 'analyzeTransitionWords', () => {
	describe( 'basic functionality', () => {
		it( 'detects transition words when present', () => {
			const content =
				'Pertama, kami membuat rencana. Kemudian, kami melaksanakannya. Akhirnya, kami berhasil. Selanjutnya, kami merayakan.';
			const result = analyzeTransitionWords( content );

			expect( result.id ).toBe( 'transition-words' );
			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
			expect( result.details.sentenceCount ).toBeGreaterThan( 0 );
		} );

		it( 'returns good when transition word usage is high', () => {
			const content =
				'Pertama, kami membuat rencana. Kemudian, kami melaksanakannya. Akhirnya, kami berhasil. Selanjutnya, kami merayakan.';
			const result = analyzeTransitionWords( content );

			expect( result.type ).toBe( 'good' );
			expect( result.score ).toBe( 100 );
		} );

		it( 'returns problem when transition word usage is low', () => {
			const content =
				'Kami membuat rencana. Kami melaksanakannya. Kami berhasil. Kami merayakan. Kami pulang.';
			const result = analyzeTransitionWords( content );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'transition word detection', () => {
		it( 'detects additive transitions (dan, juga)', () => {
			const content = 'Kami membuat rencana. Dan kami melaksanakannya.';
			const result = analyzeTransitionWords( content );

			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
		} );

		it( 'detects contrast transitions (namun, tetapi)', () => {
			const content =
				'Kami membuat rencana. Namun, kami tidak melaksanakannya.';
			const result = analyzeTransitionWords( content );

			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
		} );

		it( 'detects causal transitions (karena, maka)', () => {
			const content =
				'Kami membuat rencana. Karena itu, kami melaksanakannya.';
			const result = analyzeTransitionWords( content );

			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
		} );

		it( 'detects sequential transitions (kemudian, selanjutnya)', () => {
			const content =
				'Kami membuat rencana. Kemudian, kami melaksanakannya.';
			const result = analyzeTransitionWords( content );

			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
		} );

		it( 'detects exemplifying transitions (misalnya, contohnya)', () => {
			const content =
				'Ada banyak cara. Misalnya, kami bisa membuat rencana.';
			const result = analyzeTransitionWords( content );

			expect( result.details.sentencesWithTransitions ).toBeGreaterThan(
				0
			);
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty content', () => {
			const result = analyzeTransitionWords( '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.sentenceCount ).toBe( 0 );
		} );

		it( 'handles null content', () => {
			const result = analyzeTransitionWords( null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles content with no sentences', () => {
			const result = analyzeTransitionWords( '   ' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure', () => {
			const content =
				'Kami membuat rencana. Kemudian, kami melaksanakannya.';
			const result = analyzeTransitionWords( content );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'transitionPercentage' );
			expect( result.details ).toHaveProperty( 'sentenceCount' );
			expect( result.details ).toHaveProperty(
				'sentencesWithTransitions'
			);
		} );
	} );
} );
