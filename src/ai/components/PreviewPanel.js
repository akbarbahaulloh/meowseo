/**
 * Preview Panel Component
 * 
 * Displays generated content in a preview panel with editable fields.
 * Allows users to review and modify generated content before applying.
 * 
 * Accessibility Features (Requirements 34.1-34.6):
 * - ARIA labels for all buttons
 * - Proper label associations with form controls
 * - ARIA descriptions for complex controls
 * - Full keyboard navigation support
 * - Focus indicators for all focusable elements
 * 
 * Requirements: 8.1-8.7, 34.1-34.6
 */

import { useState, useCallback, useRef } from '@wordpress/element';
import {
	Button,
	TextControl,
	TextareaControl,
	PanelRow,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import '../styles/preview-panel.css';

/**
 * Character count constraints for different fields
 * 
 * Requirements: 8.3, 8.4
 */
const FIELD_CONSTRAINTS = {
	seo_title: { min: 0, max: 60, label: __( 'SEO Title', 'meowseo' ) },
	seo_description: { min: 140, max: 160, label: __( 'SEO Description', 'meowseo' ) },
	focus_keyword: { min: 1, max: 100, label: __( 'Focus Keyword', 'meowseo' ) },
	og_title: { min: 0, max: 100, label: __( 'OG Title', 'meowseo' ) },
	og_description: { min: 100, max: 200, label: __( 'OG Description', 'meowseo' ) },
	twitter_title: { min: 0, max: 70, label: __( 'Twitter Title', 'meowseo' ) },
	twitter_description: { min: 0, max: 200, label: __( 'Twitter Description', 'meowseo' ) },
	direct_answer: { min: 300, max: 450, label: __( 'Direct Answer', 'meowseo' ) },
	schema_type: { min: 0, max: 50, label: __( 'Schema Type', 'meowseo' ) },
};

/**
 * PreviewPanel Component
 * 
 * Displays all generated fields with:
 * - Character counts for constrained fields
 * - Highlighting for fields exceeding limits
 * - Editable fields for user modification
 * - Generated image thumbnail
 * - Apply and Cancel buttons
 * 
 * Accessibility Features:
 * - ARIA labels for all buttons (Requirement 34.1)
 * - Proper label associations with inputs (Requirement 34.5)
 * - ARIA descriptions for character counters (Requirement 34.6)
 * - Full keyboard navigation (Requirement 34.3)
 * - Focus indicators via CSS (Requirement 34.4)
 * 
 * Requirements: 8.1-8.7, 34.1-34.6
 */
export function PreviewPanel( { content, onApply, onCancel, isApplying, provider } ) {
	const [ editedContent, setEditedContent ] = useState( content );
	const applyButtonRef = useRef( null );
	const cancelButtonRef = useRef( null );

	/**
	 * Handle field value change
	 * 
	 * Requirements: 8.6
	 */
	const handleFieldChange = useCallback( ( fieldName, value ) => {
		setEditedContent( {
			...editedContent,
			[ fieldName ]: value,
		} );
	}, [ editedContent ] );

	/**
	 * Check if field value exceeds limits
	 * 
	 * Requirements: 8.4
	 */
	const isFieldExceeded = ( fieldName, value ) => {
		const constraint = FIELD_CONSTRAINTS[ fieldName ];
		if ( ! constraint ) return false;

		const length = value ? value.length : 0;
		return length > constraint.max || length < constraint.min;
	};

	/**
	 * Get CSS class for field based on character count
	 * 
	 * Requirements: 8.4
	 */
	const getFieldClass = ( fieldName, value ) => {
		if ( isFieldExceeded( fieldName, value ) ) {
			return 'meowseo-field-exceeded';
		}
		return '';
	};

	/**
	 * Render a preview field with character count
	 * 
	 * Accessibility Features:
	 * - Proper label associations (Requirement 34.5)
	 * - ARIA descriptions for character counters (Requirement 34.6)
	 * - Keyboard navigation support (Requirement 34.3)
	 * 
	 * Requirements: 8.2, 8.3, 8.6, 34.3, 34.5, 34.6
	 */
	const renderField = ( fieldName, value ) => {
		const constraint = FIELD_CONSTRAINTS[ fieldName ];
		const length = value ? value.length : 0;
		const isMultiline = [ 'seo_description', 'og_description', 'twitter_description', 'direct_answer' ].includes( fieldName );
		
		// Generate unique IDs for label association and ARIA descriptions
		const fieldId = `meowseo-field-${ fieldName }`;
		const descriptionId = `meowseo-desc-${ fieldName }`;
		const charCountId = `meowseo-count-${ fieldName }`;
		
		// Build ARIA description for character counter
		let ariaDescription = '';
		if ( constraint ) {
			if ( constraint.min > 0 ) {
				ariaDescription = __( `Character count: ${ length } of ${ constraint.max } maximum, minimum ${ constraint.min } required`, 'meowseo' );
			} else {
				ariaDescription = __( `Character count: ${ length } of ${ constraint.max } maximum`, 'meowseo' );
			}
		}

		return (
			<div key={ fieldName } className={ `meowseo-preview-field ${ getFieldClass( fieldName, value ) }` }>
				{ isMultiline ? (
					<TextareaControl
						label={ constraint?.label || fieldName }
						value={ value || '' }
						onChange={ ( newValue ) => handleFieldChange( fieldName, newValue ) }
						rows={ 3 }
						inputId={ fieldId }
						aria-describedby={ `${ descriptionId } ${ charCountId }` }
						help={ constraint && `${ constraint.min > 0 ? `Minimum: ${ constraint.min } ` : '' }Maximum: ${ constraint.max } characters` }
					/>
				) : (
					<TextControl
						label={ constraint?.label || fieldName }
						value={ value || '' }
						onChange={ ( newValue ) => handleFieldChange( fieldName, newValue ) }
						inputId={ fieldId }
						aria-describedby={ `${ descriptionId } ${ charCountId }` }
						help={ constraint && `${ constraint.min > 0 ? `Minimum: ${ constraint.min } ` : '' }Maximum: ${ constraint.max } characters` }
					/>
				) }
				{ constraint && (
					<div className="meowseo-field-info">
						<span 
							className="meowseo-char-count"
							id={ charCountId }
							role="status"
							aria-live="polite"
						>
							{ length } / { constraint.max }
							{ constraint.min > 0 && ` (min: ${ constraint.min })` }
						</span>
						{ isFieldExceeded( fieldName, value ) && (
							<span 
								className="meowseo-field-warning"
								id={ descriptionId }
								role="alert"
							>
								{ __( 'Exceeds recommended length', 'meowseo' ) }
							</span>
						) }
					</div>
				) }
			</div>
		);
	};

	return (
		<div className="meowseo-preview-panel" role="region" aria-label={ __( 'Generated content preview', 'meowseo' ) }>
			<div className="meowseo-preview-header">
				<h3>{ __( 'Preview Generated Content', 'meowseo' ) }</h3>
				{ provider && (
					<span className="meowseo-provider-info" role="status">
						{ __( 'Generated by: ', 'meowseo' ) }
						<strong>{ provider }</strong>
					</span>
				) }
			</div>

			{ /* Display generated image if available */ }
			{ editedContent.image_url && (
				<div className="meowseo-preview-image">
					<img 
						src={ editedContent.image_url } 
						alt={ __( 'Generated featured image', 'meowseo' ) }
						role="img"
					/>
					<p className="meowseo-image-info">
						{ __( 'Generated featured image', 'meowseo' ) }
					</p>
				</div>
			) }

			{ /* Display editable fields */ }
			<div className="meowseo-preview-fields" role="group" aria-label={ __( 'Editable generated fields', 'meowseo' ) }>
				{ Object.keys( editedContent ).map( ( fieldName ) => {
					// Skip non-text fields
					if ( [ 'image_url', 'provider', 'is_fallback' ].includes( fieldName ) ) {
						return null;
					}

					return renderField( fieldName, editedContent[ fieldName ] );
				} ) }
			</div>

			{ /* Display action buttons */ }
			<div className="meowseo-preview-actions">
				<PanelRow>
					<Button
						isPrimary
						onClick={ () => onApply( editedContent ) }
						disabled={ isApplying }
						className="meowseo-apply-button"
						ref={ applyButtonRef }
						aria-label={ __( 'Apply generated content to post fields', 'meowseo' ) }
						aria-busy={ isApplying }
					>
						{ isApplying ? (
							<>
								<Spinner />
								{ __( 'Applying...', 'meowseo' ) }
							</>
						) : (
							__( 'Apply to Fields', 'meowseo' )
						) }
					</Button>
					<Button
						isSecondary
						onClick={ onCancel }
						disabled={ isApplying }
						className="meowseo-cancel-button"
						ref={ cancelButtonRef }
						aria-label={ __( 'Cancel and close preview', 'meowseo' ) }
					>
						{ __( 'Cancel', 'meowseo' ) }
					</Button>
				</PanelRow>
			</div>
		</div>
	);
}
