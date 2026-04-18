/**
 * Unit Tests: Store Actions
 *
 * Tests that each action creator returns the correct action object.
 * Requirements: 3.2, 3.3, 3.4
 */

import {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	UPDATE_CONTENT_SNAPSHOT,
	SET_ANALYZING,
	SET_ANALYSIS_RESULTS,
	SET_ACTIVE_TAB,
} from '../actions';
import { ContentSnapshot, AnalysisResult } from '../types';

describe( 'Action Creators', () => {
	describe( 'updateContentSnapshot', () => {
		it( 'should create an action to update content snapshot', () => {
			const snapshot: ContentSnapshot = {
				title: 'Test Title',
				content: 'Test Content',
				excerpt: 'Test Excerpt',
				focusKeyword: 'test keyword',
				postType: 'post',
				permalink: 'https://example.com/test',
			};

			const action = updateContentSnapshot( snapshot );

			expect( action ).toEqual( {
				type: UPDATE_CONTENT_SNAPSHOT,
				payload: snapshot,
			} );
		} );

		it( 'should handle empty strings in content snapshot', () => {
			const snapshot: ContentSnapshot = {
				title: '',
				content: '',
				excerpt: '',
				focusKeyword: '',
				postType: '',
				permalink: '',
			};

			const action = updateContentSnapshot( snapshot );

			expect( action.type ).toBe( UPDATE_CONTENT_SNAPSHOT );
			expect( action.payload ).toEqual( snapshot );
		} );
	} );

	describe( 'setAnalyzing', () => {
		it( 'should create an action to set analyzing to true', () => {
			const action = setAnalyzing( true );

			expect( action ).toEqual( {
				type: SET_ANALYZING,
				payload: true,
			} );
		} );

		it( 'should create an action to set analyzing to false', () => {
			const action = setAnalyzing( false );

			expect( action ).toEqual( {
				type: SET_ANALYZING,
				payload: false,
			} );
		} );
	} );

	describe( 'setAnalysisResults', () => {
		it( 'should create an action to set analysis results', () => {
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
				80,
				75,
				1250,
				45,
				8,
				68,
				1.2,
				Date.now()
			);

			expect( action.type ).toBe( SET_ANALYSIS_RESULTS );
			expect( action.payload.seoResults ).toEqual( seoResults );
			expect( action.payload.readabilityResults ).toEqual(
				readabilityResults
			);
			expect( action.payload.seoScore ).toBe( 80 );
			expect( action.payload.readabilityScore ).toBe( 75 );
			expect( action.payload.wordCount ).toBe( 1250 );
			expect( action.payload.sentenceCount ).toBe( 45 );
			expect( action.payload.paragraphCount ).toBe( 8 );
			expect( action.payload.fleschScore ).toBe( 68 );
			expect( action.payload.keywordDensity ).toBe( 1.2 );
		} );

		it( 'should handle empty analysis results', () => {
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

			expect( action.type ).toBe( SET_ANALYSIS_RESULTS );
			expect( action.payload.seoScore ).toBe( 0 );
			expect( action.payload.readabilityScore ).toBe( 0 );
			expect( action.payload.seoResults ).toEqual( [] );
			expect( action.payload.readabilityResults ).toEqual( [] );
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

			expect( action.payload.seoScore ).toBe( 100 );
			expect( action.payload.readabilityScore ).toBe( 100 );
		} );
	} );

	describe( 'setActiveTab', () => {
		it( 'should create an action to set active tab to general', () => {
			const action = setActiveTab( 'general' );

			expect( action ).toEqual( {
				type: SET_ACTIVE_TAB,
				payload: 'general',
			} );
		} );

		it( 'should create an action to set active tab to social', () => {
			const action = setActiveTab( 'social' );

			expect( action ).toEqual( {
				type: SET_ACTIVE_TAB,
				payload: 'social',
			} );
		} );

		it( 'should create an action to set active tab to schema', () => {
			const action = setActiveTab( 'schema' );

			expect( action ).toEqual( {
				type: SET_ACTIVE_TAB,
				payload: 'schema',
			} );
		} );

		it( 'should create an action to set active tab to advanced', () => {
			const action = setActiveTab( 'advanced' );

			expect( action ).toEqual( {
				type: SET_ACTIVE_TAB,
				payload: 'advanced',
			} );
		} );
	} );
} );
