/**
 * AI Generator Panel Component
 *
 * Main component for the AI Generator sidebar panel in Gutenberg.
 * Provides UI for generating SEO content and featured images.
 *
 * Accessibility Features (Requirements 34.1-34.6):
 * - ARIA labels for all buttons
 * - ARIA live regions for status messages
 * - Full keyboard navigation support
 * - Focus indicators for all focusable elements
 * - Proper semantic HTML structure
 *
 * Requirements: 7.1, 7.2, 7.3, 7.7, 7.8, 7.9, 8.1-8.7, 9.1-9.5, 10.1-10.4, 11.1-11.5, 34.1-34.6
 */

import { useState, useCallback, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Spinner,
	Notice,
	Panel,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PreviewPanel } from './PreviewPanel';
import '../styles/ai-generator-panel.css';

/**
 * AiGeneratorPanel Component
 *
 * Manages the generation workflow:
 * 1. Display generation buttons
 * 2. Handle API calls to generate content
 * 3. Display preview of generated content
 * 4. Allow user to apply content to post fields
 *
 * Accessibility Features:
 * - ARIA labels for all buttons (Requirements 34.1)
 * - ARIA live regions for status messages (Requirements 34.2)
 * - Full keyboard navigation with proper tab order (Requirements 34.3, 34.4)
 * - Focus indicators via CSS (Requirements 34.4)
 *
 * Requirements: 7.1, 7.2, 7.3, 7.7, 7.8, 7.9, 34.1-34.6
 */
