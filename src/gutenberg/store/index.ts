/**
 * Redux Store Registration for meowseo/data
 */

import { createReduxStore, register } from '@wordpress/data';
import { reducer, initialState } from './reducer';
import {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	analyzeContent,
} from './actions';
import * as selectors from './selectors';

const STORE_NAME = 'meowseo/data';

// Action creators object (excluding action type constants)
const actions = {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	analyzeContent,
};

// Create and register the store only if createReduxStore is available
// (in tests, this might be mocked or unavailable)
let store: any = null;

try {
	if ( typeof createReduxStore === 'function' ) {
		// Create the Redux store
		store = createReduxStore( STORE_NAME, {
			reducer,
			actions,
			selectors,
			initialState,
		} );

		// Register the store
		register( store );
	}
} catch ( error ) {
	console.warn( 'Failed to create Redux store:', error );
}

export { STORE_NAME };
export * from './types';
export * from './actions';
export * from './selectors';
