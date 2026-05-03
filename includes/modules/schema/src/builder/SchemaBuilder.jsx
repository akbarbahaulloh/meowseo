/**
 * Schema Builder Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import SchemaList from '../components/SchemaList';
import SchemaEditor from '../components/SchemaEditor';
import useSchemas from '../hooks/useSchemas';

export default function SchemaBuilder({ postId }) {
	const {
		schemas,
		loading,
		error,
		createSchema,
		updateSchema,
		deleteSchema,
		reload,
	} = useSchemas(postId);

	const [editingSchema, setEditingSchema] = useState(null);
	const [isCreating, setIsCreating] = useState(false);

	const handleCreate = () => {
		setIsCreating(true);
		setEditingSchema(null);
	};

	const handleEdit = (schema) => {
		setEditingSchema(schema);
		setIsCreating(false);
	};

	const handleSave = async (schemaData) => {
		try {
			if (editingSchema) {
				await updateSchema(editingSchema.id, schemaData);
			} else {
				await createSchema(schemaData);
			}
			setEditingSchema(null);
			setIsCreating(false);
		} catch (err) {
			console.error('Error saving schema:', err);
			throw err;
		}
	};

	const handleDelete = async (schemaId) => {
		if (!confirm(window.meowseoSchema?.i18n?.confirmDelete || 'Are you sure?')) {
			return;
		}

		try {
			await deleteSchema(schemaId);
		} catch (err) {
			console.error('Error deleting schema:', err);
		}
	};

	const handleCancel = () => {
		setEditingSchema(null);
		setIsCreating(false);
	};

	if (loading) {
		return (
			<div style={{ padding: '40px', textAlign: 'center' }}>
				<Spinner />
				<p>{__('Loading schemas...', 'meowseo')}</p>
			</div>
		);
	}

	if (error) {
		return (
			<div style={{ padding: '20px', color: '#dc3232' }}>
				<p>{__('Error loading schemas:', 'meowseo')} {error}</p>
				<Button isSecondary onClick={reload}>
					{__('Retry', 'meowseo')}
				</Button>
			</div>
		);
	}

	if (isCreating || editingSchema) {
		return (
			<SchemaEditor
				postId={postId}
				schema={editingSchema}
				onSave={handleSave}
				onCancel={handleCancel}
			/>
		);
	}

	return (
		<div>
			<div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
				<h3 style={{ margin: 0 }}>{__('Schemas', 'meowseo')}</h3>
				<Button isPrimary onClick={handleCreate}>
					{__('Add Schema', 'meowseo')}
				</Button>
			</div>

			<SchemaList
				schemas={schemas}
				onEdit={handleEdit}
				onDelete={handleDelete}
				postId={postId}
			/>
		</div>
	);
}
