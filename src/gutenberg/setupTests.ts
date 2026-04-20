import '@testing-library/jest-dom';

// Mock @wordpress/private-apis
jest.mock( '@wordpress/private-apis', () => ( {
	__dangerousOptInToUnstableAPIsOnlyForCoreModules: jest.fn( () => ( {
		lock: jest.fn(),
		unlock: jest.fn( () => ( {} ) ),
	} ) ),
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => {
	const createMockSelect = () => ( storeName: string ) => {
		if ( storeName === 'meowseo/data' ) {
			// Return meowseo/data store selectors
			return {
				getActiveTab: () => 'general',
				getSeoScore: () => 75,
				getReadabilityScore: () => 60,
				getSeoResults: () => [],
				getReadabilityResults: () => [],
				getAnalysisResults: () => [],
				isAnalyzing: () => false,
				getIsAnalyzing: () => false,
				getContentSnapshot: () => ( {
					title: '',
					content: '',
					excerpt: '',
					focusKeyword: '',
					postType: 'post',
					permalink: '',
				} ),
				getDirectAnswer: () => '',
				getSchemaType: () => '',
				getWordCount: () => 0,
				getSentenceCount: () => 0,
				getParagraphCount: () => 0,
				getFleschScore: () => 0,
				getKeywordDensity: () => 0,
				getAnalysisTimestamp: () => null,
				getSeoScoreColor: () => 'green',
				getReadabilityScoreColor: () => 'green',
				getAnalysisResultsByType: () => ( {
					good: [],
					ok: [],
					problem: [],
				} ),
			};
		}
		if ( storeName === 'core/editor' ) {
			// Return core/editor store selectors
			return {
				getEditedPostAttribute: () => '',
				getCurrentPostType: () => 'post',
				getCurrentPostId: () => 1,
				getPermalink: () => 'https://example.com/post',
			};
		}
		if ( storeName === 'core/block-editor' ) {
			// Return core/block-editor store selectors
			return {
				getSelectedBlockClientId: () => null,
				getBlock: () => null,
				getBlocks: () => [],
			};
		}
		// Return empty object for unknown stores
		return {};
	};

	return {
		...jest.requireActual( '@wordpress/data' ),
		createRegistrySelector: jest.fn( ( selector ) => selector ),
		createReduxStore: jest.fn( ( name, config ) => ( {
			name,
			reducer: config.reducer,
			actions: config.actions,
			selectors: config.selectors,
		} ) ),
		register: jest.fn(),
		useSelect: jest.fn( ( selector ) => {
			// Implement proper selector callback pattern
			// The selector function receives a select function that returns store-specific selectors
			const mockSelect = createMockSelect();
			// Call the selector function with the mock select function
			return selector( mockSelect );
		} ),
		useDispatch: jest.fn( ( storeName ) => {
			if ( storeName === 'meowseo/data' ) {
				// Return meowseo/data store action creators
				return {
					setActiveTab: jest.fn(),
					updateAnalysis: jest.fn(),
					setAnalyzing: jest.fn(),
					setAnalysisResults: jest.fn(),
					updateContentSnapshot: jest.fn(),
					analyzeContent: jest.fn(),
				};
			}
			if ( storeName === 'core/editor' ) {
				// Return core/editor store action creators
				return {
					editPost: jest.fn(),
					savePost: jest.fn(),
				};
			}
			if ( storeName === 'core/block-editor' ) {
				// Return core/block-editor store action creators
				return {
					updateBlockAttributes: jest.fn(),
					selectBlock: jest.fn(),
				};
			}
			// Return generic dispatch for unknown stores
			return {
				updateBlockAttributes: jest.fn(),
			};
		} ),
	};
} );

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch', () => jest.fn( () => Promise.resolve( {} ) ) );

// Mock @wordpress/core-data
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn( () => [ '', jest.fn() ] ),
	useEntityRecord: jest.fn( () => ( { record: {}, isResolving: false } ) ),
} ) );

// Mock @wordpress/block-editor
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn( () => ( {} ) ),
	InnerBlocks: () => <div />,
	BlockControls: ( { children }: any ) => <div>{ children }</div>,
	InspectorControls: ( { children }: any ) => <div>{ children }</div>,
} ) );

// Mock @wordpress/plugins
jest.mock( '@wordpress/plugins', () => ( {
	registerPlugin: jest.fn(),
	PluginSidebar: ( { children }: any ) => <div>{ children }</div>,
} ) );

// Mock WordPress globals
global.wp = {
	data: {
		select: ( storeName: string ) => {
			if ( storeName === 'meowseo/data' ) {
				return {
					getActiveTab: () => 'general',
					getSeoScore: () => 75,
					getReadabilityScore: () => 60,
					getSeoResults: () => [],
					getReadabilityResults: () => [],
					getAnalysisResults: () => [],
					isAnalyzing: () => false,
					getIsAnalyzing: () => false,
					getContentSnapshot: () => ( {
						title: '',
						content: '',
						excerpt: '',
						focusKeyword: '',
						postType: 'post',
						permalink: '',
					} ),
					getDirectAnswer: () => '',
					getSchemaType: () => '',
				};
			}
			if ( storeName === 'core/editor' ) {
				return {
					getEditedPostAttribute: () => '',
					getCurrentPostType: () => 'post',
					getCurrentPostId: () => 1,
					getPermalink: () => 'https://example.com/post',
				};
			}
			return {};
		},
		dispatch: ( storeName: string ) => {
			if ( storeName === 'meowseo/data' ) {
				return {
					setActiveTab: jest.fn(),
					updateAnalysis: jest.fn(),
					setAnalyzing: jest.fn(),
					setAnalysisResults: jest.fn(),
					updateContentSnapshot: jest.fn(),
					analyzeContent: jest.fn(),
				};
			}
			if ( storeName === 'core/editor' ) {
				return {
					editPost: jest.fn(),
					savePost: jest.fn(),
				};
			}
			return {};
		},
	},
	apiFetch: jest.fn( () => Promise.resolve( {} ) ),
} as any;

// Mock Web Worker for tests
class MockWorker {
	url: string;
	onmessage: ( ( event: MessageEvent ) => void ) | null = null;
	onerror: ( ( event: ErrorEvent ) => void ) | null = null;

	constructor( stringUrl: string ) {
		this.url = stringUrl;
	}

	postMessage( msg: any ) {
		// Mock implementation
	}

	addEventListener( type: string, listener: EventListener ) {
		// Mock implementation
	}

	removeEventListener( type: string, listener: EventListener ) {
		// Mock implementation
	}

	terminate() {
		// Mock implementation
	}
}

global.Worker = MockWorker as any;

// Mock the useAnalysis hook to avoid import.meta issues
jest.mock( '../hooks/useAnalysis', () => ( {
	useAnalysis: jest.fn(),
} ) );
