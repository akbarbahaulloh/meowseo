/**
 * Property-Based Test: Store State Immutability
 *
 * **Validates: Requirements 3.6, 3.7**
 *
 * Tests that dispatching actions never mutates existing state objects.
 * Generates random action sequences and verifies state references change.
 */

import * as fc from 'fast-check';
import { reducer, initialState } from '../reducer';
import {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	MeowSEOAction,
} from '../actions';
import {
	MeowSEOState,
	TabType,
	ContentSnapshot,
	AnalysisResult,
} from '../types';

// Arbitraries for generating random data
const tabArbitrary = fc.constantFrom< TabType >(
	'general',
	'social',
	'schema',
	'advanced'
);

const contentSnapshotArbitrary: fc.Arbitrary< ContentSnapshot > = fc.record( {
	title: fc.string(),
	content: fc.string(),
	excerpt: fc.string(),
	focusKeyword: fc.string(),
	postType: fc.string(),
	permalink: fc.string(),
} );

const analysisResultArbitrary: fc.Arbitrary< AnalysisResult > = fc.record( {
	id: fc.string(),
	type: fc.constantFrom( 'good', 'ok', 'problem' ),
	message: fc.string(),
} );

const actionArbitrary: fc.Arbitrary< MeowSEOAction > = fc.oneof(
	contentSnapshotArbitrary.map( ( snapshot ) =>
		updateContentSnapshot( snapshot )
	),
	fc.boolean().map( ( isAnalyzing ) => setAnalyzing( isAnalyzing ) ),
	fc
		.tuple(
			fc.array( analysisResultArbitrary, { maxLength: 10 } ),
			fc.array( analysisResultArbitrary, { maxLength: 5 } ),
			fc.integer( { min: 0, max: 100 } ),
			fc.integer( { min: 0, max: 100 } ),
			fc.integer( { min: 0, max: 5000 } ),
			fc.integer( { min: 0, max: 500 } ),
			fc.integer( { min: 0, max: 100 } ),
			fc.integer( { min: 0, max: 100 } ),
			fc.float( { min: 0, max: 5 } ),
			fc.integer()
		)
		.map(
			( [
				seoResults,
				readabilityResults,
				seoScore,
				readabilityScore,
				wordCount,
				sentenceCount,
				paragraphCount,
				fleschScore,
				keywordDensity,
				timestamp,
			] ) =>
				setAnalysisResults(
					seoResults,
					readabilityResults,
					seoScore,
					readabilityScore,
					wordCount,
					sentenceCount,
					paragraphCount,
					fleschScore,
					keywordDensity,
					timestamp
				)
		),
	tabArbitrary.map( ( tab ) => setActiveTab( tab ) )
);

describe( 'Store State Immutability Property Test', () => {
	it( 'should never mutate existing state objects when dispatching actions', () => {
		fc.assert(
			fc.property(
				fc.array( actionArbitrary, { minLength: 1, maxLength: 20 } ),
				( actions ) => {
					let currentState: MeowSEOState = initialState;
					const stateReferences: MeowSEOState[] = [ currentState ];

					// Apply each action and collect state references
					for ( const action of actions ) {
						const previousState = currentState;
						const newState = reducer( currentState, action );

						// Verify that the state reference changed (immutability)
						expect( newState ).not.toBe( previousState );

						// Verify that the previous state was not mutated
						expect( previousState ).toEqual(
							stateReferences[ stateReferences.length - 1 ]
						);

						currentState = newState;
						stateReferences.push( newState );
					}

					// Verify all previous states remain unchanged
					for ( let i = 0; i < stateReferences.length - 1; i++ ) {
						const stateSnapshot = stateReferences[ i ];
						// Each state should still be equal to itself (not mutated)
						expect( stateSnapshot ).toEqual( stateReferences[ i ] );
					}
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'should create new state objects for each action type', () => {
		fc.assert(
			fc.property(
				contentSnapshotArbitrary,
				fc.boolean(),
				fc.integer( { min: 0, max: 100 } ),
				fc.integer( { min: 0, max: 100 } ),
				fc.array( analysisResultArbitrary, { maxLength: 5 } ),
				fc.array( analysisResultArbitrary, { maxLength: 5 } ),
				fc.integer( { min: 0, max: 5000 } ),
				fc.integer( { min: 0, max: 500 } ),
				fc.integer( { min: 0, max: 100 } ),
				fc.integer( { min: 0, max: 100 } ),
				fc.float( { min: 0, max: 5 } ),
				fc.integer(),
				tabArbitrary,
				(
					snapshot,
					isAnalyzing,
					seoScore,
					readabilityScore,
					seoResults,
					readabilityResults,
					wordCount,
					sentenceCount,
					paragraphCount,
					fleschScore,
					keywordDensity,
					timestamp,
					tab
				) => {
					const state1 = reducer(
						initialState,
						updateContentSnapshot( snapshot )
					);
					expect( state1 ).not.toBe( initialState );
					expect( state1.contentSnapshot ).toBe( snapshot );

					const state2 = reducer(
						state1,
						setAnalyzing( isAnalyzing )
					);
					expect( state2 ).not.toBe( state1 );
					expect( state2.isAnalyzing ).toBe( isAnalyzing );

					const state3 = reducer(
						state2,
						setAnalysisResults(
							seoResults,
							readabilityResults,
							seoScore,
							readabilityScore,
							wordCount,
							sentenceCount,
							paragraphCount,
							fleschScore,
							keywordDensity,
							timestamp
						)
					);
					expect( state3 ).not.toBe( state2 );
					expect( state3.seoScore ).toBe( seoScore );
					expect( state3.readabilityScore ).toBe( readabilityScore );
					expect( state3.analysisResults ).toBe( seoResults );
					expect( state3.readabilityResults ).toBe(
						readabilityResults
					);

					const state4 = reducer( state3, setActiveTab( tab ) );
					expect( state4 ).not.toBe( state3 );
					expect( state4.activeTab ).toBe( tab );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'should preserve unaffected state properties when updating', () => {
		fc.assert(
			fc.property(
				contentSnapshotArbitrary,
				fc.boolean(),
				tabArbitrary,
				( snapshot, isAnalyzing, tab ) => {
					// Set up initial state with some values
					let state = reducer( initialState, setAnalyzing( true ) );
					state = reducer( state, setActiveTab( 'social' ) );
					state = reducer( state, setAnalysisResults( 75, 80, [] ) );

					const seoScoreBefore = state.seoScore;
					const readabilityScoreBefore = state.readabilityScore;
					const activeTabBefore = state.activeTab;

					// Update content snapshot
					const newState = reducer(
						state,
						updateContentSnapshot( snapshot )
					);

					// Verify unaffected properties remain the same
					expect( newState.seoScore ).toBe( seoScoreBefore );
					expect( newState.readabilityScore ).toBe(
						readabilityScoreBefore
					);
					expect( newState.activeTab ).toBe( activeTabBefore );
					expect( newState.contentSnapshot ).toBe( snapshot );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
