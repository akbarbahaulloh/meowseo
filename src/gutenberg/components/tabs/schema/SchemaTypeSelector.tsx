/**
 * SchemaTypeSelector Component
 *
 * Displays a SelectControl for choosing schema type.
 * Uses useEntityPropBinding for _meowseo_schema_type postmeta.
 *
 * Requirements: 9.1, 9.5
 */

import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

/**
 * SchemaTypeSelector Component
 *
 * Requirements:
 * - 9.1: Display schema type selector with Article, WebPage, FAQPage, HowTo, LocalBusiness, Product
 * - 9.5: Save selection to _meowseo_schema_type postmeta
 */
const SchemaTypeSelector: React.FC = () => {
	const [ schemaType, setSchemaType ] = useEntityPropBinding(
		'_meowseo_schema_type'
	);

	const schemaOptions = [
		{ label: __( 'None', 'meowseo' ), value: '' },
		{ label: __( 'Article', 'meowseo' ), value: 'Article' },
		{ label: __( 'WebPage', 'meowseo' ), value: 'WebPage' },
		{ label: __( 'FAQPage', 'meowseo' ), value: 'FAQPage' },
		{ label: __( 'HowTo', 'meowseo' ), value: 'HowTo' },
		{ label: __( 'LocalBusiness', 'meowseo' ), value: 'LocalBusiness' },
		{ label: __( 'Product', 'meowseo' ), value: 'Product' },
	];

	return (
		<SelectControl
			label={ __( 'Schema Type', 'meowseo' ) }
			value={ schemaType }
			options={ schemaOptions }
			onChange={ setSchemaType }
			help={ __(
				'Select the structured data type for this content',
				'meowseo'
			) }
		/>
	);
};

export default SchemaTypeSelector;
