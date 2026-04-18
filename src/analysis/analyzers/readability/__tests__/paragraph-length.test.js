/**
 * Unit tests for Paragraph Length Analyzer
 */

import { analyzeParagraphLength } from '../paragraph-length.js';

describe( 'analyzeParagraphLength', () => {
	describe( 'basic functionality', () => {
		it( 'returns good (100) when average paragraph length is <150 words', () => {
			const content = '<p>Short paragraph.</p><p>Another short one.</p>';
			const result = analyzeParagraphLength( content );

			expect( result.id ).toBe( 'paragraph-length' );
			expect( result.type ).toBe( 'good' );
			expect( result.score ).toBe( 100 );
			expect( result.weight ).toBe( 0.2 );
			expect( result.details.paragraphCount ).toBe( 2 );
		} );

		it( 'returns ok (50) when average paragraph length is 150-200 words', () => {
			const words = Array( 175 ).fill( 'word' ).join( ' ' );
			const content = `<p>${ words }</p>`;
			const result = analyzeParagraphLength( content );

			expect( result.type ).toBe( 'ok' );
			expect( result.score ).toBe( 50 );
		} );

		it( 'returns problem (0) when average paragraph length is >200 words', () => {
			const words = Array( 250 ).fill( 'word' ).join( ' ' );
			const content = `<p>${ words }</p>`;
			const result = analyzeParagraphLength( content );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty content', () => {
			const result = analyzeParagraphLength( '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.paragraphCount ).toBe( 0 );
		} );

		it( 'handles null content', () => {
			const result = analyzeParagraphLength( null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles content with no paragraphs', () => {
			const result = analyzeParagraphLength(
				'<div>No paragraphs here</div>'
			);

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.paragraphCount ).toBe( 0 );
		} );

		it( 'handles single paragraph', () => {
			const content =
				'<p>This is a single paragraph with some content.</p>';
			const result = analyzeParagraphLength( content );

			expect( result.details.paragraphCount ).toBe( 1 );
			expect( result.type ).toBe( 'good' );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure', () => {
			const content = '<p>Test paragraph.</p>';
			const result = analyzeParagraphLength( content );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'averageLength' );
			expect( result.details ).toHaveProperty( 'paragraphCount' );
		} );
	} );
} );
