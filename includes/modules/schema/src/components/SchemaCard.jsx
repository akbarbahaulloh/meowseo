/**
 * Schema Card Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import PreviewModal from './PreviewModal';

export default function SchemaCard({ schema, onEdit, onDelete, postId }) {
	const [showPreview, setShowPreview] = useState(false);

	const getIcon = (type) => {
		const icons = {
			Article: '📄',
			Product: '🛍️',
			Recipe: '🥕',
			Event: '📅',
			LocalBusiness: '🏪',
			FAQPage: '❓',
			HowTo: '📋',
			Review: '⭐',
			VideoObject: '🎥',
			Course: '🎓',
		};
		return icons[type] || '📋';
	};

	return (
		<>
			<div className="meowseo-schema-card">
				<div className="meowseo-schema-card__icon">
					{getIcon(schema.type)}
				</div>
				<div className="meowseo-schema-card__content">
					<h4 className="meowseo-schema-card__title">
						{schema.data?.name || schema.data?.headline || schema.type}
					</h4>
					<p className="meowseo-schema-card__type">
						{__('Type:', 'meowseo')} {schema.type}
						{schema.shortcode && (
							<>
								{' | '}
								<code>[meowseo_schema id="{schema.shortcode}"]</code>
							</>
						)}
					</p>
				</div>
				<div className="meowseo-schema-card__actions">
					<button
						className="meowseo-schema-card__action meowseo-schema-card__action--preview"
						onClick={() => setShowPreview(true)}
					>
						{__('Preview', 'meowseo')}
					</button>
					<button
						className="meowseo-schema-card__action meowseo-schema-card__action--edit"
						onClick={onEdit}
					>
						{__('Edit', 'meowseo')}
					</button>
					<button
						className="meowseo-schema-card__action meowseo-schema-card__action--delete"
						onClick={onDelete}
					>
						{__('Delete', 'meowseo')}
					</button>
				</div>
			</div>

			{showPreview && (
				<PreviewModal
					schema={schema}
					postId={postId}
					onClose={() => setShowPreview(false)}
				/>
			)}
		</>
	);
}
