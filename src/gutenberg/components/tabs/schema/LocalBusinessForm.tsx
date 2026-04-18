/**
 * LocalBusinessForm Component
 *
 * Form for LocalBusiness schema configuration.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 13.7, 13.9
 */

import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';

interface LocalBusinessSchema {
	name: string;
	address: {
		streetAddress: string;
		addressLocality: string;
		addressRegion: string;
		postalCode: string;
		addressCountry: string;
	};
	telephone: string;
	openingHours: string;
	geo?: {
		latitude: string;
		longitude: string;
	};
}

/**
 * LocalBusinessForm Component
 *
 * Requirements:
 * - 13.7: Provide inputs for name, address, telephone, opening hours, optional geo coordinates
 * - 13.9: Persist configuration to _meowseo_schema_config postmeta
 */
const LocalBusinessForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	// Parse schema config from JSON
	const schemaConfig: LocalBusinessSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						address: {
							streetAddress: '',
							addressLocality: '',
							addressRegion: '',
							postalCode: '',
							addressCountry: '',
						},
						telephone: '',
						openingHours: '',
						geo: { latitude: '', longitude: '' },
					};
				}
		  } )()
		: {
				name: '',
				address: {
					streetAddress: '',
					addressLocality: '',
					addressRegion: '',
					postalCode: '',
					addressCountry: '',
				},
				telephone: '',
				openingHours: '',
				geo: { latitude: '', longitude: '' },
		  };

	// Ensure address object exists
	if ( ! schemaConfig.address ) {
		schemaConfig.address = {
			streetAddress: '',
			addressLocality: '',
			addressRegion: '',
			postalCode: '',
			addressCountry: '',
		};
	}

	// Ensure geo object exists
	if ( ! schemaConfig.geo ) {
		schemaConfig.geo = { latitude: '', longitude: '' };
	}

	// Update a top-level field
	const updateField = useCallback(
		( field: keyof LocalBusinessSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	// Update an address field
	const updateAddressField = useCallback(
		( field: keyof LocalBusinessSchema[ 'address' ], value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				address: { ...schemaConfig.address, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	// Update a geo field
	const updateGeoField = useCallback(
		( field: 'latitude' | 'longitude', value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				geo: { ...schemaConfig.geo, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Business Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
			/>

			<h3>{ __( 'Address', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Street Address', 'meowseo' ) }
				value={ schemaConfig.address.streetAddress }
				onChange={ ( value ) =>
					updateAddressField( 'streetAddress', value )
				}
			/>

			<TextControl
				label={ __( 'City', 'meowseo' ) }
				value={ schemaConfig.address.addressLocality }
				onChange={ ( value ) =>
					updateAddressField( 'addressLocality', value )
				}
			/>

			<TextControl
				label={ __( 'State/Region', 'meowseo' ) }
				value={ schemaConfig.address.addressRegion }
				onChange={ ( value ) =>
					updateAddressField( 'addressRegion', value )
				}
			/>

			<TextControl
				label={ __( 'Postal Code', 'meowseo' ) }
				value={ schemaConfig.address.postalCode }
				onChange={ ( value ) =>
					updateAddressField( 'postalCode', value )
				}
			/>

			<TextControl
				label={ __( 'Country', 'meowseo' ) }
				value={ schemaConfig.address.addressCountry }
				onChange={ ( value ) =>
					updateAddressField( 'addressCountry', value )
				}
			/>

			<TextControl
				label={ __( 'Telephone', 'meowseo' ) }
				value={ schemaConfig.telephone }
				onChange={ ( value ) => updateField( 'telephone', value ) }
				type="tel"
			/>

			<TextareaControl
				label={ __( 'Opening Hours', 'meowseo' ) }
				value={ schemaConfig.openingHours }
				onChange={ ( value ) => updateField( 'openingHours', value ) }
				help={ __( 'e.g., Mo-Fr 09:00-17:00', 'meowseo' ) }
				rows={ 3 }
			/>

			<h3>{ __( 'Geo Coordinates (Optional)', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Latitude', 'meowseo' ) }
				value={ schemaConfig.geo?.latitude || '' }
				onChange={ ( value ) => updateGeoField( 'latitude', value ) }
				type="number"
				step="any"
			/>

			<TextControl
				label={ __( 'Longitude', 'meowseo' ) }
				value={ schemaConfig.geo?.longitude || '' }
				onChange={ ( value ) => updateGeoField( 'longitude', value ) }
				type="number"
				step="any"
			/>
		</div>
	);
};

export default LocalBusinessForm;
