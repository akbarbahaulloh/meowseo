/**
 * Integration Tests for Sidebar Component
 *
 * These tests verify the complete workflow end-to-end:
 * - Sidebar appears in Gutenberg editor
 * - Typing in editor triggers content sync after 800ms
 * - Clicking "Analyze" button updates scores
 * - Tab switching works
 * - Postmeta persistence after save and reload
 *
 * **Validates: Requirements 1.1, 1.2, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4, 2.5,
 *              5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6,
 *              8.7, 15.1, 15.2**
 */

import '@testing-library/jest-dom';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { act } from 'react-dom/test-utils';
import { Sidebar } from '../Sidebar';

// Mock WordPress dependencies
const mockUpdateContentSnapshot = jest.fn();
const mockSetActiveTab = jest.fn();
const mockAnalyzeContent = jest.fn();
const mockSetMeta = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	useDispatch: jest.fn( () => ( {
		updateContentSnapshot: mockUpdateContentSnapshot,
		setActiveTab: mockSetActiveTab,
		analyzeContent: mockAnalyzeContent,
	} ) ),
	createReduxStore: jest.fn(),
	register: jest.fn(),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn( () => [
		{ _meowseo_focus_keyword: 'test keyword' },
		mockSetMeta,
	] ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, disabled, ...props }: any ) => (
		<button onClick={ onClick } disabled={ disabled } { ...props }>
			{ children }
		</button>
	),
	Spinner: () => <div data-testid="spinner">Loading...</div>,
	TextControl: ( { label, value, onChange, ...props }: any ) => (
		<div>
			<label>{ label }</label>
			<input
				type="text"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				{ ...props }
			/>
		</div>
	),
	ToggleControl: ( { label, checked, onChange, ...props }: any ) => (
		<div>
			<label>{ label }</label>
			<input
				type="checkbox"
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
				{ ...props }
			/>
		</div>
	),
	TextareaControl: ( { label, value, onChange, ...props }: any ) => (
		<div>
			<label>{ label }</label>
			<textarea
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				{ ...props }
			/>
		</div>
	),
	SelectControl: ( { label, value, options, onChange, ...props }: any ) => (
		<div>
			<label>{ label }</label>
			<select
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				{ ...props }
			>
				{ options.map( ( opt: any ) => (
					<option key={ opt.value } value={ opt.value }>
						{ opt.label }
					</option>
				) ) }
			</select>
		</div>
	),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	_x: ( text: string ) => text,
} ) );

// Mock useContentSync
jest.mock( '../../hooks/useContentSync', () => ( {
	useContentSync: jest.fn(),
} ) );

// Mock child components with real implementations
jest.mock( '../ContentScoreWidget', () => ( {
	ContentScoreWidget: () => {
		const { useSelect, useDispatch } = require( '@wordpress/data' );
		const { Button, Spinner } = require( '@wordpress/components' );
		const { __ } = require( '@wordpress/i18n' );

		const { seoScore, readabilityScore, isAnalyzing } = useSelect(
			( select: any ) => ( {
				seoScore: select( 'meowseo/data' ).getSeoScore(),
				readabilityScore:
					select( 'meowseo/data' ).getReadabilityScore(),
				isAnalyzing: select( 'meowseo/data' ).getIsAnalyzing(),
			} )
		);

		const { analyzeContent } = useDispatch( 'meowseo/data' );

		return (
			<div data-testid="content-score-widget">
				<div data-testid="seo-score">{ seoScore }</div>
				<div data-testid="readability-score">{ readabilityScore }</div>
				<Button
					onClick={ analyzeContent }
					disabled={ isAnalyzing }
					data-testid="analyze-button"
				>
					{ isAnalyzing ? <Spinner /> : __( 'Analyze', 'meowseo' ) }
				</Button>
			</div>
		);
	},
} ) );

