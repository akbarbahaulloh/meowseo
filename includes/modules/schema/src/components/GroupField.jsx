/**
 * Group Field Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { __ } from '@wordpress/i18n';
import FieldRenderer from './FieldRenderer';

export default function GroupField({ label, description, fields, value, onChange }) {
	const handleFieldChange = (fieldId, fieldValue) => {
		onChange({
			...value,
			[fieldId]: fieldValue,
		});
	};

	return (
		<div className="meowseo-group">
			{label && <h4 className="meowseo-group__title">{label}</h4>}
			{description && (
				<p className="meowseo-field__description">{description}</p>
			)}
			<div>
				{Object.entries(fields || {}).map(([fieldId, field]) => (
					<FieldRenderer
						key={fieldId}
						fieldId={fieldId}
						field={field}
						value={value?.[fieldId]}
						onChange={(fieldValue) => handleFieldChange(fieldId, fieldValue)}
					/>
				))}
			</div>
		</div>
	);
}
