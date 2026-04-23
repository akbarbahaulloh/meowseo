/**
 * AI Settings JavaScript
 *
 * Implements settings page functionality:
 * - Drag-and-drop provider ordering (34.1)
 * - Test connection functionality (34.2)
 * - Provider status auto-refresh (34.3)
 * - Custom instructions character counter (34.4)
 *
 * @package MeowSEO\Modules\AI
 */

(function() {
	'use strict';

	/**
	 * AI Settings Manager
	 *
	 * Handles all settings page interactions and AJAX requests.
	 */
	const AISettings = {
		/**
		 * Configuration
		 */
		config: {
			sortableSelector: '#meowseo-providers-sortable',
			orderInputSelector: '#ai_provider_order',
			testButtonSelector: '.meowseo-test-connection-btn',
			statusIndicatorSelector: '.meowseo-test-status',
			customInstructionsSelector: '#ai_custom_instructions',
			customInstructionsCountSelector: '#ai_custom_instructions_count',
			statusTableSelector: '.meowseo-provider-status-table',
			statusRefreshInterval: 30000, // 30 seconds
			testConnectionEndpoint: 'meowseo/v1/ai/test-provider',
			statusEndpoint: 'meowseo/v1/ai/provider-status',
		},

		/**
		 * Helper to get the correct REST URL
		 * @param {string} path - The REST API path
		 * @return {string} Full URL
		 */
		getRestUrl: function(path) {
			if (typeof meowseoAISettings !== 'undefined' && meowseoAISettings.restUrl) {
				return meowseoAISettings.restUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
			}
			return '/wp-json/' + path.replace(/^\//, '');
		},

		/**
		 * State
		 */
		state: {
			isTestingProvider: {},
			statusRefreshTimer: null,
			nonce: null,
		},

		/**
		 * Initialize the settings manager
		 */
		init: function() {
			// Get nonce from page
			this.state.nonce = this.getNonce();

			// Initialize drag-and-drop (34.1)
			this.initDragAndDrop();

			// Initialize test connection (34.2)
			this.initTestConnection();

			// Initialize API key input handlers
			this.initAPIKeyInputHandlers();

			// Initialize status auto-refresh (34.3)
			this.initStatusAutoRefresh();

			// Initialize character counter (34.4)
			this.initCharacterCounter();
		},

		/**
		 * Initialize API key input handlers
		 *
		 * Clears masked API key when user focuses on the input for editing.
		 */
		initAPIKeyInputHandlers: function() {
			document.addEventListener('focus', (e) => {
				const input = e.target;
				if (!input.matches('input[name*="[api_key]"]')) {
					return;
				}

				// Check if the value is masked (contains ...)
				if (input.value.includes('...')) {
					// Clear the masked value so user can enter a new one
					input.value = '';
					input.setAttribute('data-is-encrypted', '0');
				}
			}, true); // Use capture phase to catch focus events
		},

		/**
		 * 34.1 - Implement drag-and-drop provider ordering
		 *
		 * Uses native HTML5 drag-and-drop API for provider reordering.
		 * Updates hidden field with new order and saves on change.
		 */
		initDragAndDrop: function() {
			const sortable = document.querySelector(this.config.sortableSelector);
			if (!sortable) {
				return;
			}

			let draggedElement = null;

			// Add drag event listeners to all provider items
			const items = sortable.querySelectorAll('.meowseo-provider-item');
			items.forEach((item) => {
				item.draggable = true;

				item.addEventListener('dragstart', (e) => {
					draggedElement = item;
					item.classList.add('meowseo-dragging');
					e.dataTransfer.effectAllowed = 'move';
				});

				item.addEventListener('dragend', (e) => {
					item.classList.remove('meowseo-dragging');
					draggedElement = null;
				});

				item.addEventListener('dragover', (e) => {
					e.preventDefault();
					e.dataTransfer.dropEffect = 'move';

					if (draggedElement && draggedElement !== item) {
						// Visual feedback: insert before or after
						const rect = item.getBoundingClientRect();
						const midpoint = rect.top + rect.height / 2;

						if (e.clientY < midpoint) {
							item.parentNode.insertBefore(draggedElement, item);
						} else {
							item.parentNode.insertBefore(draggedElement, item.nextSibling);
						}
					}
				});

				item.addEventListener('drop', (e) => {
					e.preventDefault();
					e.stopPropagation();
				});
			});

			// Update order when drag ends
			sortable.addEventListener('dragend', () => {
				this.updateProviderOrder();
			});
		},

		/**
		 * Update provider order in hidden field and save
		 *
		 * Requirements: 1.2, 2.9
		 */
		updateProviderOrder: function() {
			const sortable = document.querySelector(this.config.sortableSelector);
			const orderInput = document.querySelector(this.config.orderInputSelector);

			if (!sortable || !orderInput) {
				return;
			}

			const items = sortable.querySelectorAll('.meowseo-provider-item');
			const order = [];
			let priority = 1;

			items.forEach((item) => {
				const provider = item.getAttribute('data-provider');
				if (provider) {
					order.push(provider);
					// Update priority display
					const prioritySpan = item.querySelector('.meowseo-provider-priority');
					if (prioritySpan) {
						prioritySpan.textContent = priority;
					}
					priority++;
				}
			});

			// Update hidden field
			orderInput.value = JSON.stringify(order);

			// Trigger change event for form detection
			orderInput.dispatchEvent(new Event('change', { bubbles: true }));
		},

		/**
		 * 34.2 - Implement test connection functionality
		 *
		 * Makes AJAX request to test-provider endpoint.
		 * Shows loading state during test.
		 * Displays success/error status.
		 * Updates provider status indicator.
		 *
		 * Requirements: 2.4, 2.5, 2.6, 2.7
		 */
		initTestConnection: function() {
			document.addEventListener('click', (e) => {
				const button = e.target.closest(this.config.testButtonSelector);
				if (button) {
					e.preventDefault();
					this.testProviderConnection(button);
				}
			});
		},

		/**
		 * Test connection for a specific provider
		 *
		 * @param {HTMLElement} button - The test button element
		 */
		testProviderConnection: function(button) {
			const profileId = button.getAttribute('data-profile-id');
			const provider = button.getAttribute('data-provider'); // Legacy support
			
			if (this.state.isTestingProvider[profileId || provider]) {
				return;
			}

			// Find API key input in the same profile item
			const profileItem = button.closest('.meowseo-profile-item');
			const apiKeyInput = profileItem ? profileItem.querySelector('input[name*="[api_key]"]') : null;
			
			let apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';
			const selectedProvider = profileItem ? profileItem.querySelector('select[name*="[provider]"]').value : provider;

			// Check if API key is masked (contains ...)
			const isMasked = apiKey.includes('...');

			// If API key is masked and we have a profile ID, don't send the key
			// The backend will fetch it from the saved profile
			if (isMasked && profileId) {
				apiKey = ''; // Let backend fetch from profile
			}

			// If API key is masked but no profile ID, show error
			if (isMasked && !profileId) {
				this.showTestStatus(profileId || provider, 'error', 'Please enter a new API key or save the profile first');
				return;
			}

			// If no API key and no profile ID, show error
			if (!apiKey && !profileId) {
				this.showTestStatus(profileId || provider, 'error', 'Please enter an API key');
				return;
			}

			// Show loading state
			this.state.isTestingProvider[profileId || provider] = true;
			button.disabled = true;
			button.classList.add('meowseo-loading');
			const originalText = button.textContent;
			button.textContent = 'Testing...';

			// Clear previous status
			this.showTestStatus(profileId || provider, 'loading', 'Connecting to ' + selectedProvider + '...');

			// Make AJAX request with proper nonce
			fetch(this.getRestUrl(this.config.testConnectionEndpoint), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.state.nonce,
				},
				credentials: 'same-origin', // Important for cookie-based auth
				body: JSON.stringify({
					profile_id: profileId,
					provider: selectedProvider,
					api_key: apiKey,
				}),
			})
				.then((response) => {
					return response.json().then((data) => {
						if (!response.ok) {
							data._httpStatus = response.status;
						}
						return data;
					}).catch(() => {
						throw new Error(`HTTP ${response.status}: ${response.statusText}`);
					});
				})
				.then((data) => {
					// Display debug log if available
					if (data.data && data.data.debug_log) {
						this.showDebugLog(profileId || provider, data.data.debug_log);
					}

					if (!data._httpStatus && data.success && data.data && data.data.valid) {
						this.showTestStatus(profileId || provider, 'success', data.data.message || 'Connection successful');
						this.updateStatusIndicator(selectedProvider, 'success');
					} else {
						const errorMsg = data.data?.message || data.message || (data._httpStatus ? `HTTP ${data._httpStatus} Error` : 'Connection failed');
						this.showTestStatus(profileId || provider, 'error', errorMsg);
						this.updateStatusIndicator(selectedProvider, 'error');
					}
				})
				.catch((error) => {
					console.error('Test connection error:', error);
					this.showTestStatus(profileId || provider, 'error', 'Request failed: ' + error.message);
					this.updateStatusIndicator(selectedProvider, 'error');
				})
				.finally(() => {
					// Restore button state
					this.state.isTestingProvider[profileId || provider] = false;
					button.disabled = false;
					button.classList.remove('meowseo-loading');
					button.textContent = originalText;
				});
		},

		/**
		 * Show debug log in a modal or expandable section
		 *
		 * @param {string} provider - Provider slug
		 * @param {array} debugLog - Array of debug log lines
		 */
		showDebugLog: function(provider, debugLog) {
			if (!debugLog || !Array.isArray(debugLog)) {
				return;
			}

			// Find or create debug log container
			let debugContainer = document.querySelector(`#debug-log-${provider}`);
			if (!debugContainer) {
				const statusElement = document.querySelector(`#test-status-${provider}`);
				if (!statusElement) {
					return;
				}

				debugContainer = document.createElement('div');
				debugContainer.id = `debug-log-${provider}`;
				debugContainer.className = 'meowseo-debug-log';
				debugContainer.style.cssText = 'margin-top: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; font-size: 12px; white-space: pre-wrap; max-height: 400px; overflow-y: auto;';
				
				// Add toggle button
				const toggleButton = document.createElement('button');
				toggleButton.type = 'button';
				toggleButton.className = 'button button-small';
				toggleButton.textContent = 'Show Debug Log';
				toggleButton.style.cssText = 'margin-top: 5px;';
				toggleButton.onclick = function() {
					if (debugContainer.style.display === 'none') {
						debugContainer.style.display = 'block';
						toggleButton.textContent = 'Hide Debug Log';
					} else {
						debugContainer.style.display = 'none';
						toggleButton.textContent = 'Show Debug Log';
					}
				};

				statusElement.parentNode.insertBefore(toggleButton, statusElement.nextSibling);
				statusElement.parentNode.insertBefore(debugContainer, toggleButton.nextSibling);
				debugContainer.style.display = 'none'; // Hidden by default
			}

			// Format debug log with colors
			const formattedLog = debugLog.map(line => {
				if (line.startsWith('✅') || line.startsWith('✓')) {
					return `<span style="color: #46b450;">${this.escapeHtml(line)}</span>`;
				} else if (line.startsWith('❌')) {
					return `<span style="color: #dc3232; font-weight: bold;">${this.escapeHtml(line)}</span>`;
				} else if (line.startsWith('⚠️')) {
					return `<span style="color: #f56e28;">${this.escapeHtml(line)}</span>`;
				} else if (line.startsWith('→')) {
					return `<span style="color: #0073aa;">${this.escapeHtml(line)}</span>`;
				} else if (line.startsWith('===')) {
					return `<span style="font-weight: bold;">${this.escapeHtml(line)}</span>`;
				} else {
					return this.escapeHtml(line);
				}
			}).join('\n');

			debugContainer.innerHTML = formattedLog;
		},

		/**
		 * Escape HTML to prevent XSS
		 *
		 * @param {string} text - Text to escape
		 * @return {string} Escaped text
		 */
		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
		},

		/**

		 * Show test status message
		 *
		 * @param {string} provider - Provider slug
		 * @param {string} status - Status type: 'success', 'error', 'loading'
		 * @param {string} message - Status message
		 */
		showTestStatus: function(provider, status, message) {
			const statusElement = document.querySelector(
				`#test-status-${provider}`
			);
			if (!statusElement) {
				return;
			}

			statusElement.textContent = message;
			statusElement.className = `meowseo-test-status meowseo-test-status-${status}`;

			// Auto-clear success message after 3 seconds
			if (status === 'success') {
				setTimeout(() => {
					statusElement.textContent = '';
					statusElement.className = 'meowseo-test-status';
				}, 3000);
			}
		},

		/**
		 * Update status indicator for a provider
		 *
		 * @param {string} provider - Provider slug
		 * @param {string} status - Status type
		 */
		updateStatusIndicator: function(provider, status) {
			const row = document.querySelector(
				`.meowseo-provider-status-row[data-provider="${provider}"]`
			);
			if (!row) {
				return;
			}

			const indicator = row.querySelector('.meowseo-status-badge');
			if (indicator) {
				// Remove all status classes
				indicator.className = 'meowseo-status-badge';

				// Add appropriate class
				if (status === 'success' || status === 'active') {
					indicator.classList.add('meowseo-status-active');
					indicator.textContent = 'Active';
				} else if (status === 'error') {
					indicator.classList.add('meowseo-status-no-key');
					indicator.textContent = 'Error';
				}
			}
		},

		/**
		 * 34.3 - Implement provider status auto-refresh
		 *
		 * Polls provider-status endpoint every 30 seconds.
		 * Updates status indicators without page reload.
		 *
		 * Requirements: 3.5
		 */
		initStatusAutoRefresh: function() {
			// Initial refresh
			this.refreshProviderStatus();

			// Set up polling
			this.state.statusRefreshTimer = setInterval(() => {
				this.refreshProviderStatus();
			}, this.config.statusRefreshInterval);
		},

		/**
		 * Refresh provider status from API
		 */
		refreshProviderStatus: function() {
			fetch(this.getRestUrl(this.config.statusEndpoint), {
				method: 'GET',
				headers: {
					'X-WP-Nonce': this.state.nonce,
				},
			})
				.then((response) => response.json())
				.then((data) => {
					if (data.success && data.data) {
						this.updateStatusTable(data.data);
					}
				})
				.catch((error) => {
					console.error('Status refresh error:', error);
				});
		},

		/**
		 * Update status table with fresh data
		 *
		 * @param {Object} statuses - Provider statuses from API
		 */
		updateStatusTable: function(statuses) {
			const table = document.querySelector(this.config.statusTableSelector);
			if (!table) {
				return;
			}

			Object.keys(statuses).forEach((provider) => {
				const status = statuses[provider];
				const row = table.querySelector(
					`.meowseo-provider-status-row[data-provider="${provider}"]`
				);

				if (!row) {
					return;
				}

				// Update status indicator
				const indicator = row.querySelector('.meowseo-status-badge');
				if (indicator) {
					indicator.className = 'meowseo-status-badge';

					if (!status.active) {
						indicator.classList.add('meowseo-status-inactive');
						indicator.textContent = 'Inactive';
					} else if (!status.has_api_key) {
						indicator.classList.add('meowseo-status-no-key');
						indicator.textContent = 'No API Key';
					} else if (status.rate_limited) {
						indicator.classList.add('meowseo-status-rate-limited');
						indicator.textContent = 'Rate Limited';
					} else {
						indicator.classList.add('meowseo-status-active');
						indicator.textContent = 'Active';
					}
				}

				// Update details
				const details = row.querySelector('.meowseo-provider-status-details');
				if (details) {
					let detailText = '';

					if (status.rate_limited && status.rate_limit_remaining > 0) {
						const minutes = Math.ceil(status.rate_limit_remaining / 60);
						detailText = `Rate limit resets in ${minutes} minute(s)`;
					} else if (!status.has_api_key) {
						detailText = 'Configure API key to enable';
					} else if (status.active) {
						const capabilities = [];
						if (status.supports_text) {
							capabilities.push('Text');
						}
						if (status.supports_image) {
							capabilities.push('Image');
						}
						detailText = capabilities.join(', ');
					}

					details.textContent = detailText;
				}
			});
		},

		/**
		 * 34.4 - Implement custom instructions character counter
		 *
		 * Updates count on input.
		 * Shows warning when approaching limit.
		 *
		 * Requirements: 15.2, 15.4
		 */
		initCharacterCounter: function() {
			const textarea = document.querySelector(this.config.customInstructionsSelector);
			const counter = document.querySelector(this.config.customInstructionsCountSelector);

			if (!textarea || !counter) {
				return;
			}

			const maxLength = 500;
			const warningThreshold = 450; // Show warning at 90% capacity

			const updateCount = () => {
				const length = textarea.value.length;
				const remaining = maxLength - length;

				// Update counter text
				counter.textContent = `${length} / ${maxLength} characters`;

				// Show warning when approaching limit
				if (length > warningThreshold) {
					counter.style.color = '#d63638'; // WordPress error red
					counter.style.fontWeight = 'bold';
				} else {
					counter.style.color = '';
					counter.style.fontWeight = '';
				}
			};

			// Update on input
			textarea.addEventListener('input', updateCount);

			// Initial update
			updateCount();
		},

		/**
		 * Get nonce from page
		 *
		 * Looks for nonce in various common locations.
		 *
		 * @return {string} Nonce value
		 */
		getNonce: function() {
			// Try to get from wp_localize_script (highest priority)
			if (typeof meowseoAISettings !== 'undefined' && meowseoAISettings.nonce) {
				return meowseoAISettings.nonce;
			}

			// Try to get from wp_nonce_field
			let nonce = document.querySelector('input[name="_wpnonce"]');
			if (nonce) {
				return nonce.value;
			}

			// Try to get from REST API nonce meta tag
			const restNonce = document.querySelector('meta[name="wp-nonce"]');
			if (restNonce) {
				return restNonce.getAttribute('content');
			}

			// Try to get from wpApiSettings (WordPress REST API)
			if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
				return wpApiSettings.nonce;
			}

			console.warn('MeowSEO AI Settings: No nonce found! Test connection may fail.');
			return '';
		},

		/**
		 * Cleanup
		 */
		destroy: function() {
			if (this.state.statusRefreshTimer) {
				clearInterval(this.state.statusRefreshTimer);
			}
		},
	};

	/**
	 * Initialize when DOM is ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			AISettings.init();
		});
	} else {
		AISettings.init();
	}

	/**
	 * Cleanup on page unload
	 */
	window.addEventListener('beforeunload', () => {
		AISettings.destroy();
	});
})();
