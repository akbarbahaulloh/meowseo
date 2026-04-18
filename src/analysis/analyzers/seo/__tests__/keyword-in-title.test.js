/**
 * Unit tests for Keyword in Title Analyzer
 *
 * Tests the analyzeKeywordInTitle function which checks if the focus keyword
 * appears in the SEO title with Indonesian stemming support.
 */

import { analyzeKeywordInTitle } from '../keyword-in-title.js';

describe( 'analyzeKeywordInTitle', () => {
	describe( 'basic functionality', () => {
		it( 'returns good (100) when keyword is present in title', () => {
			const result = analyzeKeywordInTitle(
				'SEO Optimization Tips for Beginners',
				'seo optimization'
			);

			expect( result.id ).toBe( 'keyword-in-title' );
			expect( result.type ).toBe( 'good' );
			expect( result.score ).toBe( 100 );
			expect( result.weight ).toBe( 0.08 );
			expect( result.message ).toBe( 'Focus keyword found in title' );
			expect( result.details.found ).toBe( true );
			expect( result.details.keyword ).toBe( 'seo optimization' );
		} );

		it( 'returns problem (0) when keyword is missing from title', () => {
			const result = analyzeKeywordInTitle(
				'Tips for Content Writers',
				'seo optimization'
			);

			expect( result.id ).toBe( 'keyword-in-title' );
			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.weight ).toBe( 0.08 );
			expect( result.message ).toBe( 'Add focus keyword to title' );
			expect( result.details.found ).toBe( false );
			expect( result.details.keyword ).toBe( 'seo optimization' );
		} );

		it( 'performs case-insensitive matching', () => {
			const result = analyzeKeywordInTitle(
				'SEO OPTIMIZATION Tips for Beginners',
				'seo optimization'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword at any position in title', () => {
			const result = analyzeKeywordInTitle(
				'Complete Guide to SEO Optimization',
				'seo optimization'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword at end of title', () => {
			const result = analyzeKeywordInTitle(
				'Complete Guide to SEO Optimization',
				'optimization'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty keyword', () => {
			const result = analyzeKeywordInTitle( 'Some Title', '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.message ).toBe(
				'Set a focus keyword to analyze title optimization'
			);
		} );

		it( 'handles null keyword', () => {
			const result = analyzeKeywordInTitle( 'Some Title', null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles undefined keyword', () => {
			const result = analyzeKeywordInTitle( 'Some Title', undefined );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles empty title', () => {
			const result = analyzeKeywordInTitle( '', 'seo optimization' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.message ).toBe( 'Add an SEO title to your content' );
		} );

		it( 'handles null title', () => {
			const result = analyzeKeywordInTitle( null, 'seo optimization' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles undefined title', () => {
			const result = analyzeKeywordInTitle(
				undefined,
				'seo optimization'
			);

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles whitespace-only keyword', () => {
			const result = analyzeKeywordInTitle( 'Some Title', '   ' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles whitespace-only title', () => {
			const result = analyzeKeywordInTitle( '   ', 'seo optimization' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles keyword with extra whitespace', () => {
			const result = analyzeKeywordInTitle(
				'SEO Optimization Tips',
				'  seo   optimization  '
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.keyword ).toBe( 'seo   optimization' );
		} );
	} );

	describe( 'Indonesian stemming', () => {
		it( 'finds keyword with me- prefix variation', () => {
			// "membuat" should stem to "buat"
			const result = analyzeKeywordInTitle(
				'Cara Membuat Website yang Baik',
				'buat'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword with di- prefix variation', () => {
			// "dibuat" should stem to "buat"
			const result = analyzeKeywordInTitle(
				'Website yang Dibuat dengan Baik',
				'buat'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword with ber- prefix variation', () => {
			// "berjalan" should stem to "jalan"
			const result = analyzeKeywordInTitle(
				'Cara Berjalan yang Benar',
				'jalan'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword with ter- prefix variation', () => {
			// "terbuat" should stem to "buat"
			const result = analyzeKeywordInTitle(
				'Produk yang Terbuat dari Bahan Berkualitas',
				'buat'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword with -an suffix variation', () => {
			// "pembuatan" should stem to "buat"
			const result = analyzeKeywordInTitle(
				'Proses Pembuatan Website',
				'buat'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );

		it( 'finds keyword with -kan suffix variation', () => {
			// "buatkan" should stem to "buat"
			const result = analyzeKeywordInTitle(
				'Tolong Buatkan Website untuk Saya',
				'buat'
			);

			expect( result.type ).toBe( 'good' );
			expect( result.details.found ).toBe( true );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure for found keyword', () => {
			const result = analyzeKeywordInTitle( 'SEO Tips', 'seo' );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'keyword' );
			expect( result.details ).toHaveProperty( 'found' );
			expect( result.details ).toHaveProperty( 'position' );
		} );

		it( 'returns correct result structure for missing keyword', () => {
			const result = analyzeKeywordInTitle( 'Tips for Writers', 'seo' );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'keyword' );
			expect( result.details ).toHaveProperty( 'found' );
			expect( result.details ).toHaveProperty( 'position' );
		} );
	} );
} );
