/**
 * Settings App Component
 *
 * React-based settings UI for MeowSEO admin page.
 * Requirement: 2.4
 *
 * @package MeowSEO
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Panel,
	PanelBody,
	PanelRow,
	CheckboxControl,
	TextControl,
	SelectControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';

/**
 * Settings App Component
 *
 * @since 1.0.0
 * @return {JSX.Element} Settings app component.
 */
const SettingsApp = () => {
	const [settings, setSettings] = useState(null);
	const [loading, setLoading] = useState(true);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	// Available modules.
	const availableModules = [
		{ value: 'meta', label: __('SEO Meta', 'meowseo') },
		{ value: 'schema', label: __('Schema / Structured Data', 'meowseo') },
		{ value: 'sitemap', label: __('XML Sitemap', 'meowseo') },
		{ value: 'redirects', label: __('Redirects', 'meowseo') },
		{ value: 'monitor_404', label: __('404 Monitor', 'meowseo') },
		{ value: 'internal_links', label: __('Internal Links', 'meowseo') },
		{ value: 'gsc', label: __('Google Search Console', 'meowseo') },
		{ value: 'social', label: __('Social Meta (Open Graph / Twitter)', 'meowseo') },
	];

	// Add WooCommerce module if active.
	if (window.meowseoAdmin?.isWooCommerceActive) {
		availableModules.push({
			value: 'woocommerce',
			label: __('WooCommerce SEO', 'meowseo'),
		});
	}

	// Load settings on mount.
	useEffect(() => {
		loadSettings();
	}, []);

	/**
	 * Load settings from REST API
	 *
	 * @since 1.0.0
	 */
	const loadSettings = async () => {
		setLoading(true);
		try {
			const response = await apiFetch({
				path: '/meowseo/v1/settings',
				method: 'GET',
			});

			if (response.success) {
				setSettings(response.settings);
			}
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to load settings.', 'meowseo'),
			});
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Save settings to REST API
	 *
	 * @since 1.0.0
	 */
	const saveSettings = async () => {
		setSaving(true);
		setNotice(null);

		try {
			const response = await apiFetch({
				path: '/meowseo/v1/settings',
				method: 'POST',
				data: settings,
				headers: {
					'X-WP-Nonce': window.meowseoAdmin?.nonce,
				},
			});

			if (response.success) {
				setNotice({
					type: 'success',
					message: __('Settings saved successfully.', 'meowseo'),
				});
			}
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings.', 'meowseo'),
			});
		} finally {
			setSaving(false);
		}
	};

	/**
	 * Update a setting value
	 *
	 * @since 1.0.0
	 * @param {string} key   Setting key.
	 * @param {*}      value Setting value.
	 */
	const updateSetting = (key, value) => {
		setSettings({
			...settings,
			[key]: value,
		});
	};

	/**
	 * Toggle module enabled state
	 *
	 * @since 1.0.0
	 * @param {string} moduleId Module ID.
	 */
	const toggleModule = (moduleId) => {
		const enabledModules = settings.enabled_modules || [];
		const isEnabled = enabledModules.includes(moduleId);

		const newEnabledModules = isEnabled
			? enabledModules.filter((id) => id !== moduleId)
			: [...enabledModules, moduleId];

		updateSetting('enabled_modules', newEnabledModules);
	};

	if (loading) {
		return (
			<div style={{ padding: '20px', textAlign: 'center' }}>
				<Spinner />
			</div>
		);
	}

	if (!settings) {
		return (
			<Notice status="error" isDismissible={false}>
				{__('Failed to load settings.', 'meowseo')}
			</Notice>
		);
	}

	const enabledModules = settings.enabled_modules || [];

	return (
		<div className="meowseo-settings">
			{notice && (
				<Notice
					status={notice.type}
					isDismissible={true}
					onRemove={() => setNotice(null)}
				>
					{notice.message}
				</Notice>
			)}

			<Panel>
				<PanelBody
					title={__('Enabled Modules', 'meowseo')}
					initialOpen={true}
				>
					<p>
						{__(
							'Select which SEO features you want to enable. Only enabled modules will be loaded.',
							'meowseo'
						)}
					</p>
					{availableModules.map((module) => (
						<PanelRow key={module.value}>
							<CheckboxControl
								label={module.label}
								checked={enabledModules.includes(module.value)}
								onChange={() => toggleModule(module.value)}
							/>
						</PanelRow>
					))}
				</PanelBody>

				<PanelBody
					title={__('General Settings', 'meowseo')}
					initialOpen={true}
				>
					<PanelRow>
						<SelectControl
							label={__('Title Separator', 'meowseo')}
							value={settings.separator || '|'}
							options={[
								{ value: '|', label: '|' },
								{ value: '-', label: '-' },
								{ value: '–', label: '–' },
								{ value: '—', label: '—' },
								{ value: '·', label: '·' },
								{ value: '•', label: '•' },
							]}
							onChange={(value) => updateSetting('separator', value)}
							help={__(
								'Character used to separate post title from site title.',
								'meowseo'
							)}
						/>
					</PanelRow>

					<PanelRow>
						<CheckboxControl
							label={__('Delete all data on uninstall', 'meowseo')}
							checked={settings.delete_on_uninstall || false}
							onChange={(value) =>
								updateSetting('delete_on_uninstall', value)
							}
							help={__(
								'If enabled, all plugin data (settings, custom tables) will be deleted when the plugin is uninstalled.',
								'meowseo'
							)}
						/>
					</PanelRow>
				</PanelBody>

				{window.meowseoAdmin?.isWooCommerceActive &&
					enabledModules.includes('woocommerce') && (
						<PanelBody
							title={__('WooCommerce Settings', 'meowseo')}
							initialOpen={false}
						>
							<PanelRow>
								<CheckboxControl
									label={__(
										'Exclude out-of-stock products from sitemap',
										'meowseo'
									)}
									checked={
										settings.woocommerce_exclude_out_of_stock || false
									}
									onChange={(value) =>
										updateSetting(
											'woocommerce_exclude_out_of_stock',
											value
										)
									}
								/>
							</PanelRow>
						</PanelBody>
					)}
			</Panel>

			<div style={{ marginTop: '20px' }}>
				<Button
					variant="primary"
					onClick={saveSettings}
					isBusy={saving}
					disabled={saving}
				>
					{saving
						? __('Saving...', 'meowseo')
						: __('Save Settings', 'meowseo')}
				</Button>
			</div>
		</div>
	);
};

export default SettingsApp;
