/**
 * SocialTabContent Component Tests
 *
 * Unit tests for the Social tab components including FacebookSubTab,
 * TwitterSubTab, and SocialTabContent.
 *
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 12.9
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import SocialTabContent from '../SocialTabContent';
import FacebookSubTab from '../FacebookSubTab';
import TwitterSubTab from '../TwitterSubTab';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
	_x: ( text: string ) => text,
	_n: ( single: string, plural: string, number: number ) =>
		number === 1 ? single : plural,
	isRTL: () => false,
} ) );

// Mock @wordpress/components
jest.mock( '@wordpress/components', () => ( {
	TextControl: ( {
		label,
		value,
		onChange,
		help,
		placeholder,
		disabled,
	}: any ) => (
		<div>
			<label htmlFor={ label.toLowerCase().replace( /\s+/g, '-' ) }>
				{ label }
			</label>
			<input
				id={ label.toLowerCase().replace( /\s+/g, '-' ) }
				type="text"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
				disabled={ disabled }
			/>
			{ help && <p>{ help }</p> }
		</div>
	),
	TextareaControl: ( {
		label,
		value,
		onChange,
		help,
		placeholder,
		disabled,
	}: any ) => (
		<div>
			<label htmlFor={ label.toLowerCase().replace( /\s+/g, '-' ) }>
				{ label }
			</label>
			<textarea
				id={ label.toLowerCase().replace( /\s+/g, '-' ) }
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
				disabled={ disabled }
			/>
			{ help && <p>{ help }</p> }
		</div>
	),
	Button: ( { children, onClick, variant, disabled }: any ) => (
		<button
			onClick={ onClick }
			data-variant={ variant }
			disabled={ disabled }
		>
			{ children }
		</button>
	),
	ButtonGroup: ( { children }: any ) => <div>{ children }</div>,
	ToggleControl: ( { label, help, checked, onChange }: any ) => (
		<div>
			<label>
				<input
					type="checkbox"
					checked={ checked }
					onChange={ ( e ) => onChange( e.target.checked ) }
				/>
				{ label }
			</label>
			{ help && <p>{ help }</p> }
		</div>
	),
} ) );

// Mock @wordpress/block-editor
jest.mock( '@wordpress/block-editor', () => ( {
	MediaUpload: ( { onSelect, render }: any ) => {
		const mockMedia = {
			id: 123,
			source_url: 'https://example.com/image.jpg',
		};
		return render( {
			open: () => onSelect( mockMedia ),
		} );
	},
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn( ( callback ) => {
		const mockSelect = {
			'core/editor': {
				getEditedPostAttribute: ( attr: string ) => {
					if ( attr === 'title' ) {
						return 'Test Post Title';
					}
					if ( attr === 'excerpt' ) {
						return 'Test post excerpt';
					}
					return '';
				},
			},
			core: {
				getMedia: ( id: number ) => ( {
					id,
					source_url: `https://example.com/image-${ id }.jpg`,
				} ),
			},
		};
		return callback(
			( store: string ) => mockSelect[ store as keyof typeof mockSelect ]
		);
	} ),
} ) );

// Mock @wordpress/core-data
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn( () => [ {}, jest.fn() ] ),
} ) );

// Mock the useEntityPropBinding hook
jest.mock( '../../../hooks/useEntityPropBinding' );

const mockUseEntityPropBinding = useEntityPropBinding as jest.MockedFunction<
	typeof useEntityPropBinding
>;

describe( 'SocialTabContent', () => {
	beforeEach( () => {
		// Reset all mocks before each test
		jest.clearAllMocks();

		// Default mock implementation
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			const mockSetValue = jest.fn();
			return [ '', mockSetValue ];
		} );
	} );

	/**
	 * Test: Social tab displays Facebook and Twitter sub-tabs
	 * Requirement: 12.1 - Display Facebook and Twitter sub-tabs
	 */
	it( 'should display Facebook and Twitter sub-tab buttons', () => {
		render( <SocialTabContent /> );

		const facebookButton = screen.getByText( 'Facebook' );
		const twitterButton = screen.getByText( 'Twitter' );

		expect( facebookButton ).toBeInTheDocument();
		expect( twitterButton ).toBeInTheDocument();
	} );

	/**
	 * Test: Facebook sub-tab is active by default
	 * Requirement: 12.9 - Implement sub-tab navigation
	 */
	it( 'should show Facebook sub-tab by default', () => {
		render( <SocialTabContent /> );

		const facebookTitle = screen.getByLabelText( /facebook title/i );
		expect( facebookTitle ).toBeInTheDocument();
	} );

	/**
	 * Test: Clicking Twitter button switches to Twitter sub-tab
	 * Requirement: 12.9 - Implement sub-tab navigation
	 */
	it( 'should switch to Twitter sub-tab when Twitter button is clicked', () => {
		render( <SocialTabContent /> );

		const twitterButton = screen.getByText( 'Twitter' );
		fireEvent.click( twitterButton );

		const twitterTitle = screen.getByLabelText( /twitter title/i );
		expect( twitterTitle ).toBeInTheDocument();
	} );
} );

