/**
 * Preview Modal Component
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function PreviewModal({ schema, postId, onClose }) {
	const [jsonld, setJsonld] = useState(null);
	const [loading, setLoading] = useState(true);
	const [copied, setCopied] = useState(false);

	useEffect(() => {
		loadPreview();
	}, []);

	async function loadPreview() {
		try {
			const result = await apiFetch({
				path: '/meowseo/v1/schemas/preview',
				method: 'POST',
				data: {
					schema: {
						type: schema.type,
						data: schema.data,
					},
					post_id: postId,
				},
			});
			setJsonld(result.data);
		} catch (err) {
			console.error('Error loading preview:', err);
		} finally {
			setLoading(false);
		}
	}

	const handleCopy = () => {
		const text = JSON.stringify(jsonld, null, 2);
		navigator.clipboard.writeText(text).then(() => {
			setCopied(true);
			setTimeout(() => setCopied(false), 2000);
		});
	};

	return (
		<div className="meowseo-preview-modal" onClick={onClose}>
			<div
				className="meowseo-preview-modal__content"
				onClick={(e) => e.stopPropagation()}
			>
				<div className="meowseo-preview-modal__header">
					<h3 className="meowseo-preview-modal__title">
						{__('JSON-LD Preview', 'meowseo')}
					</h3>
					<div style={{ display: 'flex', gap: '10px' }}>
						{jsonld && (
							<Button isSecondary onClick={handleCopy}>
								{copied ? __('Copied!', 'meowseo') : __('Copy', 'meowseo')}
							</Button>
						)}
						<button
							className="meowseo-preview-modal__close"
							onClick={onClose}
						>
							{__('Close', 'meowseo')}
						</button>
					</div>
				</div>

				<div className="meowseo-preview-modal__body">
					{loading ? (
						<div style={{ padding: '40px', textAlign: 'center' }}>
							<Spinner />
							<p>{__('Loading preview...', 'meowseo')}</p>
						</div>
					) : (
						<pre className="meowseo-preview-modal__code">
							{JSON.stringify(jsonld, null, 2)}
						</pre>
					)}
				</div>
			</div>
		</div>
	);
}
