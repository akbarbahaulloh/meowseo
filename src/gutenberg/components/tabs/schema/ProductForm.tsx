/**
 * ProductForm Component
 *
 * Form for Product schema configuration.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 13.8, 13.9
 */

import {
	TextControl,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';

interface ProductSchema {
	name: string;
	description: string;
	sku: string;
	offers: {
		price: string;
		priceCurrency: string;
		availability: string;
	};
}

/**
 * ProductForm Component
 *
 * Requirements:
 * - 13.8: Provide inputs for name, description, SKU, price, currency, availability
 * - 13.9: Persist configuration to _meowseo_schema_config postmeta
 */
const ProductForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	// Parse schema config from JSON
	const schemaConfig: ProductSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						description: '',
						sku: '',
						offers: {
							price: '',
							priceCurrency: 'USD',
							availability: 'InStock',
						},
					};
				}
		  } )()
		: {
				name: '',
				description: '',
				sku: '',
				offers: {
					price: '',
					priceCurrency: 'USD',
					availability: 'InStock',
				},
		  };

	// Ensure offers object exists
	if ( ! schemaConfig.offers ) {
		schemaConfig.offers = {
			price: '',
			priceCurrency: 'USD',
			availability: 'InStock',
		};
	}

	// Update a top-level field
	const updateField = useCallback(
		( field: keyof ProductSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	// Update an offers field
	const updateOffersField = useCallback(
		( field: keyof ProductSchema[ 'offers' ], value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				offers: { ...schemaConfig.offers, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const availabilityOptions = [
		{ label: __( 'In Stock', 'meowseo' ), value: 'InStock' },
		{ label: __( 'Out of Stock', 'meowseo' ), value: 'OutOfStock' },
		{ label: __( 'Pre-Order', 'meowseo' ), value: 'PreOrder' },
		{ label: __( 'Discontinued', 'meowseo' ), value: 'Discontinued' },
	];

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Product Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
			/>

			<TextareaControl
				label={ __( 'Description', 'meowseo' ) }
				value={ schemaConfig.description }
				onChange={ ( value ) => updateField( 'description', value ) }
				rows={ 4 }
			/>

			<TextControl
				label={ __( 'SKU', 'meowseo' ) }
				value={ schemaConfig.sku }
				onChange={ ( value ) => updateField( 'sku', value ) }
				help={ __( 'Stock Keeping Unit', 'meowseo' ) }
			/>

			<h3>{ __( 'Pricing', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Price', 'meowseo' ) }
				value={ schemaConfig.offers.price }
				onChange={ ( value ) => updateOffersField( 'price', value ) }
				type="number"
				step="0.01"
			/>

			<TextControl
				label={ __( 'Currency', 'meowseo' ) }
				value={ schemaConfig.offers.priceCurrency }
				onChange={ ( value ) =>
					updateOffersField( 'priceCurrency', value )
				}
				help={ __( 'e.g., USD, EUR, GBP', 'meowseo' ) }
			/>

			<SelectControl
				label={ __( 'Availability', 'meowseo' ) }
				value={ schemaConfig.offers.availability }
				options={ availabilityOptions }
				onChange={ ( value ) =>
					updateOffersField( 'availability', value )
				}
			/>
		</div>
	);
};

export default ProductForm;
