/**
 * Schema Sidebar Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useSchemas from '../hooks/useSchemas';
import SchemaEditor from '../components/SchemaEditor';

export default function SchemaSidebar({ postId }) {
	const {
		schemas,
		loading,
		error,
		createSchema,
		updateSchema,
		deleteSchema,
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
		if (!confirm(__('Are you sure you want to delete this schema?', 'meowseo'))) {
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
			<div style={{ padding: '20px', textAlign: 'center' }}>
				<Spinner />
				<p style={{ fontSize: '12px', marginTop: '10px' }}>
					{__('Loading...', 'meowseo')}
				</p>
			</div>
		);
	}

	if (error) {
		return (
			<div style={{ padding: '16px', color: '#dc3232', fontSize: '13px' }}>
				<p>{__('Error loading schemas', 'meowseo')}</p>
			</div>
		);
	}

	if (isCreating || editingSchema) {
		return (
			<div style={{ marginTop: '16px' }}>
				<SchemaEditor
					postId={postId}
					schema={editingSchema}
					onSave={handleSave}
					onCancel={handleCancel}
				/>
			</div>
		);
	}

	return (
		<div className="meowseo-schema-sidebar">
			<div className="meowseo-schema-sidebar__header">
				<p className="meowseo-schema-sidebar__description">
					{__('Add structured data to improve search visibility', 'meowseo')}
				</p>
			</div>

			{schemas.length === 0 ? (
				<div className="meowseo-schema-sidebar__empty">
					<div className="meowseo-schema-sidebar__empty-icon">📋</div>
					<p className="meowseo-schema-sidebar__empty-text">
						{__('No schemas yet', 'meowseo')}
					</p>
				</div>
			) : (
				<div className="meowseo-schema-sidebar-list">
					{schemas.map((schema) => (
						<div key={schema.id} className="meowseo-schema-sidebar-card">
							<div className="meowseo-schema-sidebar-card__header">
								<div>
									<div className="meowseo-schema-sidebar-card__title">
										{schema.data?.name || schema.data?.headline || schema.type}
									</div>
									<div className="meowseo-schema-sidebar-card__type">
										{schema.type}
									</div>
								</div>
							</div>
							<div className="meowseo-schema-sidebar-card__actions">
								<Button
									isSmall
									isSecondary
									onClick={() => handleEdit(schema)}
								>
									{__('Edit', 'meowseo')}
								</Button>
								<Button
									isSmall
									isDestructive
									onClick={() => handleDelete(schema.id)}
								>
									{__('Delete', 'meowseo')}
								</Button>
							</div>
						</div>
					))}
				</div>
			)}

			<Button
				isPrimary
				className="meowseo-schema-sidebar__add-button"
				onClick={handleCreate}
				style={{ marginTop: '16px' }}
			>
				{__('Add Schema', 'meowseo')}
			</Button>
		</div>
	);
}
