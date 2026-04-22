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
			testConnectionEndpoint: '/wp-json/meowseo/v1/ai/test-provider',
			statusEndpoint: '/wp-json/meowseo/v1/ai/provider-status',
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

			// Initialize status auto-refresh (34.3)
			this.initStatusAutoRefresh();

			// Initialize character counter (34.4)
			this.initCharacterCounter();
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
			
			if (apiKeyInput && !apiKeyInput.value.trim() && !profileId) {
				this.showTestStatus(profileId || provider, 'error', 'Please enter an API key');
				return;
			}

			const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';
			const selectedProvider = profileItem ? profileItem.querySelector('select[name*="[provider]"]').value : provider;

			// Show loading state
			this.state.isTestingProvider[provider] = true;
			button.disabled = true;
			button.classList.add('meowseo-loading');
			const originalText = button.textContent;
			button.textContent = 'Testing...';

			// Make AJAX request
			fetch(this.config.testConnectionEndpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.state.nonce,
				},
				body: JSON.stringify({
					profile_id: profileId,
					provider: selectedProvider,
					api_key: apiKey,
				}),
			})
				.then((response) => response.json())
				.then((data) => {
					if (data.success && data.data && data.data.valid) {
						this.showTestStatus(profileId || provider, 'success', data.data.message || 'Connection successful');
					} else {
						const errorMsg = data.data?.message || data.message || 'Connection failed';
						this.showTestStatus(profileId || provider, 'error', errorMsg);
					}
				})
				.catch((error) => {
					console.error('Test connection error:', error);
					this.showTestStatus(provider, 'error', 'Request failed');
					this.updateStatusIndicator(provider, 'error');
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
			fetch(this.config.statusEndpoint, {
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
			// Try to get from wp_nonce_field
			let nonce = document.querySelector('input[name="_wpnonce"]');
			if (nonce) {
				return nonce.value;
			}

			// Try to get from wp_localize_script
			if (typeof meowseoAISettings !== 'undefined' && meowseoAISettings.nonce) {
				return meowseoAISettings.nonce;
			}

			// Try to get from REST API nonce
			const restNonce = document.querySelector('meta[name="wp-nonce"]');
			if (restNonce) {
				return restNonce.getAttribute('content');
			}

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
