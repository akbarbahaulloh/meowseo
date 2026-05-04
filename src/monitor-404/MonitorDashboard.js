/**
 * 404 Monitor Dashboard Component
 *
 * @package MeowSEO
 */

import { useState, useEffect } from '@wordpress/element';
import { 
	Button, 
	Placeholder, 
	Spinner, 
	ExternalLink,
	Modal,
	TextControl,
	SelectControl,
	Notice
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function MonitorDashboard() {
	const [logs, setLogs] = useState([]);
	const [pagination, setPagination] = useState({ page: 1, per_page: 50, total: 0, total_pages: 1 });
	const [loading, setLoading] = useState(true);
	const [isRedirectModalOpen, setIsRedirectModalOpen] = useState(false);
	const [activeLog, setActiveLog] = useState(null);
	const [redirectData, setRedirectData] = useState({ target_url: '', type: '301' });
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		fetchLogs();
	}, [pagination.page]);

	const fetchLogs = async () => {
		try {
			setLoading(true);
			const response = await apiFetch({ 
				path: `/meowseo/v1/404-log?page=${pagination.page}&per_page=${pagination.per_page}` 
			});
			setLogs(response.entries || []);
			setPagination(response.pagination);
		} catch (err) {
			console.error('Error fetching logs:', err);
			setNotice({ type: 'error', message: __('Failed to fetch 404 logs.', 'meowseo') });
		} finally {
			setLoading(false);
		}
	};

	const handleDelete = async (id) => {
		if (!confirm(__('Are you sure you want to delete this entry?', 'meowseo'))) return;
		
		try {
			await apiFetch({
				path: `/meowseo/v1/404-log/${id}`,
				method: 'DELETE',
			});
			setLogs(logs.filter(log => log.id !== id));
			setNotice({ type: 'success', message: __('Entry deleted successfully.', 'meowseo') });
		} catch (err) {
			setNotice({ type: 'error', message: __('Failed to delete entry.', 'meowseo') });
		}
	};

	const handleIgnore = async (log) => {
		if (!confirm(__('Are you sure you want to ignore this URL?', 'meowseo'))) return;

		try {
			await apiFetch({
				path: `/meowseo/v1/404-log/ignore`,
				method: 'POST',
				data: { url: log.url, entry_id: log.id }
			});
			setLogs(logs.filter(l => l.id !== log.id));
			setNotice({ type: 'success', message: __('URL added to ignore list.', 'meowseo') });
		} catch (err) {
			setNotice({ type: 'error', message: __('Failed to ignore URL.', 'meowseo') });
		}
	};

	const handleClearAll = async () => {
		if (!confirm(__('Are you sure you want to clear ALL 404 logs? This cannot be undone.', 'meowseo'))) return;

		try {
			await apiFetch({
				path: `/meowseo/v1/404-log/clear-all`,
				method: 'POST',
			});
			setLogs([]);
			setPagination({ ...pagination, total: 0, total_pages: 1 });
			setNotice({ type: 'success', message: __('All logs cleared.', 'meowseo') });
		} catch (err) {
			setNotice({ type: 'error', message: __('Failed to clear logs.', 'meowseo') });
		}
	};

	const openRedirectModal = (log) => {
		setActiveLog(log);
		setRedirectData({ target_url: '', type: '301' });
		setIsRedirectModalOpen(true);
	};

	const handleCreateRedirect = async () => {
		if (!redirectData.target_url) return;

		try {
			setIsSaving(true);
			// We'll use the redirects REST API if available, or a specific helper.
			// For now, let's assume we use the legacy AJAX via a new REST wrapper or directly.
			// Actually, let's create a specific REST endpoint for this in Monitor_404_REST.
			
			const response = await apiFetch({
				path: `/meowseo/v1/redirects`, // Assuming redirects module has this
				method: 'POST',
				data: {
					source_url: activeLog.url,
					target_url: redirectData.target_url,
					redirect_type: parseInt(redirectData.type),
					is_active: 1
				}
			});

			if (response.success) {
				// Delete from 404 log
				await apiFetch({ path: `/meowseo/v1/404-log/${activeLog.id}`, method: 'DELETE' });
				setLogs(logs.filter(l => l.id !== activeLog.id));
				setIsRedirectModalOpen(false);
				setNotice({ type: 'success', message: __('Redirect created and 404 log removed.', 'meowseo') });
			}
		} catch (err) {
			setNotice({ type: 'error', message: __('Failed to create redirect. Make sure the redirects module is active.', 'meowseo') });
		} finally {
			setIsSaving(false);
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
					<h2 className="meowseo-dashboard__title">{__('404 Monitor', 'meowseo')}</h2>
					<p className="meowseo-dashboard__subtitle">{__('Track and fix broken links on your site.', 'meowseo')}</p>
				</div>
				<div className="meowseo-dashboard__actions">
					<Button 
						isSecondary 
						onClick={handleClearAll}
						disabled={logs.length === 0}
					>
						{__('Clear All Logs', 'meowseo')}
					</Button>
					<Button 
						isPrimary 
						onClick={fetchLogs}
						icon="update"
					>
						{__('Refresh', 'meowseo')}
					</Button>
				</div>
			</header>

			<div className="meowseo-card meowseo-log-card">
				{loading ? (
					<div className="meowseo-loader">
						<Spinner />
						<p>{__('Loading logs...', 'meowseo')}</p>
					</div>
				) : logs.length === 0 ? (
					<Placeholder
						icon="search"
						label={__('No 404 Errors Found', 'meowseo')}
						instructions={__('Great job! No one has hit a 404 page recently.', 'meowseo')}
					/>
				) : (
					<div className="meowseo-table-container">
						<table className="meowseo-table">
							<thead>
								<tr>
									<th>{__('URL', 'meowseo')}</th>
									<th style={{ width: '80px' }}>{__('Hits', 'meowseo')}</th>
									<th style={{ width: '150px' }}>{__('Last Seen', 'meowseo')}</th>
									<th style={{ width: '200px' }}>{__('Actions', 'meowseo')}</th>
								</tr>
							</thead>
							<tbody>
								{logs.map(log => (
									<tr key={log.id}>
										<td className="meowseo-td-url">
											<div className="meowseo-url-text">{log.url}</div>
											{log.referrer && (
												<div className="meowseo-url-referrer">
													<span>{__('Referrer:', 'meowseo')}</span> {log.referrer}
												</div>
											)}
										</td>
										<td>
											<span className="meowseo-badge">{log.hit_count}</span>
										</td>
										<td>{log.last_seen}</td>
										<td className="meowseo-td-actions">
											<Button 
												isSmall 
												isPrimary 
												onClick={() => openRedirectModal(log)}
											>
												{__('Fix', 'meowseo')}
											</Button>
											<Button 
												isSmall 
												isSecondary 
												onClick={() => handleIgnore(log)}
											>
												{__('Ignore', 'meowseo')}
											</Button>
											<Button 
												isSmall 
												isDestructive
												icon="trash"
												onClick={() => handleDelete(log.id)}
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

			{isRedirectModalOpen && (
				<Modal 
					title={__('Create Redirect', 'meowseo')}
					onRequestClose={() => setIsRedirectModalOpen(false)}
					className="meowseo-modal"
				>
					<div className="meowseo-modal-content">
						<p>
							<strong>{__('Source URL:', 'meowseo')}</strong><br />
							<code>{activeLog?.url}</code>
						</p>
						
						<TextControl
							label={__('Target URL', 'meowseo')}
							value={redirectData.target_url}
							onChange={(val) => setRedirectData({ ...redirectData, target_url: val })}
							placeholder="/new-destination/"
							help={__('Enter the relative or absolute URL where users should be redirected.', 'meowseo')}
						/>

						<SelectControl
							label={__('Redirect Type', 'meowseo')}
							value={redirectData.type}
							options={[
								{ label: __('301 Permanent', 'meowseo'), value: '301' },
								{ label: __('302 Temporary', 'meowseo'), value: '302' },
								{ label: __('307 Temporary', 'meowseo'), value: '307' },
							]}
							onChange={(val) => setRedirectData({ ...redirectData, type: val })}
						/>

						<div className="meowseo-modal-footer">
							<Button isSecondary onClick={() => setIsRedirectModalOpen(false)}>
								{__('Cancel', 'meowseo')}
							</Button>
							<Button 
								isPrimary 
								onClick={handleCreateRedirect}
								disabled={isSaving || !redirectData.target_url}
							>
								{isSaving ? __('Creating...', 'meowseo') : __('Create Redirect', 'meowseo')}
							</Button>
						</div>
					</div>
				</Modal>
			)}
		</div>
	);
}