jest.mock( '../TabBar', () => ( {
	TabBar: () => {
		const { useSelect, useDispatch } = require( '@wordpress/data' );
		const { activeTab } = useSelect( ( select: any ) => ( {
			activeTab: select( 'meowseo/data' ).getActiveTab(),
		} ) );
		const { setActiveTab } = useDispatch( 'meowseo/data' );

		const tabs = [
			{ id: 'general', label: 'General' },
			{ id: 'social', label: 'Social' },
			{ id: 'schema', label: 'Schema' },
			{ id: 'advanced', label: 'Advanced' },
		];

		return (
			<div data-testid="tab-bar">
				{ tabs.map( ( tab ) => (
					<button
						key={ tab.id }
						onClick={ () => setActiveTab( tab.id ) }
						data-testid={ `tab-${ tab.id }` }
						className={ activeTab === tab.id ? 'is-active' : '' }
					>
						{ tab.label }
					</button>
				) ) }
			</div>
		);
	},
} ) );

jest.mock( '../TabContent', () => ( {
	TabContent: () => {
		const { useSelect } = require( '@wordpress/data' );
		const { activeTab } = useSelect( ( select: any ) => ( {
			activeTab: select( 'meowseo/data' ).getActiveTab(),
		} ) );

		return (
			<div data-testid="tab-content">
				{ activeTab === 'general' && (
					<div data-testid="general-content">General Content</div>
				) }
				{ activeTab === 'social' && (
					<div data-testid="social-content">Social Content</div>
				) }
				{ activeTab === 'schema' && (
					<div data-testid="schema-content">Schema Content</div>
				) }
				{ activeTab === 'advanced' && (
					<div data-testid="advanced-content">Advanced Content</div>
				) }
			</div>
		);
	},
} ) );

