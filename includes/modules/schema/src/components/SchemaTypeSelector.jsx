/**
 * Schema Type Selector Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function SchemaTypeSelector({ onSelect }) {
	const [types, setTypes] = useState([]);
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		loadTypes();
	}, []);

	async function loadTypes() {
		try {
			const result = await apiFetch({
				path: '/meowseo/v1/schema-types',
			});
			setTypes(result.data || []);
		} catch (err) {
			console.error('Error loading types:', err);
		} finally {
			setLoading(false);
		}
	}

	const getIcon = (icon) => {
		const iconMap = {
			'media-document': '📄',
			'products': '🛍️',
			'carrot': '🥕',
			'calendar-alt': '📅',
			'store': '🏪',
			'editor-help': '❓',
			'list-view': '📋',
			'star-filled': '⭐',
			'video-alt3': '🎥',
			'welcome-learn-more': '🎓',
		};
		return iconMap[icon] || '📋';
	};

	if (loading) {
		return (
			<div style={{ padding: '40px', textAlign: 'center' }}>
				<Spinner />
				<p>{__('Loading schema types...', 'meowseo')}</p>
			</div>
		);
	}

	return (
		<div>
			<p style={{ marginBottom: '20px', color: '#666' }}>
				{__('Select a schema type to get started:', 'meowseo')}
			</p>
			<div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '15px' }}>
				{types.map((type) => (
					<button
						key={type.id}
						onClick={() => onSelect(type.type)}
						style={{
							padding: '20px',
							border: '1px solid #ddd',
							borderRadius: '4px',
							background: '#fff',
							cursor: 'pointer',
							textAlign: 'left',
							transition: 'all 0.2s ease',
						}}
						onMouseEnter={(e) => {
							e.currentTarget.style.borderColor = '#0073aa';
							e.currentTarget.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
						}}
						onMouseLeave={(e) => {
							e.currentTarget.style.borderColor = '#ddd';
							e.currentTarget.style.boxShadow = 'none';
						}}
					>
						<div style={{ fontSize: '32px', marginBottom: '10px' }}>
							{getIcon(type.icon)}
						</div>
						<h4 style={{ margin: '0 0 5px', fontSize: '14px', fontWeight: '600' }}>
							{type.label}
						</h4>
						<p style={{ margin: 0, fontSize: '12px', color: '#666', lineHeight: '1.4' }}>
							{type.description}
						</p>
					</button>
				))}
			</div>
		</div>
	);
}
