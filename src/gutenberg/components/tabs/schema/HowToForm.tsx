/**
 * HowToForm Component
 *
 * Form for HowTo schema configuration with repeatable step fields.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 13.6, 13.9
 */

import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';
import './SchemaForms.css';

interface HowToStep {
	name: string;
	text: string;
	image?: string;
}

interface HowToSchema {
	name: string;
	steps: HowToStep[];
}

/**
 * HowToForm Component
 *
 * Requirements:
 * - 13.6: Provide repeatable step fields with name, text, optional image
 * - 13.9: Persist configuration to _meowseo_schema_config postmeta
 */
const HowToForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	// Parse schema config from JSON
	const schemaConfig: HowToSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						steps: [ { name: '', text: '', image: '' } ],
					};
				}
		  } )()
		: { name: '', steps: [ { name: '', text: '', image: '' } ] };

	// Ensure steps array exists
	if ( ! schemaConfig.steps || ! Array.isArray( schemaConfig.steps ) ) {
		schemaConfig.steps = [ { name: '', text: '', image: '' } ];
	}

	// Update the HowTo name
	const updateName = useCallback(
		( value: string ) => {
			const updatedConfig = { ...schemaConfig, name: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	// Update a specific step
	const updateStep = useCallback(
		( index: number, field: keyof HowToStep, value: string ) => {
			const updatedSteps = [ ...schemaConfig.steps ];
			updatedSteps[ index ] = {
				...updatedSteps[ index ],
				[ field ]: value,
			};
			const updatedConfig = { ...schemaConfig, steps: updatedSteps };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	// Add a new step
	const addStep = useCallback( () => {
		const updatedSteps = [
			...schemaConfig.steps,
			{ name: '', text: '', image: '' },
		];
		const updatedConfig = { ...schemaConfig, steps: updatedSteps };
		setSchemaConfigJson( JSON.stringify( updatedConfig ) );
	}, [ schemaConfig, setSchemaConfigJson ] );

	// Remove a step
	const removeStep = useCallback(
		( index: number ) => {
			const updatedSteps = schemaConfig.steps.filter(
				( _, i ) => i !== index
			);
			const updatedConfig = { ...schemaConfig, steps: updatedSteps };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'HowTo Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ updateName }
				help={ __( 'The name of the HowTo guide', 'meowseo' ) }
			/>

			<h3>{ __( 'Steps', 'meowseo' ) }</h3>

			{ schemaConfig.steps.map( ( step, index ) => (
				<div key={ index } className="meowseo-howto-step">
					<h4>
						{ __( 'Step', 'meowseo' ) } { index + 1 }
					</h4>

					<TextControl
						label={ __( 'Step Name', 'meowseo' ) }
						value={ step.name }
						onChange={ ( value ) =>
							updateStep( index, 'name', value )
						}
					/>

					<TextareaControl
						label={ __( 'Step Instructions', 'meowseo' ) }
						value={ step.text }
						onChange={ ( value ) =>
							updateStep( index, 'text', value )
						}
						rows={ 4 }
					/>

					<TextControl
						label={ __( 'Step Image URL (optional)', 'meowseo' ) }
						value={ step.image || '' }
						onChange={ ( value ) =>
							updateStep( index, 'image', value )
						}
						type="url"
					/>

					{ schemaConfig.steps.length > 1 && (
						<Button
							isDestructive
							variant="secondary"
							onClick={ () => removeStep( index ) }
						>
							{ __( 'Remove Step', 'meowseo' ) }
						</Button>
					) }
				</div>
			) ) }

			<Button variant="primary" onClick={ addStep }>
				{ __( 'Add Step', 'meowseo' ) }
			</Button>
		</div>
	);
};

export default HowToForm;
