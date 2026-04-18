/**
 * Unit Tests: Store Reducer
 *
 * Tests that the reducer handles each action type correctly and maintains initial state.
 * Requirements: 3.2, 3.3, 3.4
 */

import { reducer, initialState } from '../reducer';
import {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
} from '../actions';
import { ContentSnapshot, AnalysisResult, MeowSEOState } from '../types';

describe( 'Reducer', () => {
	describe( 'Initial State', () => {
		it( 'should return the initial state', () => {
			expect( initialState ).toEqual( {
				seoScore: 0,
				readabilityScore: 0,
				analysisResults: [],
				readabilityResults: [],
				wordCount: 0,
				sentenceCount: 0,
				paragraphCount: 0,
				fleschScore: 0,
				keywordDensity: 0,
				analysisTimestamp: null,
				activeTab: 'general',
				isAnalyzing: false,
				contentSnapshot: {
					title: '',
					content: '',
					excerpt: '',
					focusKeyword: '',
					postType: '',
					permalink: '',
				},
			} );
		} );

		it( 'should return initial state when called with undefined state', () => {
			const action = { type: 'UNKNOWN_ACTION' } as any;
			const state = reducer( undefined as any, action );

			expect( state ).toEqual( initialState );
		} );
	} );

	describe( 'UPDATE_CONTENT_SNAPSHOT', () => {
		it( 'should update content snapshot', () => {
			const snapshot: ContentSnapshot = {
				title: 'New Title',
				content: 'New Content',
				excerpt: 'New Excerpt',
				focusKeyword: 'new keyword',
				postType: 'page',
				permalink: 'https://example.com/new',
			};

			const action = updateContentSnapshot( snapshot );
			const newState = reducer( initialState, action );

			expect( newState.contentSnapshot ).toEqual( snapshot );
			expect( newState.seoScore ).toBe( 0 );
			expect( newState.readabilityScore ).toBe( 0 );
			expect( newState.activeTab ).toBe( 'general' );
		} );

		it( 'should not mutate the original state', () => {
			const snapshot: ContentSnapshot = {
				title: 'Test',
				content: 'Test',
				excerpt: 'Test',
				focusKeyword: 'test',
				postType: 'post',
				permalink: 'https://example.com/test',
			};

			const action = updateContentSnapshot( snapshot );
			const stateBefore = { ...initialState };
			const newState = reducer( initialState, action );

			expect( initialState ).toEqual( stateBefore );
			expect( newState ).not.toBe( initialState );
		} );
	} );

	describe( 'SET_ANALYZING', () => {
		it( 'should set isAnalyzing to true', () => {
			const action = setAnalyzing( true );
			const newState = reducer( initialState, action );

			expect( newState.isAnalyzing ).toBe( true );
			expect( newState.seoScore ).toBe( 0 );
			expect( newState.activeTab ).toBe( 'general' );
		} );

		it( 'should set isAnalyzing to false', () => {
			const stateWithAnalyzing: MeowSEOState = {
				...initialState,
				isAnalyzing: true,
			};

			const action = setAnalyzing( false );
			const newState = reducer( stateWithAnalyzing, action );

			expect( newState.isAnalyzing ).toBe( false );
		} );

		it( 'should not mutate the original state', () => {
			const action = setAnalyzing( true );
			const stateBefore = { ...initialState };
			const newState = reducer( initialState, action );

			expect( initialState ).toEqual( stateBefore );
			expect( newState ).not.toBe( initialState );
		} );
	} );

	describe( 'SET_ANALYSIS_RESULTS', () => {
		it( 'should update seoScore, readabilityScore, and analysisResults', () => {
			const seoResults: AnalysisResult[] = [
				{
					id: 'keyword-in-title',
					type: 'good',
					message: 'Focus keyword appears in SEO title',
				},
			];
			const readabilityResults: AnalysisResult[] = [
				{
					id: 'sentence-length',
					type: 'good',
					message: 'Sentences are concise',
				},
			];

			const action = setAnalysisResults(
				seoResults,
				readabilityResults,
				85,
				90,
				1250,
				45,
				8,
				68,
				1.2,
				Date.now()
			);
			const newState = reducer( initialState, action );

			expect( newState.seoScore ).toBe( 85 );
			expect( newState.readabilityScore ).toBe( 90 );
			expect( newState.analysisResults ).toEqual( seoResults );
			expect( newState.readabilityResults ).toEqual( readabilityResults );
			expect( newState.wordCount ).toBe( 1250 );
			expect( newState.sentenceCount ).toBe( 45 );
			expect( newState.paragraphCount ).toBe( 8 );
			expect( newState.fleschScore ).toBe( 68 );
			expect( newState.keywordDensity ).toBe( 1.2 );
			expect( newState.activeTab ).toBe( 'general' );
			expect( newState.isAnalyzing ).toBe( false );
		} );

		it( 'should handle empty results', () => {
			const action = setAnalysisResults(
				[],
				[],
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				Date.now()
			);
			const newState = reducer( initialState, action );

			expect( newState.seoScore ).toBe( 0 );
			expect( newState.readabilityScore ).toBe( 0 );
			expect( newState.analysisResults ).toEqual( [] );
			expect( newState.readabilityResults ).toEqual( [] );
		} );

		it( 'should handle maximum scores', () => {
			const action = setAnalysisResults(
				[],
				[],
				100,
				100,
				5000,
				200,
				50,
				100,
				2.5,
				Date.now()
			);
			const newState = reducer( initialState, action );

			expect( newState.seoScore ).toBe( 100 );
			expect( newState.readabilityScore ).toBe( 100 );
		} );

		it( 'should not mutate the original state', () => {
			const results: AnalysisResult[] = [
				{ id: 'test', type: 'good', message: 'Test' },
			];
			const action = setAnalysisResults(
				results,
				[],
				50,
				60,
				500,
				20,
				5,
				50,
				1.0,
				Date.now()
			);
			const stateBefore = { ...initialState };
			const newState = reducer( initialState, action );

			expect( initialState ).toEqual( stateBefore );
			expect( newState ).not.toBe( initialState );
		} );
	} );

	describe( 'SET_ACTIVE_TAB', () => {
		it( 'should set active tab to general', () => {
			const action = setActiveTab( 'general' );
			const newState = reducer( initialState, action );

			expect( newState.activeTab ).toBe( 'general' );
		} );

		it( 'should set active tab to social', () => {
			const action = setActiveTab( 'social' );
			const newState = reducer( initialState, action );

			expect( newState.activeTab ).toBe( 'social' );
		} );

		it( 'should set active tab to schema', () => {
			const action = setActiveTab( 'schema' );
			const newState = reducer( initialState, action );

			expect( newState.activeTab ).toBe( 'schema' );
		} );

		it( 'should set active tab to advanced', () => {
			const action = setActiveTab( 'advanced' );
			const newState = reducer( initialState, action );

			expect( newState.activeTab ).toBe( 'advanced' );
		} );

		it( 'should not mutate the original state', () => {
			const action = setActiveTab( 'social' );
			const stateBefore = { ...initialState };
			const newState = reducer( initialState, action );

			expect( initialState ).toEqual( stateBefore );
			expect( newState ).not.toBe( initialState );
		} );
	} );

	describe( 'Unknown Action', () => {
		it( 'should return the current state for unknown actions', () => {
			const action = { type: 'UNKNOWN_ACTION' } as any;
			const newState = reducer( initialState, action );

			expect( newState ).toBe( initialState );
		} );
	} );

	describe( 'State Composition', () => {
		it( 'should handle multiple actions in sequence', () => {
			let state = initialState;

			// Update content snapshot
			state = reducer(
				state,
				updateContentSnapshot( {
					title: 'Test',
					content: 'Content',
					excerpt: 'Excerpt',
					focusKeyword: 'keyword',
					postType: 'post',
					permalink: 'https://example.com/test',
				} )
			);

			// Set analyzing
			state = reducer( state, setAnalyzing( true ) );

			// Set analysis results
			state = reducer(
				state,
				setAnalysisResults(
					[],
					[],
					75,
					80,
					500,
					20,
					5,
					50,
					1.0,
					Date.now()
				)
			);

			// Set active tab
			state = reducer( state, setActiveTab( 'social' ) );

			expect( state.contentSnapshot.title ).toBe( 'Test' );
			expect( state.isAnalyzing ).toBe( true );
			expect( state.seoScore ).toBe( 75 );
			expect( state.readabilityScore ).toBe( 80 );
			expect( state.activeTab ).toBe( 'social' );
		} );
	} );
} );
