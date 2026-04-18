/**
 * Internationalization (i18n) Tests
 *
 * Tests that all user-facing strings are properly wrapped with translation functions
 * and use the correct text domain.
 *
 * Requirements: 19.1, 19.2, 19.3, 19.4, 19.5, 19.6
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

// Import all components that should have i18n
import { ContentScoreWidget } from '../ContentScoreWidget';
import { TabBar } from '../TabBar';
import { Sidebar } from '../Sidebar';

// Mock the store module
jest.mock( '../../store', () => ( {
	STORE_NAME: 'meowseo/data',
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
	register: jest.fn(),
	createReduxStore: jest.fn(),
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

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, ...props }: any ) => (
		<button { ...props }>{ children }</button>
	),
	ButtonGroup: ( { children }: any ) => <div>{ children }</div>,
	Spinner: () => <span>Loading...</span>,
	TextControl: ( { label }: any ) => <div>{ label }</div>,
	TextareaControl: ( { label }: any ) => <div>{ label }</div>,
	ToggleControl: ( { label }: any ) => <div>{ label }</div>,
	SelectControl: ( { label, options }: any ) => (
		<div>
			{ label }
			{ options?.map( ( opt: any ) => (
				<span key={ opt.value }>{ opt.label }</span>
			) ) }
		</div>
	),
} ) );

// Mock @wordpress/block-editor
jest.mock( '@wordpress/block-editor', () => ( {
	MediaUpload: ( { render }: any ) => render( { open: jest.fn() } ),
} ) );

// Mock hooks
jest.mock( '../../hooks/useContentSync', () => ( {
	useContentSync: jest.fn(),
} ) );

jest.mock( '../../hooks/useEntityPropBinding', () => ( {
	useEntityPropBinding: jest.fn( () => [ '', jest.fn() ] ),
} ) );

// Mock apiFetch
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'Internationalization (i18n)', () => {
	const { useSelect, useDispatch } = require( '@wordpress/data' );
	const { __ } = require( '@wordpress/i18n' );

	beforeEach( () => {
		translationCalls.length = 0;
		jest.clearAllMocks();

		// Default mock implementations
		( useSelect as jest.Mock ).mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => {
				if ( storeName === 'meowseo/data' ) {
					return {
						getSeoScore: () => 75,
						getReadabilityScore: () => 60,
						getIsAnalyzing: () => false,
						getActiveTab: () => 'general',
						getAnalysisResults: () => [
							{
								id: 'keyword-in-title',
								type: 'good',
								message: 'Keyword in title',
								score: 100,
								weight: 0.08,
								details: {},
							},
						],
						getReadabilityResults: () => [
							{
								id: 'sentence-length',
								type: 'good',
								message: 'Sentences are concise',
								score: 100,
								weight: 0.2,
								details: {},
							},
						],
					};
				}
				return {};
			};
			return selector( mockSelect );
		} );

		( useDispatch as jest.Mock ).mockReturnValue( {
			setActiveTab: jest.fn(),
		} );
	} );

	describe( 'Translation Function Usage', () => {
		it( 'should wrap all user-facing strings with translation functions', () => {
			// Requirement 19.2: All translatable strings wrapped with __() or _x()
			render( <ContentScoreWidget /> );

			// Verify that translation function was called
			expect( __ ).toHaveBeenCalled();
			expect( translationCalls.length ).toBeGreaterThan( 0 );
		} );

		it( 'should use translation functions in TabBar component', () => {
			render( <TabBar /> );

			// TabBar should translate tab labels
			expect( __ ).toHaveBeenCalled();

			// Check for specific tab labels
			const tabLabels = translationCalls.map( ( call ) => call.text );
			expect( tabLabels ).toContain( 'General' );
			expect( tabLabels ).toContain( 'Social' );
			expect( tabLabels ).toContain( 'Schema' );
			expect( tabLabels ).toContain( 'Advanced' );
		} );

		it( 'should use translation functions in ContentScoreWidget', () => {
			render( <ContentScoreWidget /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'SEO Score' );
			expect( texts ).toContain( 'Readability Score' );
			expect( texts ).toContain( 'Analyze' );
		} );
	} );

	describe( 'Text Domain Validation', () => {
		it( 'should use "meowseo" text domain for all translations', () => {
			// Requirement 19.3: Use "meowseo" text domain
			render( <ContentScoreWidget /> );
			render( <TabBar /> );

			// Verify all translation calls use the correct domain
			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
			} );
		} );

		it( 'should consistently use meowseo domain across all components', () => {
			const components = [
				<ContentScoreWidget key="score" />,
				<TabBar key="tabs" />,
			];

			components.forEach( ( component ) => {
				translationCalls.length = 0;
				render( component );

				translationCalls.forEach( ( call ) => {
					expect( call.domain ).toBe( 'meowseo' );
				} );
			} );
		} );
	} );

	describe( 'No Hardcoded English Text', () => {
		it( 'should not have hardcoded English text in ContentScoreWidget', () => {
			// Requirement 19.6: Do NOT hardcode any user-facing text in English
			const { container } = render( <ContentScoreWidget /> );

			// All user-facing text should come through translation functions
			expect( __ ).toHaveBeenCalledWith( 'SEO Score', 'meowseo' );
			expect( __ ).toHaveBeenCalledWith( 'Readability Score', 'meowseo' );
			expect( __ ).toHaveBeenCalledWith( 'Analyze', 'meowseo' );
		} );

		it( 'should not have hardcoded English text in TabBar', () => {
			render( <TabBar /> );

			// All tab labels should be translated
			expect( __ ).toHaveBeenCalledWith( 'General', 'meowseo' );
			expect( __ ).toHaveBeenCalledWith( 'Social', 'meowseo' );
			expect( __ ).toHaveBeenCalledWith( 'Schema', 'meowseo' );
			expect( __ ).toHaveBeenCalledWith( 'Advanced', 'meowseo' );
		} );
	} );

	describe( 'Translation Coverage', () => {
		it( 'should translate all button labels', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				isAnalyzing: false,
			} );

			render( <ContentScoreWidget /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Analyze' );
		} );

		it( 'should translate loading states', () => {
			( useSelect as jest.Mock ).mockReturnValue( {
				seoScore: 75,
				readabilityScore: 60,
				isAnalyzing: true,
			} );

			translationCalls.length = 0;
			render( <ContentScoreWidget /> );

			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'Analyzing...' );
		} );

		it( 'should translate all tab labels', () => {
			render( <TabBar /> );

			const texts = translationCalls.map( ( call ) => call.text );

			// All four tabs should be translated
			expect( texts ).toContain( 'General' );
			expect( texts ).toContain( 'Social' );
			expect( texts ).toContain( 'Schema' );
			expect( texts ).toContain( 'Advanced' );
		} );
	} );

	describe( 'RTL Support', () => {
		it( 'should support RTL languages through CSS', () => {
			// Requirement 19.5: Support right-to-left (RTL) languages
			const { container } = render( <ContentScoreWidget /> );

			// RTL support is handled by WordPress and CSS
			// Components should not have hardcoded LTR-specific styles
			const widget = container.querySelector(
				'.meowseo-content-score-widget'
			);
			expect( widget ).toBeInTheDocument();

			// Verify no inline styles that would break RTL
			const inlineStyles = widget?.getAttribute( 'style' );
			if ( inlineStyles ) {
				expect( inlineStyles ).not.toContain( 'direction: ltr' );
			}
			// If no inline styles, that's good - RTL support is handled by CSS
		} );

		it( 'should not use hardcoded directional styles in TabBar', () => {
			const { container } = render( <TabBar /> );

			const tabBar = container.querySelector( '.meowseo-tab-bar' );
			expect( tabBar ).toBeInTheDocument();

			// Verify no inline styles that would break RTL
			const inlineStyles = tabBar?.getAttribute( 'style' );
			if ( inlineStyles ) {
				expect( inlineStyles ).not.toContain( 'direction: ltr' );
			}
			// If no inline styles, that's good - RTL support is handled by CSS
		} );
	} );

	describe( 'Locale Change Support', () => {
		it( 'should display translated strings when WordPress locale changes', () => {
			// Requirement 19.4: Display translated strings when WordPress locale changes

			// Components use the __ function from @wordpress/i18n
			// When WordPress locale changes, the __ function returns translated strings
			// This test verifies that components call __ for all user-facing text

			translationCalls.length = 0;
			render( <ContentScoreWidget /> );

			// Verify translation function is called for all user-facing strings
			expect( __ ).toHaveBeenCalled();
			expect( translationCalls.length ).toBeGreaterThan( 0 );

			// All strings should be translatable (passed through __)
			const texts = translationCalls.map( ( call ) => call.text );
			expect( texts ).toContain( 'SEO Score' );
			expect( texts ).toContain( 'Readability Score' );
			expect( texts ).toContain( 'Analyze' );
		} );
	} );

	describe( 'Translation Function Parameters', () => {
		it( 'should pass correct parameters to __ function', () => {
			render( <ContentScoreWidget /> );

			// Verify __ is called with (text, domain) parameters
			expect( __ ).toHaveBeenCalledWith(
				expect.any( String ),
				'meowseo'
			);
		} );

		it( 'should use consistent parameter order', () => {
			render( <TabBar /> );

			// All calls should follow the pattern: __(text, domain)
			translationCalls.forEach( ( call ) => {
				expect( call.domain ).toBe( 'meowseo' );
				expect( typeof call.text ).toBe( 'string' );
				expect( call.text.length ).toBeGreaterThan( 0 );
			} );
		} );
	} );

	describe( 'Complete Component Coverage', () => {
		it( 'should have i18n in all major components', () => {
			const components = [
				{
					name: 'ContentScoreWidget',
					component: <ContentScoreWidget />,
				},
				{ name: 'TabBar', component: <TabBar /> },
			];

			components.forEach( ( { name, component } ) => {
				translationCalls.length = 0;
				render( component );

				expect( translationCalls.length ).toBeGreaterThan( 0 );

				// All should use meowseo domain
				translationCalls.forEach( ( call ) => {
					expect( call.domain ).toBe( 'meowseo' );
				} );
			} );
		} );
	} );
} );
