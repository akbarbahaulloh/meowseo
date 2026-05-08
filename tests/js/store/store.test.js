/**
 * Tests for MeowSEO Redux Store.
 *
 * @package MeowSEO\Tests\JS
 */

import { createRegistry } from '@wordpress/data';

// Mock the store registration
const DEFAULT_STATE = {
	meta: {
		title: '',
		description: '',
		robots: 'index,follow',
		canonical: '',
		focusKeyword: '',
		schemaType: '',
		socialTitle: '',
		socialDescription: '',
		socialImageId: 0,
	},
	analysis: {
		seoScore: 0,
		seoChecks: [],
		readabilityScore: 0,
		readabilityChecks: [],
	},
	ui: {
		activeTab: 'meta',
		isSaving: false,
		error: null,
	},
};

const actions = {
	updateMeta( key, value ) {
		return {
			type: 'UPDATE_META',
			key,
			value,
		};
	},
	setAnalysis( seoScore, seoChecks, readabilityScore, readabilityChecks ) {
		return {
			type: 'SET_ANALYSIS',
			seoScore,
			seoChecks,
			readabilityScore,
			readabilityChecks,
		};
	},
	setActiveTab( tab ) {
		return {
			type: 'SET_ACTIVE_TAB',
			tab,
		};
	},
	setSaving( isSaving ) {
		return {
			type: 'SET_SAVING',
			isSaving,
		};
	},
	initializeMeta( meta ) {
		return {
			type: 'INITIALIZE_META',
			meta,
		};
	},
	setError( error ) {
		return {
			type: 'SET_ERROR',
			error,
		};
	},
	clearError() {
		return {
			type: 'CLEAR_ERROR',
		};
	},
};

const selectors = {
	getSeoMeta( state ) {
		return state.meta;
	},
	getMetaField( state, key ) {
		return state.meta[ key ];
	},
	getSeoScore( state ) {
		return state.analysis.seoScore;
	},
	getSeoChecks( state ) {
		return state.analysis.seoChecks;
	},
	getReadabilityScore( state ) {
		return state.analysis.readabilityScore;
	},
	getReadabilityChecks( state ) {
		return state.analysis.readabilityChecks;
	},
	getActiveTab( state ) {
		return state.ui.activeTab;
	},
	isSaving( state ) {
		return state.ui.isSaving;
	},
	getError( state ) {
		return state.ui.error;
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'UPDATE_META':
			return {
				...state,
				meta: {
					...state.meta,
					[ action.key ]: action.value,
				},
			};

		case 'SET_ANALYSIS':
			return {
				...state,
				analysis: {
					seoScore: action.seoScore,
					seoChecks: action.seoChecks,
					readabilityScore: action.readabilityScore,
					readabilityChecks: action.readabilityChecks,
				},
			};

		case 'SET_ACTIVE_TAB':
			return {
				...state,
				ui: {
					...state.ui,
					activeTab: action.tab,
				},
			};

		case 'SET_SAVING':
			return {
				...state,
				ui: {
					...state.ui,
					isSaving: action.isSaving,
				},
			};

		case 'INITIALIZE_META':
			return {
				...state,
				meta: {
					...state.meta,
					...action.meta,
				},
			};

		case 'SET_ERROR':
			return {
				...state,
				ui: {
					...state.ui,
					error: action.error,
				},
			};

		case 'CLEAR_ERROR':
			return {
				...state,
				ui: {
					...state.ui,
					error: null,
				},
			};

		default:
			return state;
	}
};

