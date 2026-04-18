/**
 * Tests for AiGeneratorPanel Component
 *
 * Tests the main AI Generator sidebar panel component including:
 * - Rendering of generation buttons
 * - API integration for content generation
 * - Preview panel display
 * - Apply functionality
 * - Error handling
 * - Accessibility features
 *
 * Requirements: 7.1-7.9, 8.1-8.7, 9.1-9.5, 10.1-10.4, 11.1-11.5, 34.1-34.6
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { AiGeneratorPanel } from '../components/AiGeneratorPanel';
import '@testing-library/jest-dom';

// Mock WordPress packages
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useState: jest.requireActual( 'react' ).useState,
	useCallback: jest.requireActual( 'react' ).useCallback,
	useRef: jest.requireActual( 'react' ).useRef,
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn( ( selector ) => {
		// Mock returning a post ID
		return 123;
	} ),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick, disabled, ...props } ) => (
		<button onClick={ onClick } disabled={ disabled } { ...props }>
			{ children }
		</button>
	),
	Spinner: () => <div data-testid="spinner">Loading...</div>,
	Notice: ( { children, status, isDismissible, onRemove } ) => (
		<div data-testid={ `notice-${ status }` } role="alert">
			{ children }
			{ isDismissible && <button onClick={ onRemove }>Dismiss</button> }
		</div>
	),
	Panel: ( { children } ) => <div data-testid="panel">{ children }</div>,
	PanelBody: ( { children, title } ) => (
		<div data-testid="panel-body">
			<h2>{ title }</h2>
			{ children }
		</div>
	),
	PanelRow: ( { children } ) => (
		<div data-testid="panel-row">{ children }</div>
	),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

jest.mock( '../components/PreviewPanel', () => ( {
	PreviewPanel: ( { content, onApply, onCancel, isApplying, provider } ) => (
		<div data-testid="preview-panel">
			<div data-testid="preview-content">
				{ JSON.stringify( content ) }
			</div>
			<button
				onClick={ () => onApply( content ) }
				disabled={ isApplying }
			>
				Apply
			</button>
			<button onClick={ onCancel }>Cancel</button>
			{ provider && (
				<span data-testid="preview-provider">{ provider }</span>
			) }
		</div>
	),
} ) );

describe( 'AiGeneratorPanel Component', () => {
	let mockApiFetch;

	beforeEach( () => {
		mockApiFetch = require( '@wordpress/api-fetch' );
		mockApiFetch.mockClear();

		// Setup window object with required data
		window.meowseoAiData = {
			nonce: 'test-nonce',
			settingsUrl: 'http://example.com/settings',
		};

		// Mock wp.data.dispatch
		window.wp = {
			data: {
				dispatch: jest.fn( () => ( {
					editPost: jest.fn(),
				} ) ),
			},
		};
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Requirement 7.1: Sidebar panel rendering', () => {
		it( 'should render the AI Generator panel', () => {
			render( <AiGeneratorPanel /> );
			expect( screen.getByTestId( 'panel' ) ).toBeInTheDocument();
			expect( screen.getByText( 'AI Generator' ) ).toBeInTheDocument();
		} );

		it( 'should display generation buttons initially', () => {
			render( <AiGeneratorPanel /> );
			expect(
				screen.getByText( 'Generate All SEO' )
			).toBeInTheDocument();
			expect( screen.getByText( 'Text Only' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Image Only' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Requirement 7.2: Generation buttons functionality', () => {
		it( 'should call API when "Generate All SEO" button is clicked', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					seo_description: 'Test Description',
					provider: 'Gemini',
					is_fallback: false,
				},
			} );

			render( <AiGeneratorPanel /> );
			const generateButton = screen.getByText( 'Generate All SEO' );

			fireEvent.click( generateButton );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						path: '/meowseo/v1/ai/generate',
						method: 'POST',
						data: expect.objectContaining( {
							post_id: 123,
							type: 'all',
							generate_image: true,
						} ),
					} )
				);
			} );
		} );

		it( 'should call API with type "text" when "Text Only" button is clicked', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			const textButton = screen.getByText( 'Text Only' );

			fireEvent.click( textButton );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						data: expect.objectContaining( {
							type: 'text',
							generate_image: false,
						} ),
					} )
				);
			} );
		} );

		it( 'should call API with type "image" when "Image Only" button is clicked', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					image_url: 'http://example.com/image.jpg',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			const imageButton = screen.getByText( 'Image Only' );

			fireEvent.click( imageButton );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						data: expect.objectContaining( {
							type: 'image',
							generate_image: true,
						} ),
					} )
				);
			} );
		} );

		it( 'should disable buttons during generation', async () => {
			mockApiFetch.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) =>
						setTimeout(
							() =>
								resolve( {
									success: true,
									data: { seo_title: 'Test' },
								} ),
							100
						)
					)
			);

			render( <AiGeneratorPanel /> );
			const generateButton = screen.getByText( 'Generate All SEO' );

			fireEvent.click( generateButton );

			// Buttons should be disabled during generation
			expect( generateButton ).toBeDisabled();
			expect( screen.getByText( 'Text Only' ) ).toBeDisabled();
			expect( screen.getByText( 'Image Only' ) ).toBeDisabled();

			// After generation completes, preview is shown (buttons are hidden)
			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );
		} );

		it( 'should show loading spinner during generation', async () => {
			mockApiFetch.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) =>
						setTimeout(
							() =>
								resolve( {
									success: true,
									data: { seo_title: 'Test' },
								} ),
							100
						)
					)
			);

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();

			await waitFor( () => {
				expect(
					screen.queryByTestId( 'spinner' )
				).not.toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Requirement 7.3 & 7.8: Provider indicator', () => {
		it( 'should display provider badge after successful generation', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'OpenAI',
					is_fallback: false,
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				// Provider is passed to PreviewPanel
				expect(
					screen.getByTestId( 'preview-provider' )
				).toBeInTheDocument();
				expect(
					screen.getByTestId( 'preview-provider' )
				).toHaveTextContent( 'OpenAI' );
			} );
		} );
	} );

	describe( 'Requirement 10.1-10.4: Fallback notification', () => {
		it( 'should display fallback warning when fallback provider is used', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Anthropic',
					is_fallback: true,
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const notice = screen.getByTestId( 'notice-warning' );
				expect( notice ).toBeInTheDocument();
				expect( notice ).toHaveTextContent(
					'primary provider unavailable'
				);
				expect( notice ).toHaveTextContent( 'Anthropic' );
			} );
		} );

		it( 'should include link to settings in fallback notification', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Anthropic',
					is_fallback: true,
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const settingsLink = screen.getByText( 'Configure providers' );
				expect( settingsLink ).toBeInTheDocument();
				expect( settingsLink ).toHaveAttribute(
					'href',
					'http://example.com/settings'
				);
			} );
		} );
	} );

	describe( 'Requirement 11.1-11.5: Error handling', () => {
		it( 'should display error message when generation fails', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: false,
				message: 'API key is invalid',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const errorRegion = document.querySelector(
					'[role="alert"][aria-live="assertive"]'
				);
				expect( errorRegion.textContent ).toContain(
					'API key is invalid'
				);
			} );
		} );

		it( 'should display retry button in error notice', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: false,
				message: 'Generation failed',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect( screen.getByText( 'Retry' ) ).toBeInTheDocument();
			} );
		} );

		it( 'should retry generation when retry button is clicked', async () => {
			mockApiFetch
				.mockResolvedValueOnce( {
					success: false,
					message: 'Generation failed',
				} )
				.mockResolvedValueOnce( {
					success: true,
					data: { seo_title: 'Test Title', provider: 'Gemini' },
				} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect( screen.getByText( 'Retry' ) ).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Retry' ) );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledTimes( 2 );
			} );
		} );

		it( 'should include settings link in error notice', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: false,
				message: 'No providers configured',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const settingsLink = screen.getByText( 'Settings' );
				expect( settingsLink ).toBeInTheDocument();
				expect( settingsLink ).toHaveAttribute(
					'href',
					'http://example.com/settings'
				);
			} );
		} );

		it( 'should handle permission denied error', async () => {
			mockApiFetch.mockRejectedValueOnce( {
				status: 403,
				message: 'Permission denied',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const errorRegion = document.querySelector(
					'[role="alert"][aria-live="assertive"]'
				);
				expect( errorRegion.textContent ).toContain(
					'You do not have permission'
				);
			} );
		} );

		it( 'should handle content too short error', async () => {
			mockApiFetch.mockRejectedValueOnce( {
				message: 'Content must be at least 300 words for generation',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const errorRegion = document.querySelector(
					'[role="alert"][aria-live="assertive"]'
				);
				expect( errorRegion.textContent ).toContain(
					'at least 300 words'
				);
			} );
		} );
	} );

	describe( 'Requirement 8.1-8.7: Preview panel display', () => {
		it( 'should display preview panel after successful generation', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					seo_description: 'Test Description',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );
		} );

		it( 'should hide generation buttons when preview is displayed', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.queryByText( 'Generate All SEO' )
				).not.toBeInTheDocument();
			} );
		} );

		it( 'should pass generated content to preview panel', async () => {
			const generatedData = {
				seo_title: 'Test Title',
				seo_description: 'Test Description',
				focus_keyword: 'test',
				provider: 'Gemini',
			};

			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: generatedData,
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				const previewContent = screen.getByTestId( 'preview-content' );
				expect( previewContent ).toHaveTextContent(
					JSON.stringify( generatedData )
				);
			} );
		} );
	} );

	describe( 'Requirement 27.1-27.10: Apply functionality', () => {
		it( 'should call apply API when Apply button is clicked', async () => {
			mockApiFetch
				.mockResolvedValueOnce( {
					success: true,
					data: {
						seo_title: 'Test Title',
						provider: 'Gemini',
					},
				} )
				.mockResolvedValueOnce( {
					success: true,
				} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Apply' ) );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						path: '/meowseo/v1/ai/apply',
						method: 'POST',
					} )
				);
			} );
		} );

		it( 'should display success message after applying content', async () => {
			mockApiFetch
				.mockResolvedValueOnce( {
					success: true,
					data: {
						seo_title: 'Test Title',
						provider: 'Gemini',
					},
				} )
				.mockResolvedValueOnce( {
					success: true,
				} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Apply' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'notice-success' )
				).toBeInTheDocument();
				expect(
					screen.getByText( 'Content applied successfully!' )
				).toBeInTheDocument();
			} );
		} );

		it( 'should close preview panel after successful apply', async () => {
			mockApiFetch
				.mockResolvedValueOnce( {
					success: true,
					data: {
						seo_title: 'Test Title',
						provider: 'Gemini',
					},
				} )
				.mockResolvedValueOnce( {
					success: true,
				} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Apply' ) );

			await waitFor( () => {
				expect(
					screen.queryByTestId( 'preview-panel' )
				).not.toBeInTheDocument();
			} );
		} );

		it( 'should handle apply errors', async () => {
			mockApiFetch
				.mockResolvedValueOnce( {
					success: true,
					data: {
						seo_title: 'Test Title',
						provider: 'Gemini',
					},
				} )
				.mockResolvedValueOnce( {
					success: false,
					message: 'Failed to save content',
				} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Apply' ) );

			// The error should be displayed in the error live region
			await waitFor( () => {
				const errorRegion = document.querySelector(
					'[role="alert"][aria-live="assertive"]'
				);
				expect( errorRegion.textContent ).toContain(
					'Failed to save content'
				);
			} );
		} );

		it( 'should allow canceling preview without applying', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );

			fireEvent.click( screen.getByText( 'Cancel' ) );

			await waitFor( () => {
				expect(
					screen.queryByTestId( 'preview-panel' )
				).not.toBeInTheDocument();
				expect(
					screen.getByText( 'Generate All SEO' )
				).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Requirement 34.1-34.6: Accessibility features', () => {
		it( 'should have ARIA labels on all buttons', () => {
			render( <AiGeneratorPanel /> );

			const generateAllButton = screen.getByText( 'Generate All SEO' );
			expect( generateAllButton ).toHaveAttribute( 'aria-label' );
			expect( generateAllButton.getAttribute( 'aria-label' ) ).toContain(
				'Generate all SEO'
			);

			const textButton = screen.getByText( 'Text Only' );
			expect( textButton ).toHaveAttribute( 'aria-label' );

			const imageButton = screen.getByText( 'Image Only' );
			expect( imageButton ).toHaveAttribute( 'aria-label' );
		} );

		it( 'should have ARIA live regions for status messages', () => {
			render( <AiGeneratorPanel /> );

			const statusRegion = document.querySelector(
				'[role="status"][aria-live="polite"]'
			);
			expect( statusRegion ).toBeInTheDocument();

			const alertRegion = document.querySelector(
				'[role="alert"][aria-live="assertive"]'
			);
			expect( alertRegion ).toBeInTheDocument();
		} );

		it( 'should announce generation start to screen readers', async () => {
			mockApiFetch.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) =>
						setTimeout(
							() =>
								resolve( {
									success: true,
									data: { seo_title: 'Test' },
								} ),
							100
						)
					)
			);

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			const statusRegion = document.querySelector(
				'[role="status"][aria-live="polite"]'
			);
			await waitFor( () => {
				expect( statusRegion.textContent ).toContain(
					'Generating content'
				);
			} );
		} );

		it( 'should announce generation success to screen readers', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: {
					seo_title: 'Test Title',
					provider: 'Gemini',
				},
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			const statusRegion = document.querySelector(
				'[role="status"][aria-live="polite"]'
			);
			await waitFor( () => {
				expect( statusRegion.textContent ).toContain(
					'generated successfully'
				);
			} );
		} );

		it( 'should announce errors to screen readers', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: false,
				message: 'Generation failed',
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			const alertRegion = document.querySelector(
				'[role="alert"][aria-live="assertive"]'
			);
			await waitFor( () => {
				expect( alertRegion.textContent ).toContain(
					'Generation failed'
				);
			} );
		} );

		it( 'should set aria-busy on buttons during generation', async () => {
			mockApiFetch.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) =>
						setTimeout(
							() =>
								resolve( {
									success: true,
									data: { seo_title: 'Test' },
								} ),
							200
						)
					)
			);

			render( <AiGeneratorPanel /> );
			const generateButton = screen.getByText( 'Generate All SEO' );

			fireEvent.click( generateButton );

			// Button should have aria-busy="true" during generation
			await waitFor(
				() => {
					expect( generateButton ).toHaveAttribute(
						'aria-busy',
						'true'
					);
				},
				{ timeout: 100 }
			);

			// After generation completes, button should be hidden (preview shown)
			await waitFor( () => {
				expect(
					screen.getByTestId( 'preview-panel' )
				).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Requirement 28.1-28.8: API integration', () => {
		it( 'should include nonce in request headers', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: { seo_title: 'Test' },
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						headers: expect.objectContaining( {
							'X-WP-Nonce': 'test-nonce',
						} ),
					} )
				);
			} );
		} );

		it( 'should include post_id in request data', async () => {
			mockApiFetch.mockResolvedValueOnce( {
				success: true,
				data: { seo_title: 'Test' },
			} );

			render( <AiGeneratorPanel /> );
			fireEvent.click( screen.getByText( 'Generate All SEO' ) );

			await waitFor( () => {
				expect( mockApiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						data: expect.objectContaining( {
							post_id: 123,
						} ),
					} )
				);
			} );
		} );
	} );
} );
