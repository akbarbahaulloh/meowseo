/**
 * Unit tests for Passive Voice Analyzer
 */

import { analyzePassiveVoice } from '../passive-voice.js';

describe( 'analyzePassiveVoice', () => {
	describe( 'basic functionality', () => {
		it( 'detects passive voice when present', () => {
			const content =
				'Buku dibaca oleh anak. Dia menulis cerita. Mereka bermain bersama. Kami belajar bersama. Semua senang.';
			const result = analyzePassiveVoice( content );

			expect( result.id ).toBe( 'passive-voice' );
			expect( result.details.passiveCount ).toBeGreaterThan( 0 );
			expect( result.details.sentenceCount ).toBeGreaterThan( 0 );
		} );

		it( 'returns problem when passive voice is high', () => {
			const content =
				'Anak membaca buku. Dia menulis cerita. Mereka bermain bersama. Kami belajar bersama. Semua senang. Kami bermain. Dia tertawa. Mereka lari. Kami nyanyikan. Semua gembira. Kami makan. Dia minum. Mereka tidur. Kami bangun. Semua bahagia.';
			const result = analyzePassiveVoice( content );

			// This content has 20% passive voice (2 out of 15 sentences)
			expect( result.details.passivePercentage ).toBe( 20 );
			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'returns problem when passive voice is high', () => {
			const content =
				'Buku dibaca. Cerita ditulis. Permainan dimainkan. Kami diajari. Semua diajarkan.';
			const result = analyzePassiveVoice( content );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'passive voice detection', () => {
		it( 'detects di- prefix (dibuat)', () => {
			const content = 'Rumah dibuat dengan kokoh.';
			const result = analyzePassiveVoice( content );

			expect( result.details.passiveCount ).toBeGreaterThan( 0 );
		} );

		it( 'detects oleh (by) indicator', () => {
			const content = 'Buku dibaca oleh anak.';
			const result = analyzePassiveVoice( content );

			expect( result.details.passiveCount ).toBeGreaterThan( 0 );
		} );

		it( 'does not detect ter- prefix as passive voice', () => {
			const content = 'Produk terbuat dari bahan berkualitas.';
			const result = analyzePassiveVoice( content );

			// ter- is not detected as passive voice in this implementation
			expect( result.details.passiveCount ).toBe( 0 );
		} );
	} );

	describe( 'edge cases', () => {
		it( 'handles empty content', () => {
			const result = analyzePassiveVoice( '' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
			expect( result.details.sentenceCount ).toBe( 0 );
		} );

		it( 'handles null content', () => {
			const result = analyzePassiveVoice( null );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );

		it( 'handles content with no sentences', () => {
			const result = analyzePassiveVoice( '   ' );

			expect( result.type ).toBe( 'problem' );
			expect( result.score ).toBe( 0 );
		} );
	} );

	describe( 'result structure', () => {
		it( 'returns correct result structure', () => {
			const content = 'Anak membaca buku.';
			const result = analyzePassiveVoice( content );

			expect( result ).toHaveProperty( 'id' );
			expect( result ).toHaveProperty( 'type' );
			expect( result ).toHaveProperty( 'message' );
			expect( result ).toHaveProperty( 'score' );
			expect( result ).toHaveProperty( 'weight' );
			expect( result ).toHaveProperty( 'details' );
			expect( result.details ).toHaveProperty( 'passivePercentage' );
			expect( result.details ).toHaveProperty( 'sentenceCount' );
			expect( result.details ).toHaveProperty( 'passiveCount' );
		} );
	} );
} );
