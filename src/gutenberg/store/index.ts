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
} from './actions';
import * as selectors from './selectors';

const STORE_NAME = 'meowseo/data';

// Action creators object (excluding action type constants)
const actions = {
  updateContentSnapshot,
  setAnalyzing,
  setAnalysisResults,
  setActiveTab,
};

// Create the Redux store
const store = createReduxStore(STORE_NAME, {
  reducer,
  actions,
  selectors,
  initialState,
});

// Register the store
register(store);

export { STORE_NAME };
export * from './types';
export * from './actions';
export * from './selectors';
