/**
 * Unit tests for Subheading Distribution Analyzer
 */

import { analyzeSubheadingDistribution } from '../subheading-distribution.js';

describe( 'analyzeSubheadingDistribution', () => {
	describe( 'basic functionality', () => {
		it( 'returns good (100) when headings appear every <300 words', () => {
			const content = `
        <h2>Section 1</h2>
        <p>Content here with some words.</p>
        <h2>Section 2</h2>
        <p>More content here.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.id ).toBe( 'subheading-distribution' );
			expect( result.type ).toBe( 'good' );
			expect( result.score ).toBe( 100 );
			expect( result.weight ).toBe( 0.2 );
		} );

		it( 'returns ok (50) when headings appear every 300-400 words', () => {
			const words = Array( 350 ).fill( 'word' ).join( ' ' );
			const content = `
        <h2>Section 1</h2>
        <p>${ words }</p>
        <h2>Section 2</h2>
        <p>More content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.type ).toBe( 'ok' );
			expect( result.score ).toBe( 50 );
		} );

		it( 'returns problem (0) when headings appear every >400 words', () => {
			const words = Array( 450 ).fill( 'word' ).join( ' ' );
			const content = `
        <h2>Section 1</h2>
        <p>${ words }</p>
        <h2>Section 2</h2>
        <p>More content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'heading detection', () => {
		it( 'detects H2 headings', () => {
			const content = `
        <h2>Section 1</h2>
        <p>Content here.</p>
        <h2>Section 2</h2>
        <p>More content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.details.headingCount ).toBe( 2 );
		} );

		it( 'detects H3 headings', () => {
			const content = `
        <h3>Subsection 1</h3>
        <p>Content here.</p>
        <h3>Subsection 2</h3>
        <p>More content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.details.headingCount ).toBe( 2 );
		} );

		it( 'detects mixed H2 and H3 headings', () => {
			const content = `
        <h2>Section 1</h2>
        <p>Content here.</p>
        <h3>Subsection 1</h3>
        <p>More content.</p>
        <h2>Section 2</h2>
        <p>Even more content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result.details.headingCount ).toBe( 3 );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty content', () => {
			const result = analyzeSubheadingDistribution( '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.headingCount ).toBe( 0 );
		} );

		it( 'handles null content', () => {
			const result = analyzeSubheadingDistribution( null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles content with no headings', () => {
			const result = analyzeSubheadingDistribution(
				'<p>No headings here.</p>'
			);

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.headingCount ).toBe( 0 );
		} );

		it( 'handles content with only one heading', () => {
			const result = analyzeSubheadingDistribution(
				'<h2>Only Heading</h2><p>Content.</p>'
			);

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.headingCount ).toBe( 1 );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure', () => {
			const content = `
        <h2>Section 1</h2>
        <p>Content here.</p>
        <h2>Section 2</h2>
        <p>More content.</p>
      `;
			const result = analyzeSubheadingDistribution( content );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'averageSpacing' );
			expect( result.details ).toHaveProperty( 'headingCount' );
		} );
	} );
} );
