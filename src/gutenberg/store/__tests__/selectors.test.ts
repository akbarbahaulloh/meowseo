/**
 * Unit Tests: Store Selectors
 *
 * Tests that selectors return the correct state values.
 * Requirements: 3.5
 */

import {
	getSeoScore,
	getReadabilityScore,
	getAnalysisResults,
	getActiveTab,
	getIsAnalyzing,
	getContentSnapshot,
} from '../selectors';
import { MeowSEOState, AnalysisResult, ContentSnapshot } from '../types';

describe( 'Selectors', () => {
	const mockState: MeowSEOState = {
		seoScore: 85,
		readabilityScore: 90,
		analysisResults: [
			{
				id: 'keyword-in-title',
				type: 'good',
				message: 'Focus keyword appears in SEO title',
			},
			{
				id: 'keyword-in-description',
				type: 'problem',
				message: 'Focus keyword missing from meta description',
			},
		],
		activeTab: 'social',
		isAnalyzing: true,
		contentSnapshot: {
			title: 'Test Title',
			content: 'Test Content',
			excerpt: 'Test Excerpt',
			focusKeyword: 'test keyword',
			postType: 'post',
			permalink: 'https://example.com/test',
		},
	};

	describe( 'getSeoScore', () => {
		it( 'should return the SEO score', () => {
			expect( getSeoScore( mockState ) ).toBe( 85 );
		} );

		it( 'should return 0 for initial state', () => {
			const initialState: MeowSEOState = {
				...mockState,
				seoScore: 0,
			};
			expect( getSeoScore( initialState ) ).toBe( 0 );
		} );
	} );

	describe( 'getReadabilityScore', () => {
		it( 'should return the readability score', () => {
			expect( getReadabilityScore( mockState ) ).toBe( 90 );
		} );

		it( 'should return 0 for initial state', () => {
			const initialState: MeowSEOState = {
				...mockState,
				readabilityScore: 0,
			};
			expect( getReadabilityScore( initialState ) ).toBe( 0 );
		} );
	} );

	describe( 'getAnalysisResults', () => {
		it( 'should return the analysis results array', () => {
			const results = getAnalysisResults( mockState );
			expect( results ).toHaveLength( 2 );
			expect( results[ 0 ].id ).toBe( 'keyword-in-title' );
			expect( results[ 1 ].id ).toBe( 'keyword-in-description' );
		} );

		it( 'should return empty array for initial state', () => {
			const initialState: MeowSEOState = {
				...mockState,
				analysisResults: [],
			};
			expect( getAnalysisResults( initialState ) ).toEqual( [] );
		} );
	} );

	describe( 'getActiveTab', () => {
		it( 'should return the active tab', () => {
			expect( getActiveTab( mockState ) ).toBe( 'social' );
		} );

		it( 'should return general for initial state', () => {
			const initialState: MeowSEOState = {
				...mockState,
				activeTab: 'general',
			};
			expect( getActiveTab( initialState ) ).toBe( 'general' );
		} );

		it( 'should handle all tab types', () => {
			expect(
				getActiveTab( { ...mockState, activeTab: 'general' } )
			).toBe( 'general' );
			expect(
				getActiveTab( { ...mockState, activeTab: 'social' } )
			).toBe( 'social' );
			expect(
				getActiveTab( { ...mockState, activeTab: 'schema' } )
			).toBe( 'schema' );
			expect(
				getActiveTab( { ...mockState, activeTab: 'advanced' } )
			).toBe( 'advanced' );
		} );
	} );

	describe( 'getIsAnalyzing', () => {
		it( 'should return true when analyzing', () => {
			expect( getIsAnalyzing( mockState ) ).toBe( true );
		} );

		it( 'should return false when not analyzing', () => {
			const notAnalyzingState: MeowSEOState = {
				...mockState,
				isAnalyzing: false,
			};
			expect( getIsAnalyzing( notAnalyzingState ) ).toBe( false );
		} );
	} );

	describe( 'getContentSnapshot', () => {
		it( 'should return the content snapshot', () => {
			const snapshot = getContentSnapshot( mockState );
			expect( snapshot.title ).toBe( 'Test Title' );
			expect( snapshot.content ).toBe( 'Test Content' );
			expect( snapshot.excerpt ).toBe( 'Test Excerpt' );
			expect( snapshot.focusKeyword ).toBe( 'test keyword' );
			expect( snapshot.postType ).toBe( 'post' );
			expect( snapshot.permalink ).toBe( 'https://example.com/test' );
		} );

		it( 'should return empty snapshot for initial state', () => {
			const initialState: MeowSEOState = {
				...mockState,
				contentSnapshot: {
					title: '',
					content: '',
					excerpt: '',
					focusKeyword: '',
					postType: '',
					permalink: '',
				},
			};
			const snapshot = getContentSnapshot( initialState );
			expect( snapshot.title ).toBe( '' );
			expect( snapshot.content ).toBe( '' );
			expect( snapshot.excerpt ).toBe( '' );
			expect( snapshot.focusKeyword ).toBe( '' );
			expect( snapshot.postType ).toBe( '' );
			expect( snapshot.permalink ).toBe( '' );
		} );
	} );
} );
