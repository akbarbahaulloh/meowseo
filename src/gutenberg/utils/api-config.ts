/**
 * API Configuration Utility
 *
 * Configures apiFetch with nonce for secure REST API calls.
 *
 * Requirements: 18.1, 18.2, 18.3
 */

import apiFetch from '@wordpress/api-fetch';

/**
 * Configure apiFetch with nonce from localized data
 *
 * Requirements:
 * - 18.1: Include X-WP-Nonce header in all REST API calls
 * - 18.2: Retrieve nonce from meowseoData.nonce localized from PHP
 * - 18.3: REST API endpoints verify nonce before processing
 */
export function configureApiFetch(): void {
	// Get nonce from localized data
	const meowseoData = ( window as any ).meowseoData;

	if ( ! meowseoData || ! meowseoData.nonce ) {
		console.error( 'MeowSEO: Nonce not found in localized data' );
		return;
	}

	// Configure apiFetch to use our nonce
	// This ensures all REST API calls include the X-WP-Nonce header
	apiFetch.use( apiFetch.createNonceMiddleware( meowseoData.nonce ) );

	// Also set the root URL if provided
	if ( meowseoData.restUrl ) {
		apiFetch.use( apiFetch.createRootURLMiddleware( meowseoData.restUrl ) );
	}
}
