/**
 * Schema Tab Component
 *
 * Schema type selection for structured data override.
 * Reads from meowseo/data store and persists via useEntityProp.
 *
 * @package
 * @since 1.0.0
 */

import { SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Schema Tab Component
 */
export default function SchemaTab() {
	const { updateMeta } = useDispatch( 'meowseo/data' );

	// Get current post type
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// Use useEntityProp for postmeta persistence
	const [ schemaType, setSchemaType ] = useEntityProp(
		'postType',
		postType,
		'meta',
		'meowseo_schema_type'
	);

	// Schema type options
	const schemaOptions = [
		{ label: __( 'Auto (Default)', 'meowseo' ), value: '' },
		{ label: __( 'Article', 'meowseo' ), value: 'Article' },
		{ label: __( 'BlogPosting', 'meowseo' ), value: 'BlogPosting' },
		{ label: __( 'NewsArticle', 'meowseo' ), value: 'NewsArticle' },
		{ label: __( 'WebPage', 'meowseo' ), value: 'WebPage' },
		{ label: __( 'FAQPage', 'meowseo' ), value: 'FAQPage' },
		{ label: __( 'HowTo', 'meowseo' ), value: 'HowTo' },
		{ label: __( 'Product', 'meowseo' ), value: 'Product' },
		{ label: __( 'Event', 'meowseo' ), value: 'Event' },
		{ label: __( 'Recipe', 'meowseo' ), value: 'Recipe' },
	];

	/**
	 * Handle schema type change
	 *
	 * @param {string} value Schema type value
	 */
	const handleChange = ( value ) => {
		updateMeta( 'schemaType', value );
		setSchemaType( value );
	};

	return (
		<div className="meowseo-schema-tab">
			<h3>{ __( 'Structured Data (Schema.org)', 'meowseo' ) }</h3>
			<p className="description">
				{ __(
					'Override the default schema type for this post. Leave as "Auto" to use the default type based on post type.',
					'meowseo'
				) }
			</p>

			<SelectControl
				label={ __( 'Schema Type', 'meowseo' ) }
				value={ schemaType || '' }
				options={ schemaOptions }
				onChange={ handleChange }
				help={ __(
					'This controls the @type in the JSON-LD structured data output.',
					'meowseo'
				) }
			/>

			<div className="meowseo-schema-info">
				<h4>{ __( 'About Schema Types', 'meowseo' ) }</h4>
				<ul>
					<li>
						<strong>{ __( 'Article', 'meowseo' ) }:</strong>{ ' ' }
						{ __( 'General article content', 'meowseo' ) }
					</li>
					<li>
						<strong>{ __( 'BlogPosting', 'meowseo' ) }:</strong>{ ' ' }
						{ __( 'Blog post content', 'meowseo' ) }
					</li>
					<li>
						<strong>{ __( 'NewsArticle', 'meowseo' ) }:</strong>{ ' ' }
						{ __( 'News article content', 'meowseo' ) }
					</li>
					<li>
						<strong>{ __( 'FAQPage', 'meowseo' ) }:</strong>{ ' ' }
						{ __( 'Frequently asked questions', 'meowseo' ) }
					</li>
					<li>
						<strong>{ __( 'Product', 'meowseo' ) }:</strong>{ ' ' }
						{ __( 'Product pages (WooCommerce)', 'meowseo' ) }
					</li>
				</ul>
			</div>
		</div>
	);
}
