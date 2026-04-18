/**
 * Settings App Component
 *
 * React-based settings UI for MeowSEO admin page.
 * Requirement: 2.4
 *
 * @package
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
	const [ settings, setSettings ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Available modules.
	const availableModules = [
		{ value: 'meta', label: __( 'SEO Meta', 'meowseo' ) },
		{ value: 'schema', label: __( 'Schema / Structured Data', 'meowseo' ) },
		{ value: 'sitemap', label: __( 'XML Sitemap', 'meowseo' ) },
		{ value: 'redirects', label: __( 'Redirects', 'meowseo' ) },
		{ value: 'monitor_404', label: __( '404 Monitor', 'meowseo' ) },
		{ value: 'internal_links', label: __( 'Internal Links', 'meowseo' ) },
		{ value: 'gsc', label: __( 'Google Search Console', 'meowseo' ) },
		{
			value: 'social',
			label: __( 'Social Meta (Open Graph / Twitter)', 'meowseo' ),
		},
	];

	// Add WooCommerce module if active.
	if ( window.meowseoAdmin?.isWooCommerceActive ) {
		availableModules.push( {
			value: 'woocommerce',
			label: __( 'WooCommerce SEO', 'meowseo' ),
		} );
	}

	// Load settings on mount.
	useEffect( () => {
		loadSettings();
	}, [] );

	/**
	 * Load settings from REST API
	 *
	 * @since 1.0.0
	 */
	const loadSettings = async () => {
		setLoading( true );
		setNotice( null );
		try {
			const response = await apiFetch( {
				path: '/meowseo/v1/settings',
				method: 'GET',
			} );

			if ( response.success ) {
				setSettings( response.settings );
			} else {
				throw new Error(
					response.message || __( 'Unknown error', 'meowseo' )
				);
			}
		} catch ( error ) {
			console.error( 'MeowSEO: Failed to load settings', error );
			setNotice( {
				type: 'error',
				message:
					error.message ||
					__(
						'Failed to load settings. Please refresh the page and try again.',
						'meowseo'
					),
			} );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Save settings to REST API
	 *
	 * @since 1.0.0
	 */
	const saveSettings = async () => {
		setSaving( true );
		setNotice( null );

		try {
			const response = await apiFetch( {
				path: '/meowseo/v1/settings',
				method: 'POST',
				data: settings,
				headers: {
					'X-WP-Nonce': window.meowseoAdmin?.nonce,
				},
			} );

			if ( response.success ) {
				setNotice( {
					type: 'success',
					message: __( 'Settings saved successfully.', 'meowseo' ),
				} );
			} else {
				throw new Error(
					response.message || __( 'Unknown error', 'meowseo' )
				);
			}
		} catch ( error ) {
			console.error( 'MeowSEO: Failed to save settings', error );
			setNotice( {
				type: 'error',
				message:
					error.message ||
					__(
						'Failed to save settings. Please try again.',
						'meowseo'
					),
			} );
		} finally {
			setSaving( false );
		}
	};

	/**
	 * Update a setting value
	 *
	 * @since 1.0.0
	 * @param {string} key   Setting key.
	 * @param {*}      value Setting value.
	 */
	const updateSetting = ( key, value ) => {
		setSettings( {
			...settings,
			[ key ]: value,
		} );
	};

	/**
	 * Toggle module enabled state
	 *
	 * @since 1.0.0
	 * @param {string} moduleId Module ID.
	 */
	const toggleModule = ( moduleId ) => {
		const enabledModules = settings.enabled_modules || [];
		const isEnabled = enabledModules.includes( moduleId );

		const newEnabledModules = isEnabled
			? enabledModules.filter( ( id ) => id !== moduleId )
			: [ ...enabledModules, moduleId ];

		updateSetting( 'enabled_modules', newEnabledModules );
	};

	if ( loading ) {
		return (
			<div style={ { padding: '20px', textAlign: 'center' } }>
				<Spinner />
			</div>
		);
	}

	if ( ! settings ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Failed to load settings.', 'meowseo' ) }
			</Notice>
		);
	}

	const enabledModules = settings.enabled_modules || [];

	return (
		<div className="meowseo-settings">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible={ true }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<Panel>
				<PanelBody
					title={ __( 'Enabled Modules', 'meowseo' ) }
					initialOpen={ true }
				>
					<p>
						{ __(
							'Select which SEO features you want to enable. Only enabled modules will be loaded.',
							'meowseo'
						) }
					</p>
					{ availableModules.map( ( module ) => (
						<PanelRow key={ module.value }>
							<CheckboxControl
								label={ module.label }
								checked={ enabledModules.includes(
									module.value
								) }
								onChange={ () => toggleModule( module.value ) }
							/>
						</PanelRow>
					) ) }
				</PanelBody>

				<PanelBody
					title={ __( 'General Settings', 'meowseo' ) }
					initialOpen={ true }
				>
					<PanelRow>
						<SelectControl
							label={ __( 'Title Separator', 'meowseo' ) }
							value={ settings.separator || '|' }
							options={ [
								{ value: '|', label: '|' },
								{ value: '-', label: '-' },
								{ value: '–', label: '–' },
								{ value: '—', label: '—' },
								{ value: '·', label: '·' },
								{ value: '•', label: '•' },
							] }
							onChange={ ( value ) =>
								updateSetting( 'separator', value )
							}
							help={ __(
								'Character used to separate post title from site title.',
								'meowseo'
							) }
						/>
					</PanelRow>

					<PanelRow>
						<CheckboxControl
							label={ __(
								'Delete all data on uninstall',
								'meowseo'
							) }
							checked={ settings.delete_on_uninstall || false }
							onChange={ ( value ) =>
								updateSetting( 'delete_on_uninstall', value )
							}
							help={ __(
								'If enabled, all plugin data (settings, custom tables) will be deleted when the plugin is uninstalled.',
								'meowseo'
							) }
						/>
					</PanelRow>
				</PanelBody>

				{ window.meowseoAdmin?.isWooCommerceActive &&
					enabledModules.includes( 'woocommerce' ) && (
						<PanelBody
							title={ __( 'WooCommerce Settings', 'meowseo' ) }
							initialOpen={ false }
						>
							<PanelRow>
								<CheckboxControl
									label={ __(
										'Exclude out-of-stock products from sitemap',
										'meowseo'
									) }
									checked={
										settings.woocommerce_exclude_out_of_stock ||
										false
									}
									onChange={ ( value ) =>
										updateSetting(
											'woocommerce_exclude_out_of_stock',
											value
										)
									}
								/>
							</PanelRow>
						</PanelBody>
					) }

				{ enabledModules.includes( 'schema' ) && (
					<PanelBody
						title={ __(
							'Schema / Structured Data Settings',
							'meowseo'
						) }
						initialOpen={ false }
					>
						<PanelRow>
							<TextControl
								label={ __( 'Organization Name', 'meowseo' ) }
								value={
									settings.meowseo_schema_organization_name ||
									''
								}
								onChange={ ( value ) =>
									updateSetting(
										'meowseo_schema_organization_name',
										value
									)
								}
								help={ __(
									'Your organization or company name for schema markup.',
									'meowseo'
								) }
							/>
						</PanelRow>

						<PanelRow>
							<TextControl
								label={ __(
									'Organization Logo URL',
									'meowseo'
								) }
								value={
									settings.meowseo_schema_organization_logo ||
									''
								}
								onChange={ ( value ) =>
									updateSetting(
										'meowseo_schema_organization_logo',
										value
									)
								}
								help={ __(
									'URL to your organization logo image.',
									'meowseo'
								) }
							/>
						</PanelRow>

						<PanelRow>
							<div style={ { width: '100%' } }>
								<h4>{ __( 'Social Profiles', 'meowseo' ) }</h4>
								<p className="description">
									{ __(
										'Add your social media profile URLs for schema markup.',
										'meowseo'
									) }
								</p>

								<TextControl
									label={ __( 'Facebook', 'meowseo' ) }
									value={
										settings.meowseo_schema_social_profiles
											?.facebook || ''
									}
									onChange={ ( value ) => {
										const profiles =
											settings.meowseo_schema_social_profiles ||
											{};
										updateSetting(
											'meowseo_schema_social_profiles',
											{
												...profiles,
												facebook: value,
											}
										);
									} }
									placeholder="https://facebook.com/yourpage"
								/>

								<TextControl
									label={ __( 'Twitter', 'meowseo' ) }
									value={
										settings.meowseo_schema_social_profiles
											?.twitter || ''
									}
									onChange={ ( value ) => {
										const profiles =
											settings.meowseo_schema_social_profiles ||
											{};
										updateSetting(
											'meowseo_schema_social_profiles',
											{
												...profiles,
												twitter: value,
											}
										);
									} }
									placeholder="https://twitter.com/yourhandle"
								/>

								<TextControl
									label={ __( 'Instagram', 'meowseo' ) }
									value={
										settings.meowseo_schema_social_profiles
											?.instagram || ''
									}
									onChange={ ( value ) => {
										const profiles =
											settings.meowseo_schema_social_profiles ||
											{};
										updateSetting(
											'meowseo_schema_social_profiles',
											{
												...profiles,
												instagram: value,
											}
										);
									} }
									placeholder="https://instagram.com/yourprofile"
								/>

								<TextControl
									label={ __( 'LinkedIn', 'meowseo' ) }
									value={
										settings.meowseo_schema_social_profiles
											?.linkedin || ''
									}
									onChange={ ( value ) => {
										const profiles =
											settings.meowseo_schema_social_profiles ||
											{};
										updateSetting(
											'meowseo_schema_social_profiles',
											{
												...profiles,
												linkedin: value,
											}
										);
									} }
									placeholder="https://linkedin.com/company/yourcompany"
								/>
							</div>
						</PanelRow>
					</PanelBody>
				) }

				{ enabledModules.includes( 'sitemap' ) && (
					<PanelBody
						title={ __( 'XML Sitemap Settings', 'meowseo' ) }
						initialOpen={ false }
					>
						<PanelRow>
							<CheckboxControl
								label={ __( 'Enable XML Sitemap', 'meowseo' ) }
								checked={
									settings.meowseo_sitemap_enabled !== false
								}
								onChange={ ( value ) =>
									updateSetting(
										'meowseo_sitemap_enabled',
										value
									)
								}
								help={ __(
									'Generate XML sitemaps for search engines.',
									'meowseo'
								) }
							/>
						</PanelRow>

						{ settings.meowseo_sitemap_enabled !== false && (
							<>
								<PanelRow>
									<CheckboxControl
										label={ __(
											'Enable Google News Sitemap',
											'meowseo'
										) }
										checked={
											settings.meowseo_sitemap_news_enabled ||
											false
										}
										onChange={ ( value ) =>
											updateSetting(
												'meowseo_sitemap_news_enabled',
												value
											)
										}
										help={ __(
											'Generate a Google News sitemap for recent posts.',
											'meowseo'
										) }
									/>
								</PanelRow>

								<PanelRow>
									<CheckboxControl
										label={ __(
											'Enable Video Sitemap',
											'meowseo'
										) }
										checked={
											settings.meowseo_sitemap_video_enabled ||
											false
										}
										onChange={ ( value ) =>
											updateSetting(
												'meowseo_sitemap_video_enabled',
												value
											)
										}
										help={ __(
											'Generate a video sitemap for posts with video embeds.',
											'meowseo'
										) }
									/>
								</PanelRow>

								<PanelRow>
									<TextControl
										label={ __(
											'Maximum URLs per Sitemap',
											'meowseo'
										) }
										type="number"
										value={
											settings.meowseo_sitemap_max_urls ||
											1000
										}
										onChange={ ( value ) =>
											updateSetting(
												'meowseo_sitemap_max_urls',
												parseInt( value, 10 )
											)
										}
										help={ __(
											'Maximum number of URLs per sitemap file (default: 1000).',
											'meowseo'
										) }
										min={ 100 }
										max={ 50000 }
									/>
								</PanelRow>

								<PanelRow>
									<TextControl
										label={ __(
											'Cache TTL (seconds)',
											'meowseo'
										) }
										type="number"
										value={
											settings.meowseo_sitemap_cache_ttl ||
											86400
										}
										onChange={ ( value ) =>
											updateSetting(
												'meowseo_sitemap_cache_ttl',
												parseInt( value, 10 )
											)
										}
										help={ __(
											'How long to cache sitemap files (default: 86400 = 24 hours).',
											'meowseo'
										) }
										min={ 3600 }
										max={ 604800 }
									/>
								</PanelRow>

								<PanelRow>
									<div style={ { width: '100%' } }>
										<h4>
											{ __(
												'Post Types in Sitemap',
												'meowseo'
											) }
										</h4>
										<p className="description">
											{ __(
												'Select which post types to include in the sitemap.',
												'meowseo'
											) }
										</p>
										{ [ 'post', 'page' ].map(
											( postType ) => (
												<CheckboxControl
													key={ postType }
													label={
														postType === 'post'
															? __(
																	'Posts',
																	'meowseo'
															  )
															: __(
																	'Pages',
																	'meowseo'
															  )
													}
													checked={ (
														settings.meowseo_sitemap_post_types || [
															'post',
															'page',
														]
													).includes( postType ) }
													onChange={ ( checked ) => {
														const postTypes =
															settings.meowseo_sitemap_post_types || [
																'post',
																'page',
															];
														const newPostTypes =
															checked
																? [
																		...postTypes,
																		postType,
																  ]
																: postTypes.filter(
																		(
																			pt
																		) =>
																			pt !==
																			postType
																  );
														updateSetting(
															'meowseo_sitemap_post_types',
															newPostTypes
														);
													} }
												/>
											)
										) }
									</div>
								</PanelRow>
							</>
						) }
					</PanelBody>
				) }
			</Panel>

			<div style={ { marginTop: '20px' } }>
				<Button
					variant="primary"
					onClick={ saveSettings }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'meowseo' )
						: __( 'Save Settings', 'meowseo' ) }
				</Button>
			</div>
		</div>
	);
};

export default SettingsApp;
