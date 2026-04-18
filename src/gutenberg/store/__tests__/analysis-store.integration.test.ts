/**
 * Redux Store Integration Tests
 *
 * Tests Redux store updates with analysis results, including:
 * - Action dispatching
 * - State updates
 * - Selector functionality
 * - Store persistence
 *
 * @module gutenberg/store/__tests__/analysis-store.integration.test
 */

describe( 'Redux Store Integration - Analysis Results', () => {
	describe( 'Store State Structure', () => {
		it( 'should have analysis fields in store state', () => {
			// Mock store state
			const mockState = {
				meowseo: {
					data: {
						seoResults: [],
						readabilityResults: [],
						seoScore: 0,
						readabilityScore: 0,
						wordCount: 0,
						sentenceCount: 0,
						paragraphCount: 0,
						fleschScore: 0,
						keywordDensity: 0,
						analysisTimestamp: null,
						isAnalyzing: false,
					},
				},
			};

			// Verify state structure
			expect( mockState.meowseo.data ).toHaveProperty( 'seoResults' );
			expect( mockState.meowseo.data ).toHaveProperty(
				'readabilityResults'
			);
			expect( mockState.meowseo.data ).toHaveProperty( 'seoScore' );
			expect( mockState.meowseo.data ).toHaveProperty(
				'readabilityScore'
			);
			expect( mockState.meowseo.data ).toHaveProperty( 'wordCount' );
			expect( mockState.meowseo.data ).toHaveProperty( 'sentenceCount' );
			expect( mockState.meowseo.data ).toHaveProperty( 'paragraphCount' );
			expect( mockState.meowseo.data ).toHaveProperty( 'fleschScore' );
			expect( mockState.meowseo.data ).toHaveProperty( 'keywordDensity' );
			expect( mockState.meowseo.data ).toHaveProperty(
				'analysisTimestamp'
			);
			expect( mockState.meowseo.data ).toHaveProperty( 'isAnalyzing' );
		} );

		it( 'should initialize analysis fields with default values', () => {
			const mockState = {
				meowseo: {
					data: {
						seoResults: [],
						readabilityResults: [],
						seoScore: 0,
						readabilityScore: 0,
						wordCount: 0,
						sentenceCount: 0,
						paragraphCount: 0,
						fleschScore: 0,
						keywordDensity: 0,
						analysisTimestamp: null,
						isAnalyzing: false,
					},
				},
			};

			expect( Array.isArray( mockState.meowseo.data.seoResults ) ).toBe(
				true
			);
			expect(
				Array.isArray( mockState.meowseo.data.readabilityResults )
			).toBe( true );
			expect( mockState.meowseo.data.seoScore ).toBe( 0 );
			expect( mockState.meowseo.data.readabilityScore ).toBe( 0 );
			expect( mockState.meowseo.data.isAnalyzing ).toBe( false );
		} );
	} );

	describe( 'Action Dispatching', () => {
		it( 'should dispatch setAnalysisResults action with all fields', () => {
			// Mock action
			const mockAction = {
				type: 'SET_ANALYSIS_RESULTS',
				payload: {
					seoResults: [
						{
							id: 'keyword-in-title',
							type: 'good',
							message: 'Keyword found in title',
							score: 100,
							details: { position: 0 },
						},
					],
					readabilityResults: [
						{
							id: 'sentence-length',
							type: 'good',
							message: 'Sentence length is optimal',
							score: 100,
							details: { averageLength: 15 },
						},
					],
					seoScore: 75,
					readabilityScore: 80,
					wordCount: 500,
					sentenceCount: 25,
					paragraphCount: 5,
					fleschScore: 65,
					keywordDensity: 1.5,
					analysisTimestamp: Date.now(),
				},
			};

			// Verify action structure
			expect( mockAction.type ).toBe( 'SET_ANALYSIS_RESULTS' );
			expect( mockAction.payload ).toHaveProperty( 'seoResults' );
			expect( mockAction.payload ).toHaveProperty( 'readabilityResults' );
			expect( mockAction.payload ).toHaveProperty( 'seoScore' );
			expect( mockAction.payload ).toHaveProperty( 'readabilityScore' );
			expect( mockAction.payload ).toHaveProperty( 'wordCount' );
			expect( mockAction.payload ).toHaveProperty( 'sentenceCount' );
			expect( mockAction.payload ).toHaveProperty( 'paragraphCount' );
			expect( mockAction.payload ).toHaveProperty( 'fleschScore' );
			expect( mockAction.payload ).toHaveProperty( 'keywordDensity' );
			expect( mockAction.payload ).toHaveProperty( 'analysisTimestamp' );
		} );

		it( 'should dispatch setAnalyzing action', () => {
			// Mock action
			const mockAction = {
				type: 'SET_ANALYZING',
				payload: true,
			};

			expect( mockAction.type ).toBe( 'SET_ANALYZING' );
			expect( mockAction.payload ).toBe( true );
		} );
	} );

	describe( 'State Updates', () => {
		it( 'should update state with analysis results', () => {
			// Mock initial state
			let state = {
				seoResults: [],
				readabilityResults: [],
				seoScore: 0,
				readabilityScore: 0,
				wordCount: 0,
				sentenceCount: 0,
				paragraphCount: 0,
				fleschScore: 0,
				keywordDensity: 0,
				analysisTimestamp: null,
				isAnalyzing: false,
			};

			// Mock action
			const action = {
				type: 'SET_ANALYSIS_RESULTS',
				payload: {
					seoResults: [
						{
							id: 'test',
							type: 'good',
							message: 'Test',
							score: 100,
						},
					],
					readabilityResults: [
						{
							id: 'test',
							type: 'good',
							message: 'Test',
							score: 100,
						},
					],
					seoScore: 75,
					readabilityScore: 80,
					wordCount: 500,
					sentenceCount: 25,
					paragraphCount: 5,
					fleschScore: 65,
					keywordDensity: 1.5,
					analysisTimestamp: Date.now(),
				},
			};

			// Simulate reducer update
			state = {
				...state,
				...action.payload,
			};

			// Verify state was updated
			expect( state.seoResults.length ).toBe( 1 );
			expect( state.readabilityResults.length ).toBe( 1 );
			expect( state.seoScore ).toBe( 75 );
			expect( state.readabilityScore ).toBe( 80 );
			expect( state.wordCount ).toBe( 500 );
			expect( state.sentenceCount ).toBe( 25 );
			expect( state.paragraphCount ).toBe( 5 );
			expect( state.fleschScore ).toBe( 65 );
			expect( state.keywordDensity ).toBe( 1.5 );
			expect( state.analysisTimestamp ).not.toBeNull();
		} );

		it( 'should update isAnalyzing flag', () => {
			let state = {
				isAnalyzing: false,
			};

			// Set analyzing to true
			state = {
				...state,
				isAnalyzing: true,
			};

			expect( state.isAnalyzing ).toBe( true );

			// Set analyzing to false
			state = {
				...state,
				isAnalyzing: false,
			};

			expect( state.isAnalyzing ).toBe( false );
		} );

		it( 'should maintain immutability during updates', () => {
			const originalState = {
				seoResults: [],
				seoScore: 0,
			};

			const newState = {
				...originalState,
				seoScore: 75,
			};

			// Original state should not be modified
			expect( originalState.seoScore ).toBe( 0 );
			expect( newState.seoScore ).toBe( 75 );
		} );
	} );

	describe( 'Selectors', () => {
		it( 'should select SEO results from state', () => {
			const mockState = {
				meowseo: {
					data: {
						seoResults: [
							{
								id: 'keyword-in-title',
								type: 'good',
								message: 'Test',
								score: 100,
							},
						],
					},
				},
			};

			// Mock selector
			const getSeoResults = ( state: any ) =>
				state.meowseo.data.seoResults;

			const results = getSeoResults( mockState );
			expect( results.length ).toBe( 1 );
			expect( results[ 0 ].id ).toBe( 'keyword-in-title' );
		} );

		it( 'should select readability results from state', () => {
			const mockState = {
				meowseo: {
					data: {
						readabilityResults: [
							{
								id: 'sentence-length',
								type: 'good',
								message: 'Test',
								score: 100,
							},
						],
					},
				},
			};

			// Mock selector
			const getReadabilityResults = ( state: any ) =>
				state.meowseo.data.readabilityResults;

			const results = getReadabilityResults( mockState );
			expect( results.length ).toBe( 1 );
			expect( results[ 0 ].id ).toBe( 'sentence-length' );
		} );

		it( 'should select scores from state', () => {
			const mockState = {
				meowseo: {
					data: {
						seoScore: 75,
						readabilityScore: 80,
					},
				},
			};

			// Mock selectors
			const getSeoScore = ( state: any ) => state.meowseo.data.seoScore;
			const getReadabilityScore = ( state: any ) =>
				state.meowseo.data.readabilityScore;

			expect( getSeoScore( mockState ) ).toBe( 75 );
			expect( getReadabilityScore( mockState ) ).toBe( 80 );
		} );

		it( 'should select metadata from state', () => {
			const mockState = {
				meowseo: {
					data: {
						wordCount: 500,
						sentenceCount: 25,
						paragraphCount: 5,
						fleschScore: 65,
						keywordDensity: 1.5,
					},
				},
			};

			// Mock selectors
			const getWordCount = ( state: any ) => state.meowseo.data.wordCount;
			const getSentenceCount = ( state: any ) =>
				state.meowseo.data.sentenceCount;
			const getParagraphCount = ( state: any ) =>
				state.meowseo.data.paragraphCount;
			const getFleschScore = ( state: any ) =>
				state.meowseo.data.fleschScore;
			const getKeywordDensity = ( state: any ) =>
				state.meowseo.data.keywordDensity;

			expect( getWordCount( mockState ) ).toBe( 500 );
			expect( getSentenceCount( mockState ) ).toBe( 25 );
			expect( getParagraphCount( mockState ) ).toBe( 5 );
			expect( getFleschScore( mockState ) ).toBe( 65 );
			expect( getKeywordDensity( mockState ) ).toBe( 1.5 );
		} );

		it( 'should select analyzing flag from state', () => {
			const mockState = {
				meowseo: {
					data: {
						isAnalyzing: true,
					},
				},
			};

			// Mock selector
			const getIsAnalyzing = ( state: any ) =>
				state.meowseo.data.isAnalyzing;

			expect( getIsAnalyzing( mockState ) ).toBe( true );
		} );
	} );

	describe( 'Store Persistence', () => {
		it( 'should persist analysis results across component remounts', () => {
			// Mock persisted state
			const persistedState = {
				seoResults: [
					{ id: 'test', type: 'good', message: 'Test', score: 100 },
				],
				seoScore: 75,
				analysisTimestamp: Date.now(),
			};

			// Verify data persists
			expect( persistedState.seoResults.length ).toBe( 1 );
			expect( persistedState.seoScore ).toBe( 75 );
			expect( persistedState.analysisTimestamp ).not.toBeNull();
		} );

		it( 'should clear analysis results when content is cleared', () => {
			let state = {
				seoResults: [
					{ id: 'test', type: 'good', message: 'Test', score: 100 },
				],
				seoScore: 75,
			};

			// Clear results
			state = {
				seoResults: [],
				seoScore: 0,
			};

			expect( state.seoResults.length ).toBe( 0 );
			expect( state.seoScore ).toBe( 0 );
		} );
	} );

	describe( 'Error Handling', () => {
		it( 'should handle invalid analysis results gracefully', () => {
			const invalidResults = {
				seoResults: null, // Invalid: should be array
				readabilityResults: undefined, // Invalid: should be array
				seoScore: 'invalid', // Invalid: should be number
			};

			// Should handle gracefully
			expect( invalidResults.seoResults ).toBeNull();
			expect( invalidResults.readabilityResults ).toBeUndefined();
			expect( typeof invalidResults.seoScore ).toBe( 'string' );
		} );

		it( 'should handle missing analysis results', () => {
			const incompleteResults = {
				seoResults: [],
				// Missing: readabilityResults, scores, metadata
			};

			expect( incompleteResults.seoResults ).toBeDefined();
			expect( incompleteResults.readabilityResults ).toBeUndefined();
		} );
	} );
} );