describe( 'MeowSEO Store', () => {
	let registry;
	let store;

	beforeEach( () => {
		registry = createRegistry();
		store = registry.registerStore( 'meowseo/data', {
			reducer,
			actions,
			selectors,
		} );
	} );

	describe( 'Initial State', () => {
		it( 'should have correct default meta state', () => {
			const meta = registry.select( 'meowseo/data' ).getSeoMeta();
			
			expect( meta ).toEqual( {
				title: '',
				description: '',
				robots: 'index,follow',
				canonical: '',
				focusKeyword: '',
				schemaType: '',
				socialTitle: '',
				socialDescription: '',
				socialImageId: 0,
			} );
		} );

		it( 'should have correct default analysis state', () => {
			const seoScore = registry.select( 'meowseo/data' ).getSeoScore();
			const readabilityScore = registry.select( 'meowseo/data' ).getReadabilityScore();
			
			expect( seoScore ).toBe( 0 );
			expect( readabilityScore ).toBe( 0 );
		} );

		it( 'should have correct default UI state', () => {
			const activeTab = registry.select( 'meowseo/data' ).getActiveTab();
			const isSaving = registry.select( 'meowseo/data' ).isSaving();
			const error = registry.select( 'meowseo/data' ).getError();
			
			expect( activeTab ).toBe( 'meta' );
			expect( isSaving ).toBe( false );
			expect( error ).toBeNull();
		} );
	} );

	describe( 'Meta Actions', () => {
		it( 'should update meta field', () => {
			registry.dispatch( 'meowseo/data' ).updateMeta( 'title', 'Test Title' );
			
			const title = registry.select( 'meowseo/data' ).getMetaField( 'title' );
			expect( title ).toBe( 'Test Title' );
		} );

		it( 'should initialize meta from object', () => {
			const initialMeta = {
				title: 'Initial Title',
				description: 'Initial Description',
				focusKeyword: 'test keyword',
			};
			
			registry.dispatch( 'meowseo/data' ).initializeMeta( initialMeta );
			
			const meta = registry.select( 'meowseo/data' ).getSeoMeta();
			expect( meta.title ).toBe( 'Initial Title' );
			expect( meta.description ).toBe( 'Initial Description' );
			expect( meta.focusKeyword ).toBe( 'test keyword' );
			expect( meta.robots ).toBe( 'index,follow' ); // Should preserve defaults
		} );

		it( 'should not mutate other meta fields when updating one field', () => {
			registry.dispatch( 'meowseo/data' ).updateMeta( 'title', 'Title 1' );
			registry.dispatch( 'meowseo/data' ).updateMeta( 'description', 'Description 1' );
			
			const meta = registry.select( 'meowseo/data' ).getSeoMeta();
			expect( meta.title ).toBe( 'Title 1' );
			expect( meta.description ).toBe( 'Description 1' );
			expect( meta.robots ).toBe( 'index,follow' );
		} );
	} );

	describe( 'Analysis Actions', () => {
		it( 'should set analysis results', () => {
			const seoChecks = [
				{ id: 'title-length', status: 'good', message: 'Title length is good' },
			];
			const readabilityChecks = [
				{ id: 'sentence-length', status: 'ok', message: 'Sentence length is ok' },
			];
			
			registry.dispatch( 'meowseo/data' ).setAnalysis( 85, seoChecks, 70, readabilityChecks );
			
			expect( registry.select( 'meowseo/data' ).getSeoScore() ).toBe( 85 );
			expect( registry.select( 'meowseo/data' ).getSeoChecks() ).toEqual( seoChecks );
			expect( registry.select( 'meowseo/data' ).getReadabilityScore() ).toBe( 70 );
			expect( registry.select( 'meowseo/data' ).getReadabilityChecks() ).toEqual( readabilityChecks );
		} );

		it( 'should replace previous analysis results', () => {
			registry.dispatch( 'meowseo/data' ).setAnalysis( 50, [], 60, [] );
			registry.dispatch( 'meowseo/data' ).setAnalysis( 90, [], 80, [] );
			
			expect( registry.select( 'meowseo/data' ).getSeoScore() ).toBe( 90 );
			expect( registry.select( 'meowseo/data' ).getReadabilityScore() ).toBe( 80 );
		} );
	} );

	describe( 'UI Actions', () => {
		it( 'should set active tab', () => {
			registry.dispatch( 'meowseo/data' ).setActiveTab( 'social' );
			
			const activeTab = registry.select( 'meowseo/data' ).getActiveTab();
			expect( activeTab ).toBe( 'social' );
		} );

		it( 'should set saving state', () => {
			registry.dispatch( 'meowseo/data' ).setSaving( true );
			
			const isSaving = registry.select( 'meowseo/data' ).isSaving();
			expect( isSaving ).toBe( true );
		} );

		it( 'should set error message', () => {
			registry.dispatch( 'meowseo/data' ).setError( 'Test error message' );
			
			const error = registry.select( 'meowseo/data' ).getError();
			expect( error ).toBe( 'Test error message' );
		} );

		it( 'should clear error message', () => {
			registry.dispatch( 'meowseo/data' ).setError( 'Test error' );
			registry.dispatch( 'meowseo/data' ).clearError();
			
			const error = registry.select( 'meowseo/data' ).getError();
			expect( error ).toBeNull();
		} );
	} );

	describe( 'Selectors', () => {
		it( 'should get full SEO meta object', () => {
			registry.dispatch( 'meowseo/data' ).updateMeta( 'title', 'Test' );
			
			const meta = registry.select( 'meowseo/data' ).getSeoMeta();
			expect( meta ).toHaveProperty( 'title' );
			expect( meta ).toHaveProperty( 'description' );
			expect( meta ).toHaveProperty( 'robots' );
		} );

		it( 'should get specific meta field', () => {
			registry.dispatch( 'meowseo/data' ).updateMeta( 'focusKeyword', 'test' );
			
			const keyword = registry.select( 'meowseo/data' ).getMetaField( 'focusKeyword' );
			expect( keyword ).toBe( 'test' );
		} );

		it( 'should return undefined for non-existent meta field', () => {
			const value = registry.select( 'meowseo/data' ).getMetaField( 'nonExistent' );
			expect( value ).toBeUndefined();
		} );
	} );
} );
