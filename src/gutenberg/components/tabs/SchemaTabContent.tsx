/**
 * SchemaTabContent Component
 *
 * Main content for the Schema tab with lazy-loaded schema forms.
 * Validates schema configuration before saving.
 *
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 20.3, 20.4, 20.5
 */

import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import SchemaTypeSelector from './schema/SchemaTypeSelector';
import SpeakableToggle from './schema/SpeakableToggle';

// Lazy load schema forms for code splitting (Requirement 16.4)
const ArticleForm = lazy( () => import( './schema/ArticleForm' ) );
const FAQPageForm = lazy( () => import( './schema/FAQPageForm' ) );
const HowToForm = lazy( () => import( './schema/HowToForm' ) );
const LocalBusinessForm = lazy( () => import( './schema/LocalBusinessForm' ) );
const ProductForm = lazy( () => import( './schema/ProductForm' ) );

/**
 * Validate schema configuration against selected schema type
 *
 * Requirements:
 * - 9.6: Validate schema configuration before saving
 * @param schemaType
 * @param configJson
 */
const validateSchemaConfig = (
	schemaType: string,
	configJson: string
): boolean => {
	if ( ! schemaType || ! configJson ) {
		return true; // Empty is valid
	}

	try {
		const config = JSON.parse( configJson );

		// Basic validation based on schema type
		switch ( schemaType ) {
			case 'Article':
				return typeof config.headline === 'string';
			case 'WebPage':
				return true; // WebPage doesn't require additional config
			case 'FAQPage':
				return Array.isArray( config.questions );
			case 'HowTo':
				return (
					typeof config.name === 'string' &&
					Array.isArray( config.steps )
				);
			case 'LocalBusiness':
				return (
					typeof config.name === 'string' &&
					typeof config.address === 'object'
				);
			case 'Product':
				return (
					typeof config.name === 'string' &&
					typeof config.offers === 'object'
				);
			default:
				return true;
		}
	} catch ( e ) {
		return false;
	}
};

/**
 * SchemaTabContent Component
 *
 * Requirements:
 * - 9.1: Display schema type selector
 * - 9.2: Show FAQ editor when FAQPage selected
 * - 9.3: Show HowTo editor when HowTo selected
 * - 9.4: Show LocalBusiness fields when LocalBusiness selected
 * - 9.5: Save schema type to _meowseo_schema_type postmeta
 * - 9.6: Save schema configuration to _meowseo_schema_config postmeta
 * - 20.3: Provide toggle to mark block as speakable
 * - 20.4: Add id="meowseo-direct-answer" to marked block
 * - 20.5: Save block ID to _meowseo_speakable_block postmeta
 */
const SchemaTabContent: React.FC = () => {
	const [ schemaType ] = useEntityPropBinding( '_meowseo_schema_type' );
	const [ schemaConfig ] = useEntityPropBinding( '_meowseo_schema_config' );

	// Validate current configuration
	const isValid = validateSchemaConfig( schemaType, schemaConfig );

	// Render the appropriate schema form based on selected type
	const renderSchemaForm = () => {
		if ( ! schemaType ) {
			return (
				<p className="meowseo-schema-help">
					{ __(
						'Select a schema type above to configure structured data for this content.',
						'meowseo'
					) }
				</p>
			);
		}

		// Lazy load the appropriate form component
		return (
			<Suspense fallback={ <Spinner /> }>
				{ schemaType === 'Article' && <ArticleForm /> }
				{ schemaType === 'FAQPage' && <FAQPageForm /> }
				{ schemaType === 'HowTo' && <HowToForm /> }
				{ schemaType === 'LocalBusiness' && <LocalBusinessForm /> }
				{ schemaType === 'Product' && <ProductForm /> }
			</Suspense>
		);
	};

	return (
		<div className="meowseo-schema-tab">
			<SchemaTypeSelector />

			{ ! isValid && schemaConfig && (
				<div className="meowseo-schema-validation-error">
					{ __(
						'Invalid schema configuration. Please check your inputs.',
						'meowseo'
					) }
				</div>
			) }

			{ renderSchemaForm() }

			{ /* Speakable content toggle for Article schema */ }
			{ ( schemaType === 'Article' || schemaType === '' ) && (
				<>
					<hr style={ { margin: '20px 0' } } />
					<h3>{ __( 'Voice Assistant Support', 'meowseo' ) }</h3>
					<SpeakableToggle />
				</>
			) }
		</div>
	);
};

export default SchemaTabContent;