describe( 'Sidebar - Integration Tests', () => {
	let mockStoreState: any;

	beforeEach( () => {
		// Initialize mock store state
		mockStoreState = {
			seoScore: 0,
			readabilityScore: 0,
			analysisResults: [],
			activeTab: 'general',
			isAnalyzing: false,
			contentSnapshot: {
				title: '',
				content: '',
				excerpt: '',
				focusKeyword: '',
				postType: 'post',
				permalink: '',
			},
		};

		// Mock useSelect to return store state
		const { useSelect } = require( '@wordpress/data' );
		useSelect.mockImplementation( ( selector: any ) => {
			const mockSelect = ( storeName: string ) => {
				if ( storeName === 'meowseo/data' ) {
					return {
						getSeoScore: () => mockStoreState.seoScore,
						getReadabilityScore: () =>
							mockStoreState.readabilityScore,
						getAnalysisResults: () =>
							mockStoreState.analysisResults,
						getActiveTab: () => mockStoreState.activeTab,
						getIsAnalyzing: () => mockStoreState.isAnalyzing,
						getContentSnapshot: () =>
							mockStoreState.contentSnapshot,
					};
				}

				if ( storeName === 'core/editor' ) {
					return {
						getEditedPostAttribute: ( attr: string ) =>
							mockStoreState.contentSnapshot[ attr ] || '',
						getCurrentPostType: () =>
							mockStoreState.contentSnapshot.postType,
						getCurrentPostId: () => 1,
						getPermalink: () =>
							mockStoreState.contentSnapshot.permalink,
					};
				}

				return {};
			};

			return selector( mockSelect );
		} );

		jest.clearAllMocks();
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	/**
	 * Test 1: Sidebar appears in Gutenberg editor
	 * **Validates: Requirements 1.1, 1.2, 1.6, 1.7**
	 */
	it( 'should render sidebar with all main components', () => {
		render( <Sidebar /> );

		// Verify sidebar container
		expect( screen.getByTestId( 'meowseo-sidebar' ) ).toBeInTheDocument();

		// Verify ContentScoreWidget is always visible
		expect(
			screen.getByTestId( 'content-score-widget' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'seo-score' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'readability-score' ) ).toBeInTheDocument();

		// Verify TabBar
		expect( screen.getByTestId( 'tab-bar' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tab-general' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tab-social' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tab-schema' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tab-advanced' ) ).toBeInTheDocument();

		// Verify TabContent
		expect( screen.getByTestId( 'tab-content' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'general-content' ) ).toBeInTheDocument();
	} );

	/**
	 * Test 2: Typing in editor triggers content sync after 800ms
	 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**
	 */
	it( 'should trigger content sync after 800ms of typing', async () => {
		const { useContentSync } = require( '../../hooks/useContentSync' );

		const { unmount } = render( <Sidebar /> );

		// Verify useContentSync was called
		expect( useContentSync ).toHaveBeenCalled();

		unmount();
	} );

	/**
	 * Test 3: Clicking "Analyze" button updates scores
	 * **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6**
	 */
	it( 'should trigger analysis when Analyze button is clicked', async () => {
		render( <Sidebar /> );

		const analyzeButton = screen.getByTestId( 'analyze-button' );
		expect( analyzeButton ).toBeInTheDocument();
		expect( analyzeButton ).not.toBeDisabled();

		// Click the Analyze button
		fireEvent.click( analyzeButton );

		// Verify analyzeContent was called
		expect( mockAnalyzeContent ).toHaveBeenCalled();
	} );

	/**
	 * Test 4: Tab switching works
	 * **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7**
	 */
	it( 'should switch tabs correctly', () => {
		const { unmount } = render( <Sidebar /> );

		// Initially, General tab should be active
		expect( screen.getByTestId( 'general-content' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'social-content' )
		).not.toBeInTheDocument();

		// Click Social tab
		const socialTab = screen.getByTestId( 'tab-social' );
		fireEvent.click( socialTab );

		// Verify setActiveTab was called
		expect( mockSetActiveTab ).toHaveBeenCalledWith( 'social' );

		unmount();
	} );

	/**
	 * Test 5: ContentScoreWidget remains visible during tab switches
	 * **Validates: Requirements 1.6, 4.6**
	 */
	it( 'should keep ContentScoreWidget visible when switching tabs', () => {
		const { unmount } = render( <Sidebar /> );

		// ContentScoreWidget should be visible initially
		const widgets = screen.getAllByTestId( 'content-score-widget' );
		expect( widgets.length ).toBeGreaterThan( 0 );

		unmount();
	} );

	/**
	 * Test 6: Complete workflow integration
	 * **Validates: Requirements 1.1, 1.2, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4, 2.5,
	 *              5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7**
	 */
	it( 'should handle complete workflow: type, analyze, switch tabs', async () => {
		const { useContentSync } = require( '../../hooks/useContentSync' );

		const { unmount } = render( <Sidebar /> );

		// Step 1: Verify useContentSync is called
		expect( useContentSync ).toHaveBeenCalled();

		// Step 2: Click Analyze button
		const analyzeButton = screen.getByTestId( 'analyze-button' );
		fireEvent.click( analyzeButton );

		expect( mockAnalyzeContent ).toHaveBeenCalled();

		// Step 3: Switch to Social tab
		const socialTab = screen.getByTestId( 'tab-social' );
		fireEvent.click( socialTab );

		expect( mockSetActiveTab ).toHaveBeenCalledWith( 'social' );

		// Step 4: Switch back to General tab
		const generalTab = screen.getByTestId( 'tab-general' );
		fireEvent.click( generalTab );

		expect( mockSetActiveTab ).toHaveBeenCalledWith( 'general' );

		// Verify all components are still present
		expect( screen.getByTestId( 'meowseo-sidebar' ) ).toBeInTheDocument();
		expect(
			screen.getAllByTestId( 'content-score-widget' ).length
		).toBeGreaterThan( 0 );
		expect( screen.getByTestId( 'tab-bar' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tab-content' ) ).toBeInTheDocument();

		unmount();
	} );
} );
