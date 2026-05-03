/**
 * Field Renderer Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { TextControl, TextareaControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import RepeaterField from './RepeaterField';
import GroupField from './GroupField';

export default function FieldRenderer({ fieldId, field, value, onChange }) {
	const { type, label, description, required, options, fields, placeholder } = field;

	const commonProps = {
		label: required ? `${label} *` : label,
		help: description,
		value: value || '',
		onChange,
	};

	switch (type) {
		case 'text':
		case 'url':
		case 'email':
			return (
				<div className="meowseo-field">
					<TextControl
						{...commonProps}
						type={type}
						placeholder={placeholder}
						className="meowseo-field__input"
					/>
				</div>
			);

		case 'textarea':
			return (
				<div className="meowseo-field">
					<TextareaControl
						{...commonProps}
						placeholder={placeholder}
						className="meowseo-field__input meowseo-field__textarea"
						rows={5}
					/>
				</div>
			);

		case 'number':
			return (
				<div className="meowseo-field">
					<TextControl
						{...commonProps}
						type="number"
						step={field.step || 1}
						placeholder={placeholder}
						className="meowseo-field__input"
					/>
				</div>
			);

		case 'select':
			return (
				<div className="meowseo-field">
					<SelectControl
						{...commonProps}
						options={Object.entries(options || {}).map(([val, label]) => ({
							value: val,
							label,
						}))}
						className="meowseo-field__select"
					/>
				</div>
			);

		case 'date':
		case 'datetime':
		case 'time':
			return (
				<div className="meowseo-field">
					<TextControl
						{...commonProps}
						type={type === 'datetime' ? 'datetime-local' : type}
						placeholder={placeholder}
						className="meowseo-field__input"
					/>
				</div>
			);

		case 'image':
			return (
				<div className="meowseo-field">
					<label className="meowseo-field__label">
						{commonProps.label}
					</label>
					<TextControl
						value={value || ''}
						onChange={onChange}
						placeholder={placeholder || __('Enter image URL', 'meowseo')}
						className="meowseo-field__input"
					/>
					{description && (
						<p className="meowseo-field__description">{description}</p>
					)}
				</div>
			);

		case 'group':
			return (
				<GroupField
					label={label}
					description={description}
					fields={fields}
					value={value || {}}
					onChange={onChange}
				/>
			);

		case 'repeater':
			return (
				<RepeaterField
					label={label}
					description={description}
					fields={fields}
					value={value || []}
					onChange={onChange}
				/>
			);

		case 'hidden':
			return null;

		default:
			return (
				<div className="meowseo-field">
					<TextControl
						{...commonProps}
						placeholder={placeholder}
						className="meowseo-field__input"
					/>
				</div>
			);
	}
}
