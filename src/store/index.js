/**
 * MeowSEO Redux Store
 *
 * Registered via @wordpress/data as 'meowseo/data'.
 * Manages SEO meta, analysis results, and UI state.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { registerStore } from '@wordpress/data';

/**
 * Default state shape
 */
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
	},
};

/**
 * Actions
 */
const actions = {
	/**
	 * Update a single meta field
	 *
	 * @param {string} key   Meta field key
	 * @param {*}      value Meta field value
	 * @return {Object} Action object
	 */
	updateMeta( key, value ) {
		return {
			type: 'UPDATE_META',
			key,
			value,
		};
	},

	/**
	 * Set analysis results (called by ContentSyncHook only)
	 *
	 * @param {number} seoScore           SEO score (0-100)
	 * @param {Array}  seoChecks          Array of SEO check results
	 * @param {number} readabilityScore   Readability score (0-100)
	 * @param {Array}  readabilityChecks  Array of readability check results
	 * @return {Object} Action object
	 */
	setAnalysis( seoScore, seoChecks, readabilityScore, readabilityChecks ) {
		return {
			type: 'SET_ANALYSIS',
			seoScore,
			seoChecks,
			readabilityScore,
			readabilityChecks,
		};
	},

	/**
	 * Set active sidebar tab
	 *
	 * @param {string} tab Tab name
	 * @return {Object} Action object
	 */
	setActiveTab( tab ) {
		return {
			type: 'SET_ACTIVE_TAB',
			tab,
		};
	},

	/**
	 * Set saving state
	 *
	 * @param {boolean} isSaving Whether save is in progress
	 * @return {Object} Action object
	 */
	setSaving( isSaving ) {
		return {
			type: 'SET_SAVING',
			isSaving,
		};
	},

	/**
	 * Initialize meta from postmeta
	 *
	 * @param {Object} meta Meta object
	 * @return {Object} Action object
	 */
	initializeMeta( meta ) {
		return {
			type: 'INITIALIZE_META',
			meta,
		};
	},
};

/**
 * Selectors
 */
const selectors = {
	/**
	 * Get full SEO meta object
	 *
	 * @param {Object} state Store state
	 * @return {Object} Meta object
	 */
	getSeoMeta( state ) {
		return state.meta;
	},

	/**
	 * Get a single meta field value
	 *
	 * @param {Object} state Store state
	 * @param {string} key   Meta field key
	 * @return {*} Meta field value
	 */
	getMetaField( state, key ) {
		return state.meta[ key ];
	},

	/**
	 * Get SEO score
	 *
	 * @param {Object} state Store state
	 * @return {number} SEO score (0-100)
	 */
	getSeoScore( state ) {
		return state.analysis.seoScore;
	},

	/**
	 * Get SEO checks array
	 *
	 * @param {Object} state Store state
	 * @return {Array} Array of check result objects
	 */
	getSeoChecks( state ) {
		return state.analysis.seoChecks;
	},

	/**
	 * Get readability score
	 *
	 * @param {Object} state Store state
	 * @return {number} Readability score (0-100)
	 */
	getReadabilityScore( state ) {
		return state.analysis.readabilityScore;
	},

	/**
	 * Get readability checks array
	 *
	 * @param {Object} state Store state
	 * @return {Array} Array of check result objects
	 */
	getReadabilityChecks( state ) {
		return state.analysis.readabilityChecks;
	},

	/**
	 * Get active sidebar tab
	 *
	 * @param {Object} state Store state
	 * @return {string} Active tab name
	 */
	getActiveTab( state ) {
		return state.ui.activeTab;
	},

	/**
	 * Get saving state
	 *
	 * @param {Object} state Store state
	 * @return {boolean} Whether save is in progress
	 */
	isSaving( state ) {
		return state.ui.isSaving;
	},
};

/**
 * Reducer
 *
 * @param {Object} state  Current state
 * @param {Object} action Action object
 * @return {Object} New state
 */
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

		default:
			return state;
	}
};

/**
 * Register the store
 */
registerStore( 'meowseo/data', {
	reducer,
	actions,
	selectors,
} );

export default 'meowseo/data';