export function AiGeneratorPanel() {
	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ generatedContent, setGeneratedContent ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ usedProvider, setUsedProvider ] = useState( null );
	const [ isFallback, setIsFallback ] = useState( false );
	const [ generationType, setGenerationType ] = useState( null );
	const [ successMessage, setSuccessMessage ] = useState( null );

	// Refs for ARIA live regions
	const statusLiveRegionRef = useRef( null );
	const errorLiveRegionRef = useRef( null );

	// Get current post ID from editor
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	// Get nonce from window object (passed via wp_localize_script)
	const nonce = window.meowseoAiData?.nonce || '';

	/**
	 * Handle generation request
	 *
	 * Requirements: 7.2, 7.7, 28.1-28.8, 34.2
	 */
	const handleGenerate = useCallback(
		async ( type = 'all' ) => {
			setIsGenerating( true );
			setError( null );
			setGeneratedContent( null );
			setGenerationType( type );
			setSuccessMessage( null );

			// Announce to screen readers that generation is starting
			if ( statusLiveRegionRef.current ) {
				statusLiveRegionRef.current.textContent = __(
					'Generating content, please wait…',
					'meowseo'
				);
			}

			try {
				const response = await apiFetch( {
					path: '/meowseo/v1/ai/generate',
					method: 'POST',
					headers: {
						'X-WP-Nonce': nonce,
					},
					data: {
						post_id: postId,
						type,
						generate_image: type === 'all' || type === 'image',
						bypass_cache: false,
					},
				} );

				if ( response.success ) {
					setGeneratedContent( response.data );
					setUsedProvider( response.data.provider );
					setIsFallback( response.data.is_fallback || false );

					// Announce success to screen readers
					if ( statusLiveRegionRef.current ) {
						statusLiveRegionRef.current.textContent = __(
							'Content generated successfully. Review the preview below.',
							'meowseo'
						);
					}
				} else {
					const errorMsg =
						response.message ||
						__( 'Generation failed', 'meowseo' );
					setError( errorMsg );

					// Announce error to screen readers
					if ( errorLiveRegionRef.current ) {
						errorLiveRegionRef.current.textContent = errorMsg;
					}
				}
			} catch ( err ) {
				// Handle specific error cases
				let errorMsg = __(
					'Generation failed. Please check provider configuration.',
					'meowseo'
				);

				if ( err.status === 403 ) {
					errorMsg = __(
						'You do not have permission to generate content',
						'meowseo'
					);
				} else if (
					err.message &&
					err.message.includes( '300 words' )
				) {
					errorMsg = __(
						'Content must be at least 300 words for generation',
						'meowseo'
					);
				} else if ( err.message ) {
					errorMsg = err.message;
				}

				setError( errorMsg );

				// Announce error to screen readers
				if ( errorLiveRegionRef.current ) {
					errorLiveRegionRef.current.textContent = errorMsg;
				}
			} finally {
				setIsGenerating( false );
			}
		},
		[ postId, nonce ]
	);

	/**
	 * Handle applying generated content to post fields
	 *
	 * Requirements: 8.7, 27.1-27.10, 34.2
	 */
	const handleApply = useCallback(
		async ( content ) => {
			setIsGenerating( true );
			setError( null );
			setSuccessMessage( null );

			// Announce to screen readers that content is being applied
			if ( statusLiveRegionRef.current ) {
				statusLiveRegionRef.current.textContent = __(
					'Applying content to post fields…',
					'meowseo'
				);
			}

			try {
				const response = await apiFetch( {
					path: '/meowseo/v1/ai/apply',
					method: 'POST',
					headers: {
						'X-WP-Nonce': nonce,
					},
					data: {
						post_id: postId,
						content,
					},
				} );

				if ( response.success ) {
					setGeneratedContent( null );
					setSuccessMessage(
						__( 'Content applied successfully!', 'meowseo' )
					);

					// Announce success to screen readers
					if ( statusLiveRegionRef.current ) {
						statusLiveRegionRef.current.textContent = __(
							'Content applied successfully to post fields.',
							'meowseo'
						);
					}

					// Trigger editor update to reflect changes
					window.wp.data.dispatch( 'core/editor' ).editPost( {} );
				} else {
					const errorMsg =
						response.message ||
						__( 'Failed to apply content', 'meowseo' );
					setError( errorMsg );

					// Announce error to screen readers
					if ( errorLiveRegionRef.current ) {
						errorLiveRegionRef.current.textContent = errorMsg;
					}
				}
			} catch ( err ) {
				const errorMsg =
					err.message || __( 'Failed to apply content', 'meowseo' );
				setError( errorMsg );

				// Announce error to screen readers
				if ( errorLiveRegionRef.current ) {
					errorLiveRegionRef.current.textContent = errorMsg;
				}
			} finally {
				setIsGenerating( false );
			}
		},
		[ postId, nonce ]
	);

	/**
	 * Handle retry after error
	 *
	 * Requirements: 11.1-11.5, 34.1
	 */
	const handleRetry = useCallback( () => {
		setError( null );
		handleGenerate( generationType || 'all' );
	}, [ generationType, handleGenerate ] );

	return (
		<div className="meowseo-ai-generator-panel">
			{ /* ARIA live regions for status announcements (Requirement 34.2) */ }
			<div
				ref={ statusLiveRegionRef }
				className="meowseo-sr-only"
				role="status"
				aria-live="polite"
				aria-atomic="true"
			/>
			<div
				ref={ errorLiveRegionRef }
				className="meowseo-sr-only"
				role="alert"
				aria-live="assertive"
				aria-atomic="true"
			/>

			<Panel>
				<PanelBody
					title={ __( 'AI Generator', 'meowseo' ) }
					initialOpen={ true }
				>
					{ /* Display success message if content was applied */ }
					{ successMessage && (
						<Notice
							status="success"
							isDismissible={ true }
							onRemove={ () => setSuccessMessage( null ) }
						>
							<p>{ successMessage }</p>
						</Notice>
					) }

					{ /* Display error message if generation failed */ }
					{ error && (
						<Notice
							status="error"
							isDismissible={ true }
							onRemove={ () => setError( null ) }
						>
							<p>{ error }</p>
							<div className="meowseo-ai-error-actions">
								<Button
									isSecondary
									onClick={ handleRetry }
									disabled={ isGenerating }
									aria-label={ __(
										'Retry content generation',
										'meowseo'
									) }
								>
									{ __( 'Retry', 'meowseo' ) }
								</Button>
								<a
									href={
										window.meowseoAiData?.settingsUrl || '#'
									}
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Settings', 'meowseo' ) }
								</a>
							</div>
						</Notice>
					) }

					{ /* Display fallback notification */ }
					{ isFallback && usedProvider && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'Generated via ', 'meowseo' ) }
							<strong>{ usedProvider }</strong>
							{ __(
								' (primary provider unavailable)',
								'meowseo'
							) }
							<br />
							<a
								href={
									window.meowseoAiData?.settingsUrl || '#'
								}
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Configure providers', 'meowseo' ) }
							</a>
						</Notice>
					) }

					{ /* Display generation buttons */ }
					{ ! generatedContent && (
						<div className="meowseo-ai-buttons">
							<PanelRow>
								<Button
									isPrimary
									onClick={ () => handleGenerate( 'all' ) }
									disabled={ isGenerating }
									className="meowseo-ai-generate-all"
									aria-label={ __(
										'Generate all SEO content including title, description, keywords, and featured image',
										'meowseo'
									) }
									aria-busy={ isGenerating }
								>
									{ isGenerating ? (
										<>
											<Spinner />
											{ __( 'Generating…', 'meowseo' ) }
										</>
									) : (
										__( 'Generate All SEO', 'meowseo' )
									) }
								</Button>
							</PanelRow>

							<PanelRow>
								<Button
									isSecondary
									onClick={ () => handleGenerate( 'text' ) }
									disabled={ isGenerating }
									className="meowseo-ai-generate-text"
									aria-label={ __(
										'Generate text content only (title, description, keywords)',
										'meowseo'
									) }
									aria-busy={ isGenerating }
								>
									{ __( 'Text Only', 'meowseo' ) }
								</Button>
								<Button
									isSecondary
									onClick={ () => handleGenerate( 'image' ) }
									disabled={ isGenerating }
									className="meowseo-ai-generate-image"
									aria-label={ __(
										'Generate featured image only',
										'meowseo'
									) }
									aria-busy={ isGenerating }
								>
									{ __( 'Image Only', 'meowseo' ) }
								</Button>
							</PanelRow>

							{ /* Display provider indicator */ }
							{ usedProvider && (
								<PanelRow>
									<div
										className="meowseo-ai-provider-badge"
										role="status"
									>
										{ __( 'Generated via: ', 'meowseo' ) }
										<strong>{ usedProvider }</strong>
									</div>
								</PanelRow>
							) }
						</div>
					) }

					{ /* Display preview panel */ }
					{ generatedContent && (
						<PreviewPanel
							content={ generatedContent }
							onApply={ handleApply }
							onCancel={ () => setGeneratedContent( null ) }
							isApplying={ isGenerating }
							provider={ usedProvider }
						/>
					) }
				</PanelBody>
			</Panel>
		</div>
	);
}