describe( 'FacebookSubTab', () => {
	let mockSetOgTitle: jest.Mock;
	let mockSetOgDescription: jest.Mock;
	let mockSetOgImageId: jest.Mock;

	beforeEach( () => {
		mockSetOgTitle = jest.fn();
		mockSetOgDescription = jest.fn();
		mockSetOgImageId = jest.fn();

		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_og_title' ) {
				return [ '', mockSetOgTitle ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ '', mockSetOgDescription ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '', mockSetOgImageId ];
			}
			return [ '', jest.fn() ];
		} );
	} );

	/**
	 * Test: Facebook inputs are displayed
	 * Requirement: 12.2 - Provide inputs for Open Graph title, description, and image
	 */
	it( 'should display Facebook title, description, and image inputs', () => {
		render( <FacebookSubTab /> );

		const titleInput = screen.getByLabelText( /facebook title/i );
		const descriptionInput =
			screen.getByLabelText( /facebook description/i );
		const imageLabel = screen.getByText( /facebook image/i );

		expect( titleInput ).toBeInTheDocument();
		expect( descriptionInput ).toBeInTheDocument();
		expect( imageLabel ).toBeInTheDocument();
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta keys
	 * Requirement: 12.4 - Persist to _meowseo_og_title, _meowseo_og_description, _meowseo_og_image_id
	 */
	it( 'should use useEntityPropBinding with correct Open Graph meta keys', () => {
		render( <FacebookSubTab /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_og_title'
		);
		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_og_description'
		);
		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_og_image_id'
		);
	} );

	/**
	 * Test: Changing title input calls setValue
	 * Requirement: 12.4 - Persist Open Graph title
	 */
	it( 'should call setValue when title changes', () => {
		render( <FacebookSubTab /> );

		const titleInput = screen.getByLabelText( /facebook title/i );
		fireEvent.change( titleInput, {
			target: { value: 'My Facebook Title' },
		} );

		expect( mockSetOgTitle ).toHaveBeenCalledWith( 'My Facebook Title' );
	} );

	/**
	 * Test: Changing description input calls setValue
	 * Requirement: 12.4 - Persist Open Graph description
	 */
	it( 'should call setValue when description changes', () => {
		render( <FacebookSubTab /> );

		const descriptionInput =
			screen.getByLabelText( /facebook description/i );
		fireEvent.change( descriptionInput, {
			target: { value: 'My Facebook Description' },
		} );

		expect( mockSetOgDescription ).toHaveBeenCalledWith(
			'My Facebook Description'
		);
	} );

	/**
	 * Test: Facebook preview card is displayed
	 * Requirement: 12.8 - Display Facebook preview card
	 */
	it( 'should display Facebook preview card', () => {
		render( <FacebookSubTab /> );

		const previewHeading = screen.getByText( /facebook preview/i );
		expect( previewHeading ).toBeInTheDocument();
	} );

	/**
	 * Test: Preview card shows current values
	 * Requirement: 12.8 - Display preview card with current values
	 */
	it( 'should show current values in preview card', () => {
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_og_title' ) {
				return [ 'Custom OG Title', mockSetOgTitle ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ 'Custom OG Description', mockSetOgDescription ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '123', mockSetOgImageId ];
			}
			return [ '', jest.fn() ];
		} );

		render( <FacebookSubTab /> );

		// Use more specific query to find preview elements
		const preview = screen
			.getByText( /facebook preview/i )
			.closest( '.meowseo-preview-card' );
		expect( preview ).toHaveTextContent( 'Custom OG Title' );
		expect( preview ).toHaveTextContent( 'Custom OG Description' );
	} );
} );

