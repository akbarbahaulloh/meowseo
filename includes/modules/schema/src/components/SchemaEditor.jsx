/**
 * Schema Editor Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import SchemaTypeSelector from './SchemaTypeSelector';
import FieldRenderer from './FieldRenderer';

export default function SchemaEditor({ postId, schema, onSave, onCancel }) {
	const [selectedType, setSelectedType] = useState(schema?.type || '');
	const [schemaData, setSchemaData] = useState(schema?.data || {});
	const [fields, setFields] = useState([]);
	const [loading, setLoading] = useState(false);
	const [saving, setSaving] = useState(false);

	useEffect(() => {
		if (selectedType) {
			loadFields();
			if (!schema) {
				loadDefaults();
			}
		}
	}, [selectedType]);

	async function loadFields() {
		try {
			setLoading(true);
			const result = await apiFetch({
				path: `/meowseo/v1/schema-types/${selectedType}/fields`,
			});
			setFields(result.data || {});
		} catch (err) {
			console.error('Error loading fields:', err);
		} finally {
			setLoading(false);
		}
	}

	async function loadDefaults() {
		try {
			const result = await apiFetch({
				path: `/meowseo/v1/schema-types/${selectedType}/defaults?post_id=${postId}`,
			});
			setSchemaData(result.data || {});
		} catch (err) {
			console.error('Error loading defaults:', err);
		}
	}

	const handleFieldChange = (fieldId, value) => {
		setSchemaData({
			...schemaData,
			[fieldId]: value,
		});
	};

	const handleSave = async () => {
		try {
			setSaving(true);
			await onSave({
				type: selectedType,
				data: schemaData,
			});
		} catch (err) {
			alert(__('Error saving schema:', 'meowseo') + ' ' + err.message);
		} finally {
			setSaving(false);
		}
	};

	return (
		<div className="meowseo-schema-editor">
			<div className="meowseo-schema-editor__header">
				<h3 className="meowseo-schema-editor__title">
					{schema ? __('Edit Schema', 'meowseo') : __('Add Schema', 'meowseo')}
				</h3>
				<button
					className="meowseo-schema-editor__close"
					onClick={onCancel}
				>
					{__('Cancel', 'meowseo')}
				</button>
			</div>

			<div className="meowseo-schema-editor__body">
				{!selectedType ? (
					<SchemaTypeSelector onSelect={setSelectedType} />
				) : loading ? (
					<div style={{ padding: '40px', textAlign: 'center' }}>
						<Spinner />
						<p>{__('Loading fields...', 'meowseo')}</p>
					</div>
				) : (
					<div>
						<div className="meowseo-field">
							<label className="meowseo-field__label">
								{__('Schema Type', 'meowseo')}
							</label>
							<input
								type="text"
								className="meowseo-field__input"
								value={selectedType}
								disabled
								style={{ background: '#f0f0f1' }}
							/>
						</div>

						{Object.entries(fields).map(([fieldId, field]) => (
							<FieldRenderer
								key={fieldId}
								fieldId={fieldId}
								field={field}
								value={schemaData[fieldId]}
								onChange={(value) => handleFieldChange(fieldId, value)}
							/>
						))}
					</div>
				)}
			</div>

			{selectedType && !loading && (
				<div className="meowseo-schema-editor__footer">
					<Button isSecondary onClick={onCancel}>
						{__('Cancel', 'meowseo')}
					</Button>
					<Button isPrimary onClick={handleSave} disabled={saving}>
						{saving ? __('Saving...', 'meowseo') : __('Save Schema', 'meowseo')}
					</Button>
				</div>
			)}
		</div>
	);
}
