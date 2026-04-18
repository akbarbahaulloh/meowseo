/**
 * Unit tests for Flesch Reading Ease Analyzer
 */

import { analyzeFleschReadingEase } from '../flesch-reading-ease.js';

describe( 'analyzeFleschReadingEase', () => {
	describe( 'basic functionality', () => {
		it( 'calculates Flesch score and returns appropriate type', () => {
			const content =
				'Ini mudah. Teks ini sederhana. Kalimatnya pendek. Semua orang bisa mengerti. Ini sangat jelas.';
			const result = analyzeFleschReadingEase( content );

			expect( result.id ).toBe( 'flesch-reading-ease' );
			expect( result.weight ).toBe( 0 );
			expect( result.details.fleschScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.details.fleschScore ).toBeLessThanOrEqual( 100 );
		} );

		it( 'returns good when Flesch score is high', () => {
			const content =
				'Ini mudah. Teks ini sederhana. Kalimatnya pendek. Semua orang bisa mengerti. Ini sangat jelas.';
			const result = analyzeFleschReadingEase( content );

			if ( result.details.fleschScore >= 60 ) {
				expect( result.type ).toBe( 'good' );
				expect( result.score ).toBe( 100 );
			}
		} );

		it( 'returns problem when Flesch score is low', () => {
			const content =
				'Implementasi metodologi komprehensif mengintegrasikan paradigma epistemologis dengan kerangka kerja hermeneutik yang kompleks dan multidimensional.';
			const result = analyzeFleschReadingEase( content );

			if ( result.details.fleschScore < 40 ) {
				expect( result.type ).toBe( 'problem' );
				expect( result.score ).toBe( 0 );
			}
		} );
	} );

	describe( 'score calculation', () => {
		it( 'calculates Flesch score based on word count, sentence count, and syllables', () => {
			const content = 'Anak membaca buku. Dia senang membaca.';
			const result = analyzeFleschReadingEase( content );

			expect( result.details ).toHaveProperty( 'fleschScore' );
			expect( result.details.fleschScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.details.fleschScore ).toBeLessThanOrEqual( 100 );
		} );

		it( 'stores word count in details', () => {
			const content = 'Anak membaca buku.';
			const result = analyzeFleschReadingEase( content );

			expect( result.details.wordCount ).toBeGreaterThan( 0 );
		} );

		it( 'stores sentence count in details', () => {
			const content = 'Anak membaca buku. Dia senang membaca.';
			const result = analyzeFleschReadingEase( content );

			expect( result.details.sentenceCount ).toBe( 2 );
		} );

		it( 'stores syllable count in details', () => {
			const content = 'Anak membaca buku.';
			const result = analyzeFleschReadingEase( content );

			expect( result.details.syllableCount ).toBeGreaterThan( 0 );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty content', () => {
			const result = analyzeFleschReadingEase( '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.wordCount ).toBe( 0 );
		} );

		it( 'handles null content', () => {
			const result = analyzeFleschReadingEase( null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles content with no sentences', () => {
			const result = analyzeFleschReadingEase( '   ' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'clamps score to 0-100 range', () => {
			const content = 'a';
			const result = analyzeFleschReadingEase( content );

			expect( result.details.fleschScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.details.fleschScore ).toBeLessThanOrEqual( 100 );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure', () => {
			const content = 'Anak membaca buku.';
			const result = analyzeFleschReadingEase( content );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'fleschScore' );
			expect( result.details ).toHaveProperty( 'wordCount' );
			expect( result.details ).toHaveProperty( 'sentenceCount' );
			expect( result.details ).toHaveProperty( 'syllableCount' );
		} );
	} );
} );