describe( 'TwitterSubTab', () => {
	let mockSetTwitterTitle: jest.Mock;
	let mockSetTwitterDescription: jest.Mock;
	let mockSetTwitterImageId: jest.Mock;
	let mockSetUseOgForTwitter: jest.Mock;

	beforeEach( () => {
		mockSetTwitterTitle = jest.fn();
		mockSetTwitterDescription = jest.fn();
		mockSetTwitterImageId = jest.fn();
		mockSetUseOgForTwitter = jest.fn();

		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_twitter_title' ) {
				return [ '', mockSetTwitterTitle ];
			}
			if ( metaKey === '_meowseo_twitter_description' ) {
				return [ '', mockSetTwitterDescription ];
			}
			if ( metaKey === '_meowseo_twitter_image_id' ) {
				return [ '', mockSetTwitterImageId ];
			}
			if ( metaKey === '_meowseo_use_og_for_twitter' ) {
				return [ '', mockSetUseOgForTwitter ];
			}
			if ( metaKey === '_meowseo_og_title' ) {
				return [ '', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ '', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );
	} );

	/**
	 * Test: Twitter inputs are displayed
	 * Requirement: 12.3 - Provide inputs for Twitter title, description, and image
	 */
	it( 'should display Twitter title, description, and image inputs', () => {
		render( <TwitterSubTab /> );

		const titleInput = screen.getByLabelText( /twitter title/i );
		const descriptionInput =
			screen.getByLabelText( /twitter description/i );
		const imageLabel = screen.getByText( /twitter image/i );

		expect( titleInput ).toBeInTheDocument();
		expect( descriptionInput ).toBeInTheDocument();
		expect( imageLabel ).toBeInTheDocument();
	} );

	/**
	 * Test: "Use Open Graph for Twitter" toggle is displayed
	 * Requirement: 12.6 - Display "Use Open Graph for Twitter" toggle
	 */
	it( 'should display "Use Open Graph for Twitter" toggle', () => {
		render( <TwitterSubTab /> );

		const toggle = screen.getByLabelText( /use open graph for twitter/i );
		expect( toggle ).toBeInTheDocument();
	} );

	/**
	 * Test: useEntityPropBinding is called with correct meta keys
	 * Requirement: 12.5 - Persist to Twitter meta keys
	 */
	it( 'should use useEntityPropBinding with correct Twitter meta keys', () => {
		render( <TwitterSubTab /> );

		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_twitter_title'
		);
		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_twitter_description'
		);
		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_twitter_image_id'
		);
		expect( mockUseEntityPropBinding ).toHaveBeenCalledWith(
			'_meowseo_use_og_for_twitter'
		);
	} );

	/**
	 * Test: Changing title input calls setValue
	 * Requirement: 12.5 - Persist Twitter title
	 */
	it( 'should call setValue when title changes', () => {
		render( <TwitterSubTab /> );

		const titleInput = screen.getByLabelText( /twitter title/i );
		fireEvent.change( titleInput, {
			target: { value: 'My Twitter Title' },
		} );

		expect( mockSetTwitterTitle ).toHaveBeenCalledWith(
			'My Twitter Title'
		);
	} );

	/**
	 * Test: Changing description input calls setValue
	 * Requirement: 12.5 - Persist Twitter description
	 */
	it( 'should call setValue when description changes', () => {
		render( <TwitterSubTab /> );

		const descriptionInput =
			screen.getByLabelText( /twitter description/i );
		fireEvent.change( descriptionInput, {
			target: { value: 'My Twitter Description' },
		} );

		expect( mockSetTwitterDescription ).toHaveBeenCalledWith(
			'My Twitter Description'
		);
	} );

	/**
	 * Test: Toggling "Use Open Graph for Twitter" calls setValue
	 * Requirement: 12.6 - Toggle behavior
	 */
	it( 'should call setValue when toggle is changed', () => {
		render( <TwitterSubTab /> );

		const toggle = screen.getByLabelText( /use open graph for twitter/i );
		fireEvent.click( toggle );

		expect( mockSetUseOgForTwitter ).toHaveBeenCalledWith( '1' );
	} );

	/**
	 * Test: Twitter inputs are disabled when toggle is enabled
	 * Requirement: 12.7 - Disable Twitter-specific inputs when toggle is enabled
	 */
	it( 'should disable Twitter inputs when toggle is enabled', () => {
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_twitter_title' ) {
				return [ '', mockSetTwitterTitle ];
			}
			if ( metaKey === '_meowseo_twitter_description' ) {
				return [ '', mockSetTwitterDescription ];
			}
			if ( metaKey === '_meowseo_twitter_image_id' ) {
				return [ '', mockSetTwitterImageId ];
			}
			if ( metaKey === '_meowseo_use_og_for_twitter' ) {
				return [ '1', mockSetUseOgForTwitter ];
			}
			if ( metaKey === '_meowseo_og_title' ) {
				return [ 'OG Title', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ 'OG Description', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <TwitterSubTab /> );

		const titleInput = screen.getByLabelText(
			/twitter title/i
		) as HTMLInputElement;
		const descriptionInput = screen.getByLabelText(
			/twitter description/i
		) as HTMLTextAreaElement;

		expect( titleInput.disabled ).toBe( true );
		expect( descriptionInput.disabled ).toBe( true );
	} );

	/**
	 * Test: Twitter inputs are enabled when toggle is disabled
	 * Requirement: 12.7 - Enable Twitter-specific inputs when toggle is disabled
	 */
	it( 'should enable Twitter inputs when toggle is disabled', () => {
		render( <TwitterSubTab /> );

		const titleInput = screen.getByLabelText(
			/twitter title/i
		) as HTMLInputElement;
		const descriptionInput = screen.getByLabelText(
			/twitter description/i
		) as HTMLTextAreaElement;

		expect( titleInput.disabled ).toBe( false );
		expect( descriptionInput.disabled ).toBe( false );
	} );

	/**
	 * Test: Twitter preview card is displayed
	 * Requirement: 12.8 - Display Twitter preview card
	 */
	it( 'should display Twitter preview card', () => {
		render( <TwitterSubTab /> );

		const previewHeading = screen.getByText( /twitter preview/i );
		expect( previewHeading ).toBeInTheDocument();
	} );

	/**
	 * Test: Preview card shows current values
	 * Requirement: 12.8 - Display preview card with current values
	 */
	it( 'should show current values in preview card', () => {
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_twitter_title' ) {
				return [ 'Custom Twitter Title', mockSetTwitterTitle ];
			}
			if ( metaKey === '_meowseo_twitter_description' ) {
				return [
					'Custom Twitter Description',
					mockSetTwitterDescription,
				];
			}
			if ( metaKey === '_meowseo_twitter_image_id' ) {
				return [ '123', mockSetTwitterImageId ];
			}
			if ( metaKey === '_meowseo_use_og_for_twitter' ) {
				return [ '', mockSetUseOgForTwitter ];
			}
			if ( metaKey === '_meowseo_og_title' ) {
				return [ '', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ '', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <TwitterSubTab /> );

		// Use more specific query to find preview elements
		const preview = screen
			.getByText( /twitter preview/i )
			.closest( '.meowseo-preview-card' );
		expect( preview ).toHaveTextContent( 'Custom Twitter Title' );
		expect( preview ).toHaveTextContent( 'Custom Twitter Description' );
	} );

	/**
	 * Test: Preview uses Open Graph values when toggle is enabled
	 * Requirement: 12.7 - Use Open Graph values when toggle is enabled
	 */
	it( 'should use Open Graph values in preview when toggle is enabled', () => {
		mockUseEntityPropBinding.mockImplementation( ( metaKey: string ) => {
			if ( metaKey === '_meowseo_twitter_title' ) {
				return [ '', mockSetTwitterTitle ];
			}
			if ( metaKey === '_meowseo_twitter_description' ) {
				return [ '', mockSetTwitterDescription ];
			}
			if ( metaKey === '_meowseo_twitter_image_id' ) {
				return [ '', mockSetTwitterImageId ];
			}
			if ( metaKey === '_meowseo_use_og_for_twitter' ) {
				return [ '1', mockSetUseOgForTwitter ];
			}
			if ( metaKey === '_meowseo_og_title' ) {
				return [ 'OG Title', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_description' ) {
				return [ 'OG Description', jest.fn() ];
			}
			if ( metaKey === '_meowseo_og_image_id' ) {
				return [ '', jest.fn() ];
			}
			return [ '', jest.fn() ];
		} );

		render( <TwitterSubTab /> );

		const previewTitle = screen.getByText( 'OG Title' );
		const previewDescription = screen.getByText( 'OG Description' );

		expect( previewTitle ).toBeInTheDocument();
		expect( previewDescription ).toBeInTheDocument();
	} );
} );
