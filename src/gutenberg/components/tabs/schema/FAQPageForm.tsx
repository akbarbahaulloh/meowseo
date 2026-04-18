/**
 * FAQPageForm Component
 *
 * Form for FAQPage schema configuration with repeatable Q&A fields.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 13.5, 13.9
 */

import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';
import './SchemaForms.css';

interface FAQItem {
	question: string;
	answer: string;
}

interface FAQPageSchema {
	questions: FAQItem[];
}

/**
 * FAQPageForm Component
 *
 * Requirements:
 * - 13.5: Provide repeatable question and answer fields
 * - 13.9: Persist configuration to _meowseo_schema_config postmeta
 */
const FAQPageForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	// Parse schema config from JSON
	const schemaConfig: FAQPageSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return { questions: [ { question: '', answer: '' } ] };
				}
		  } )()
		: { questions: [ { question: '', answer: '' } ] };

	// Ensure questions array exists
	if (
		! schemaConfig.questions ||
		! Array.isArray( schemaConfig.questions )
	) {
		schemaConfig.questions = [ { question: '', answer: '' } ];
	}

	// Update a specific question
	const updateQuestion = useCallback(
		( index: number, field: keyof FAQItem, value: string ) => {
			const updatedQuestions = [ ...schemaConfig.questions ];
			updatedQuestions[ index ] = {
				...updatedQuestions[ index ],
				[ field ]: value,
			};
			setSchemaConfigJson(
				JSON.stringify( { questions: updatedQuestions } )
			);
		},
		[ schemaConfig.questions, setSchemaConfigJson ]
	);

	// Add a new question
	const addQuestion = useCallback( () => {
		const updatedQuestions = [
			...schemaConfig.questions,
			{ question: '', answer: '' },
		];
		setSchemaConfigJson(
			JSON.stringify( { questions: updatedQuestions } )
		);
	}, [ schemaConfig.questions, setSchemaConfigJson ] );

	// Remove a question
	const removeQuestion = useCallback(
		( index: number ) => {
			const updatedQuestions = schemaConfig.questions.filter(
				( _, i ) => i !== index
			);
			setSchemaConfigJson(
				JSON.stringify( { questions: updatedQuestions } )
			);
		},
		[ schemaConfig.questions, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			{ schemaConfig.questions.map( ( item, index ) => (
				<div key={ index } className="meowseo-faq-item">
					<h4>
						{ __( 'Question', 'meowseo' ) } { index + 1 }
					</h4>

					<TextControl
						label={ __( 'Question', 'meowseo' ) }
						value={ item.question }
						onChange={ ( value ) =>
							updateQuestion( index, 'question', value )
						}
					/>

					<TextareaControl
						label={ __( 'Answer', 'meowseo' ) }
						value={ item.answer }
						onChange={ ( value ) =>
							updateQuestion( index, 'answer', value )
						}
						rows={ 4 }
					/>

					{ schemaConfig.questions.length > 1 && (
						<Button
							isDestructive
							variant="secondary"
							onClick={ () => removeQuestion( index ) }
						>
							{ __( 'Remove Question', 'meowseo' ) }
						</Button>
					) }
				</div>
			) ) }

			<Button variant="primary" onClick={ addQuestion }>
				{ __( 'Add Question', 'meowseo' ) }
			</Button>
		</div>
	);
};

export default FAQPageForm;
