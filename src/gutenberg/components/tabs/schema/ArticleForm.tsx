/**
 * ArticleForm Component
 *
 * Form for Article schema configuration.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 13.4, 13.9
 */

import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';

interface ArticleSchema {
	headline: string;
	datePublished: string;
	dateModified: string;
	author: string;
}

/**
 * ArticleForm Component
 *
 * Requirements:
 * - 13.4: Provide inputs for headline, datePublished, dateModified, author
 * - 13.9: Persist configuration to _meowseo_schema_config postmeta
 */
const ArticleForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	// Parse schema config from JSON
	const schemaConfig: ArticleSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						headline: '',
						datePublished: '',
						dateModified: '',
						author: '',
					};
				}
		  } )()
		: { headline: '', datePublished: '', dateModified: '', author: '' };

	// Update a specific field in the schema config
	const updateField = useCallback(
		( field: keyof ArticleSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Headline', 'meowseo' ) }
				value={ schemaConfig.headline }
				onChange={ ( value ) => updateField( 'headline', value ) }
				help={ __( 'The headline of the article', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Date Published', 'meowseo' ) }
				value={ schemaConfig.datePublished }
				onChange={ ( value ) => updateField( 'datePublished', value ) }
				type="date"
				help={ __( 'The date the article was published', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Date Modified', 'meowseo' ) }
				value={ schemaConfig.dateModified }
				onChange={ ( value ) => updateField( 'dateModified', value ) }
				type="date"
				help={ __(
					'The date the article was last modified',
					'meowseo'
				) }
			/>

			<TextControl
				label={ __( 'Author', 'meowseo' ) }
				value={ schemaConfig.author }
				onChange={ ( value ) => updateField( 'author', value ) }
				help={ __( 'The author of the article', 'meowseo' ) }
			/>
		</div>
	);
};

export default ArticleForm;
