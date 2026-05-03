/**
 * Schema List Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import SchemaCard from './SchemaCard';

export default function SchemaList({ schemas, onEdit, onDelete, postId }) {
	if (!schemas || schemas.length === 0) {
		return (
			<div className="meowseo-schema-list__empty">
				<div className="meowseo-schema-list__empty-icon">📋</div>
				<p>{__('No schemas added yet', 'meowseo')}</p>
				<p style={{ fontSize: '13px', color: '#999' }}>
					{__('Click "Add Schema" to create your first schema', 'meowseo')}
				</p>
			</div>
		);
	}

	return (
		<div className="meowseo-schema-list">
			{schemas.map((schema) => (
				<SchemaCard
					key={schema.id}
					schema={schema}
					onEdit={() => onEdit(schema)}
					onDelete={() => onDelete(schema.id)}
					postId={postId}
				/>
			))}
		</div>
	);
}
