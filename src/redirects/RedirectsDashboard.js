/**
 * Redirects Dashboard Component
 *
 * @package MeowSEO
 */

import { useState, useEffect } from '@wordpress/element';
import { 
	Button, 
	Placeholder, 
	Spinner, 
	Modal,
	TextControl,
	SelectControl,
	ToggleControl,
	Notice,
	Dashicon
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function RedirectsDashboard() {
	const [redirects, setRedirects] = useState([]);
	const [pagination, setPagination] = useState({ page: 1, per_page: 50, total: 0, total_pages: 1 });
	const [loading, setLoading] = useState(true);
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [activeRedirect, setActiveRedirect] = useState(null);
	const [formData, setFormData] = useState({ source_url: '', target_url: '', redirect_type: 301, is_regex: false });
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		fetchRedirects();
	}, [pagination.page]);

	const fetchRedirects = async () => {
		try {
			setLoading(true);
			const response = await apiFetch({ 
				path: `/meowseo/v1/redirects?page=${pagination.page}&per_page=${pagination.per_page}`,
				parse: false // To access headers
			});
			
			const data = await response.json();
			setRedirects(data);
			
			setPagination({
				...pagination,
				total: parseInt(response.headers.get('X-WP-Total')),
				total_pages: parseInt(response.headers.get('X-WP-TotalPages'))
			});
		} catch (err) {
			console.error('Error fetching redirects:', err);
			setNotice({ type: 'error', message: __('Failed to fetch redirects.', 'meowseo') });
		} finally {
			setLoading(false);
		}
	};

	const openModal = (redirect = null) => {
		if (redirect) {
			setActiveRedirect(redirect);
			setFormData({
				source_url: redirect.source_url,
				target_url: redirect.target_url,
				redirect_type: parseInt(redirect.redirect_type),
				is_regex: !!redirect.is_regex
			});
		} else {
			setActiveRedirect(null);
			setFormData({ source_url: '', target_url: '', redirect_type: 301, is_regex: false });
		}
		setIsModalOpen(true);
	};

	const handleSave = async () => {
		if (!formData.source_url || !formData.target_url) return;

		try {
			setIsSaving(true);
			const path = activeRedirect ? `/meowseo/v1/redirects/${activeRedirect.id}` : `/meowseo/v1/redirects`;
			const method = activeRedirect ? 'PUT' : 'POST';

			await apiFetch({
				path,
				method,
				data: formData
			});

			setIsModalOpen(false);
			fetchRedirects();
			setNotice({ 
				type: 'success', 
				message: activeRedirect ? __('Redirect updated successfully.', 'meowseo') : __('Redirect created successfully.', 'meowseo') 
			});
		} catch (err) {
			setNotice({ type: 'error', message: err.message || __('Failed to save redirect.', 'meowseo') });
		} finally {
			setIsSaving(false);
		}
	};

	const handleDelete = async (id) => {
		if (!confirm(__('Are you sure you want to delete this redirect?', 'meowseo'))) return;

		try {
			await apiFetch({
				path: `/meowseo/v1/redirects/${id}`,
				method: 'DELETE',
			});
			fetchRedirects();
			setNotice({ type: 'success', message: __('Redirect deleted successfully.', 'meowseo') });
		} catch (err) {
			setNotice({ type: 'error', message: __('Failed to delete redirect.', 'meowseo') });
		}
	};

	return (
		<div className="meowseo-dashboard">
			{notice && (
				<Notice 
					status={notice.type} 
					onDismiss={() => setNotice(null)}
					className="meowseo-notice"
				>
					{notice.message}
				</Notice>
			)}

			<header className="meowseo-dashboard__header">
				<div className="meowseo-dashboard__title-wrap">
					<h2 className="meowseo-dashboard__title">{__('Redirects', 'meowseo')}</h2>
					<p className="meowseo-dashboard__subtitle">{__('Manage your redirects and fix 404 errors.', 'meowseo')}</p>
				</div>
				<div className="meowseo-dashboard__actions">
					<Button 
						isPrimary 
						onClick={() => openModal()}
						icon="plus"
					>
						{__('Add New Redirect', 'meowseo')}
					</Button>
				</div>
			</header>

			<div className="meowseo-card">
				{loading ? (
					<div className="meowseo-loader">
						<Spinner />
						<p>{__('Loading redirects...', 'meowseo')}</p>
					</div>
				) : redirects.length === 0 ? (
					<Placeholder
						icon="randomize"
						label={__('No Redirects Found', 'meowseo')}
						instructions={__('You haven\'t created any redirects yet.', 'meowseo')}
					>
						<Button isPrimary onClick={() => openModal()}>
							{__('Create Your First Redirect', 'meowseo')}
						</Button>
					</Placeholder>
				) : (
					<div className="meowseo-table-container">
						<table className="meowseo-table">
							<thead>
								<tr>
									<th>{__('Source URL', 'meowseo')}</th>
									<th style={{ width: '40px' }}></th>
									<th>{__('Target URL', 'meowseo')}</th>
									<th style={{ width: '80px' }}>{__('Type', 'meowseo')}</th>
									<th style={{ width: '120px' }}>{__('Actions', 'meowseo')}</th>
								</tr>
							</thead>
							<tbody>
								{redirects.map(redirect => (
									<tr key={redirect.id}>
										<td>
											<div className="meowseo-url-text">
												{redirect.source_url}
												{redirect.is_regex && <span className="meowseo-badge-regex">Regex</span>}
											</div>
										</td>
										<td>
											<Dashicon icon="arrow-right-alt2" />
										</td>
										<td>
											<div className="meowseo-url-text">{redirect.target_url}</div>
										</td>
										<td>
											<span className="meowseo-badge">{redirect.redirect_type}</span>
										</td>
										<td className="meowseo-td-actions">
											<Button 
												isSmall 
												isSecondary 
												icon="edit"
												onClick={() => openModal(redirect)}
											/>
											<Button 
												isSmall 
												isDestructive
												icon="trash"
												onClick={() => handleDelete(redirect.id)}
											/>
										</td>
									</tr>
								))}
							</tbody>
						</table>
					</div>
				)}

				{pagination.total_pages > 1 && (
					<div className="meowseo-pagination">
						<Button 
							disabled={pagination.page <= 1}
							onClick={() => setPagination({ ...pagination, page: pagination.page - 1 })}
						>
							{__('Previous', 'meowseo')}
						</Button>
						<span className="meowseo-pagination-info">
							{sprintf(__('Page %d of %d', 'meowseo'), pagination.page, pagination.total_pages)}
						</span>
						<Button 
							disabled={pagination.page >= pagination.total_pages}
							onClick={() => setPagination({ ...pagination, page: pagination.page + 1 })}
						>
							{__('Next', 'meowseo')}
						</Button>
					</div>
				)}
			</div>

			{isModalOpen && (
				<Modal 
					title={activeRedirect ? __('Edit Redirect', 'meowseo') : __('Add Redirect', 'meowseo')}
					onRequestClose={() => setIsModalOpen(false)}
					className="meowseo-modal"
				>
					<div className="meowseo-modal-content">
						<TextControl
							label={__('Source URL', 'meowseo')}
							value={formData.source_url}
							onChange={(val) => setFormData({ ...formData, source_url: val })}
							placeholder="/old-path/"
							help={__('The URL that should be redirected.', 'meowseo')}
						/>

						<ToggleControl
							label={__('Regex Match', 'meowseo')}
							checked={formData.is_regex}
							onChange={(val) => setFormData({ ...formData, is_regex: val })}
						/>

						<TextControl
							label={__('Target URL', 'meowseo')}
							value={formData.target_url}
							onChange={(val) => setFormData({ ...formData, target_url: val })}
							placeholder="/new-path/ or https://example.com/page/"
							help={__('The destination URL.', 'meowseo')}
						/>

						<SelectControl
							label={__('Redirect Type', 'meowseo')}
							value={formData.redirect_type}
							options={[
								{ label: __('301 Permanent', 'meowseo'), value: 301 },
								{ label: __('302 Temporary', 'meowseo'), value: 302 },
								{ label: __('307 Temporary', 'meowseo'), value: 307 },
								{ label: __('308 Permanent', 'meowseo'), value: 308 },
							]}
							onChange={(val) => setFormData({ ...formData, redirect_type: parseInt(val) })}
						/>

						<div className="meowseo-modal-footer">
							<Button isSecondary onClick={() => setIsModalOpen(false)}>
								{__('Cancel', 'meowseo')}
							</Button>
							<Button 
								isPrimary 
								onClick={handleSave}
								disabled={isSaving || !formData.source_url || !formData.target_url}
							>
								{isSaving ? __('Saving...', 'meowseo') : __('Save Redirect', 'meowseo')}
							</Button>
						</div>
					</div>
				</Modal>
			)}
		</div>
	);
}
