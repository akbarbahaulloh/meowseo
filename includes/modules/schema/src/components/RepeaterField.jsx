/**
 * Repeater Field Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldRenderer from './FieldRenderer';

export default function RepeaterField({ label, description, fields, value, onChange }) {
	const items = Array.isArray(value) ? value : [];

	const handleAdd = () => {
		const newItem = {};
		// Initialize with default values
		Object.entries(fields || {}).forEach(([fieldId, field]) => {
			newItem[fieldId] = field.default || '';
		});
		onChange([...items, newItem]);
	};

	const handleRemove = (index) => {
		onChange(items.filter((_, i) => i !== index));
	};

	const handleItemChange = (index, fieldId, fieldValue) => {
		const newItems = [...items];
		newItems[index] = {
			...newItems[index],
			[fieldId]: fieldValue,
		};
		onChange(newItems);
	};

	return (
		<div className="meowseo-repeater">
			{label && (
				<label className="meowseo-field__label">{label}</label>
			)}
			{description && (
				<p className="meowseo-field__description">{description}</p>
			)}

			{items.map((item, index) => (
				<div key={index} className="meowseo-repeater__item">
					<div className="meowseo-repeater__item-header">
						<span className="meowseo-repeater__item-title">
							{__('Item', 'meowseo')} {index + 1}
						</span>
						<button
							className="meowseo-repeater__item-remove"
							onClick={() => handleRemove(index)}
						>
							{__('Remove', 'meowseo')}
						</button>
					</div>
					<div>
						{Object.entries(fields || {}).map(([fieldId, field]) => (
							<FieldRenderer
								key={fieldId}
								fieldId={fieldId}
								field={field}
								value={item[fieldId]}
								onChange={(fieldValue) =>
									handleItemChange(index, fieldId, fieldValue)
								}
							/>
						))}
					</div>
				</div>
			))}

			<button className="meowseo-repeater__add" onClick={handleAdd}>
				+ {__('Add Item', 'meowseo')}
			</button>
		</div>
	);
}
