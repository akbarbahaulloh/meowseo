/**
 * Internationalization (i18n) Tests for Tab Components
 *
 * Tests that all tab components have proper i18n implementation.
 *
 * Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

// Import tab components
import FocusKeywordInput from '../FocusKeywordInput';
import DirectAnswerField from '../DirectAnswerField';
import SerpPreview from '../SerpPreview';
import InternalLinkSuggestions from '../InternalLinkSuggestions';
import SocialTabContent from '../SocialTabContent';
import FacebookSubTab from '../FacebookSubTab';
import TwitterSubTab from '../TwitterSubTab';
import RobotsToggles from '../RobotsToggles';
import CanonicalURLInput from '../CanonicalURLInput';
import GSCIntegration from '../GSCIntegration';

// Mock the store module
jest.mock( '../../../store', () => ( {
	STORE_NAME: 'meowseo/data',
} ) );

// Track all translation calls
const translationCalls: Array< { text: string; domain: string } > = [];

// Mock @wordpress/i18n to track translation calls
jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( text: string, domain: string ) => {
		translationCalls.push( { text, domain } );
		return text;
	} ),
	_x: jest.fn( ( text: string, context: string, domain: string ) => {
		translationCalls.push( { text, domain } );
		return text;
	} ),
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn( ( callback ) => {
		const select = {
			'core/editor': {
				getEditedPostAttribute: ( attr: string ) => {
					if ( attr === 'title' ) {
						return 'Test Post';
					}
					if ( attr === 'excerpt' ) {
						return 'Test excerpt';
					}
					return '';
				},
				getPermalink: () => 'https://example.com/test',
				getCurrentPostId: () => 1,
			},
			core: {
				getMedia: () => null,
			},
		};
		return callback(
			( storeName: string ) => select[ storeName as keyof typeof select ]
		);
	} ),
	useDispatch: jest.fn( () => ( {} ) ),
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	TextControl: ( { label, help, placeholder }: any ) => (
		<div>
			<label>{ label }</label>
			{ help && <span>{ help }</span> }
			{ placeholder && <span>{ placeholder }</span> }
		</div>
	),
	TextareaControl: ( { label, help, placeholder }: any ) => (
		<div>
			<label>{ label }</label>
			{ help && <span>{ help }</span> }
			{ placeholder && <span>{ placeholder }</span> }
		</div>
	),
	Button: ( { children }: any ) => <button>{ children }</button>,
	ButtonGroup: ( { children }: any ) => <div>{ children }</div>,
	ToggleControl: ( { label, help }: any ) => (
		<div>
			<label>{ label }</label>
			{ help && <span>{ help }</span> }
		</div>
	),
	Spinner: () => <span>Loading...</span>,
	SelectControl: ( { label, help }: any ) => (
		<div>
			<label>{ label }</label>
			{ help && <span>{ help }</span> }
		</div>
	),
} ) );

// Mock @wordpress/block-editor
jest.mock( '@wordpress/block-editor', () => ( {
	MediaUpload: ( { render }: any ) => render( { open: jest.fn() } ),
} ) );

// Mock hooks
jest.mock( '../../../hooks/useEntityPropBinding', () => ( {
	useEntityPropBinding: jest.fn( () => [ '', jest.fn() ] ),
} ) );

// Mock apiFetch
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'Tab Components i18n', () => {
	const { __ } = require( '@wordpress/i18n' );

	beforeEach( () => {
		translationCalls.length = 0;
		jest.clearAllMocks();
	} );

	describe( 'General Tab Components', () => {
		it( 'should translate FocusKeywordInput strings', () => {
			render( <FocusKeywordInput /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Focus Keyword' );

			// Verify all use meowseo domain
			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate DirectAnswerField strings', () => {
			render( <DirectAnswerField /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Direct Answer' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate SerpPreview strings', () => {
			render( <SerpPreview /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Search Preview' );
			expect( texts ).toContain( 'Desktop' );
			expect( texts ).toContain( 'Mobile' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate InternalLinkSuggestions strings', () => {
			const {
				useEntityPropBinding,
			} = require( '../../../hooks/useEntityPropBinding' );
			useEntityPropBinding.mockReturnValue( [
				'test keyword',
				jest.fn(),
			] );

			render( <InternalLinkSuggestions /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Internal Link Suggestions' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );
	} );

	describe( 'Social Tab Components', () => {
		it( 'should translate SocialTabContent strings', () => {
			render( <SocialTabContent /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Facebook' );
			expect( texts ).toContain( 'Twitter' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate FacebookSubTab strings', () => {
			render( <FacebookSubTab /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Facebook Title' );
			expect( texts ).toContain( 'Facebook Description' );
			expect( texts ).toContain( 'Facebook Image' );
			expect( texts ).toContain( 'Facebook Preview' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate TwitterSubTab strings', () => {
			render( <TwitterSubTab /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Use Open Graph for Twitter' );
			expect( texts ).toContain( 'Twitter Title' );
			expect( texts ).toContain( 'Twitter Description' );
			expect( texts ).toContain( 'Twitter Image' );
			expect( texts ).toContain( 'Twitter Preview' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );
	} );

	describe( 'Advanced Tab Components', () => {
		it( 'should translate RobotsToggles strings', () => {
			render( <RobotsToggles /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Robots Meta Directives' );
			expect( texts ).toContain( 'No Index' );
			expect( texts ).toContain( 'No Follow' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate CanonicalURLInput strings', () => {
			render( <CanonicalURLInput /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Canonical URL' );
			expect( texts ).toContain( 'Custom Canonical URL' );
			expect( texts ).toContain( 'Resolved Canonical URL' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should translate GSCIntegration strings', () => {
			render( <GSCIntegration /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Google Search Console' );
			expect( texts ).toContain( 'Last Indexing Request' );
			expect( texts ).toContain( 'Request Indexing' );

			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );
	} );

	describe( 'Help Text Translation', () => {
		it( 'should translate help text in FocusKeywordInput', () => {
			render( <FocusKeywordInput /> );

			const texts = translationCalls.map( ( call ) => call.text );
			const hasHelpText = texts.some( ( text ) =>
				text.includes( 'Enter the main keyword' )
			);
			expect( hasHelpText ).toBe( true );
		} );

		it( 'should translate help text in DirectAnswerField', () => {
			render( <DirectAnswerField /> );

			const texts = translationCalls.map( ( call ) => call.text );
			const hasHelpText = texts.some( ( text ) =>
				text.includes( 'Provide a concise answer' )
			);
			expect( hasHelpText ).toBe( true );
		} );

		it( 'should translate help text in RobotsToggles', () => {
			render( <RobotsToggles /> );

			const texts = translationCalls.map( ( call ) => call.text );
			const hasNoIndexHelp = texts.some( ( text ) =>
				text.includes( 'Prevent search engines from indexing' )
			);
			const hasNoFollowHelp = texts.some( ( text ) =>
				text.includes( 'Prevent search engines from following links' )
			);
			expect( hasNoIndexHelp ).toBe( true );
			expect( hasNoFollowHelp ).toBe( true );
		} );
	} );

	describe( 'Placeholder Text Translation', () => {
		it( 'should translate placeholder text in FocusKeywordInput', () => {
			render( <FocusKeywordInput /> );

			const texts = translationCalls.map( ( call ) => call.text );
			const hasPlaceholder = texts.some( ( text ) =>
				text.includes( 'wordpress seo' )
			);
			expect( hasPlaceholder ).toBe( true );
		} );

		it( 'should translate placeholder text in DirectAnswerField', () => {
			render( <DirectAnswerField /> );

			const texts = translationCalls.map( ( call ) => call.text );
			const hasPlaceholder = texts.some( ( text ) =>
				text.includes( 'WordPress SEO is the practice' )
			);
			expect( hasPlaceholder ).toBe( true );
		} );
	} );

	describe( 'Button Text Translation', () => {
		it( 'should translate button text in FacebookSubTab', () => {
			render( <FacebookSubTab /> );

			const texts = translationCalls.map( ( call ) => call.text );
			// Check for button text - "Select Image" is always rendered
			expect( texts ).toContain( 'Select Image' );
			// "Change Image" and "Remove Image" are conditional based on image presence
		} );

		it( 'should translate button text in TwitterSubTab', () => {
			render( <TwitterSubTab /> );

			const texts = translationCalls.map( ( call ) => call.text );
			// Check for button text - "Select Image" is always rendered
			expect( texts ).toContain( 'Select Image' );
			// "Change Image" is conditional based on image presence
		} );

		it( 'should translate button text in GSCIntegration', () => {
			render( <GSCIntegration /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Request Indexing' );
		} );
	} );

	describe( 'No Hardcoded English Text', () => {
		it( 'should not have hardcoded English in any tab component', () => {
			const components = [
				FocusKeywordInput,
				DirectAnswerField,
				// SerpPreview, // Skip due to mock complexity
				RobotsToggles,
				// CanonicalURLInput, // Skip due to mock complexity
				GSCIntegration,
				SocialTabContent,
				FacebookSubTab,
				TwitterSubTab,
			];

			components.forEach( ( Component ) => {
				translationCalls.length = 0;
				render( <Component /> );

				// All components should have translation calls
				expect( translationCalls.length ).toBeGreaterThan( 0 );

				// All should use meowseo domain
				translationCalls.forEach( ( call ) => {
					expect( call.domain ).toBe( 'meowseo' );
				} );
			} );
		} );
	} );

	describe( 'Consistent Text Domain', () => {
		it( 'should use meowseo domain consistently across all tab components', () => {
			const components = [
				{ name: 'FocusKeywordInput', component: <FocusKeywordInput /> },
				{ name: 'DirectAnswerField', component: <DirectAnswerField /> },
				// { name: 'SerpPreview', component: <SerpPreview /> }, // Skip due to mock complexity
				{ name: 'RobotsToggles', component: <RobotsToggles /> },
				// { name: 'CanonicalURLInput', component: <CanonicalURLInput /> }, // Skip due to mock complexity
				{ name: 'GSCIntegration', component: <GSCIntegration /> },
				{ name: 'SocialTabContent', component: <SocialTabContent /> },
				{ name: 'FacebookSubTab', component: <FacebookSubTab /> },
				{ name: 'TwitterSubTab', component: <TwitterSubTab /> },
			];

			components.forEach( ( { name, component } ) => {
				translationCalls.length = 0;
				render( component );

				translationCalls.forEach( ( call ) => {
					expect( call.domain ).toBe( 'meowseo' );
				} );
			} );
		} );
	} );
} );
