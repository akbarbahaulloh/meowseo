/**
 * Jest setup file for MeowSEO JavaScript tests.
 *
 * Configures testing environment and global mocks.
 */

import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
	i18n: {
		__: (text) => text,
		_x: (text) => text,
		_n: (single, plural, number) => (number === 1 ? single : plural),
		sprintf: (format, ...args) => format,
	},
	element: require('@wordpress/element'),
	components: require('@wordpress/components'),
	data: require('@wordpress/data'),
	apiFetch: jest.fn(),
};

// Mock window.meowseo object
global.meowseo = {
	restUrl: 'http://example.org/wp-json/meowseo/v1/',
	nonce: 'test-nonce',
	ajaxUrl: 'http://example.org/wp-admin/admin-ajax.php',
	pluginUrl: 'http://example.org/wp-content/plugins/meowseo/',
	version: '1.0.0-test',
};

// Suppress console warnings in tests
const originalWarn = console.warn;
const originalError = console.error;

beforeAll(() => {
	console.warn = jest.fn((...args) => {
		// Only show warnings that are not React warnings
		if (!args[0]?.includes?.('Warning: ReactDOM.render')) {
			originalWarn(...args);
		}
	});
	
	console.error = jest.fn((...args) => {
		// Only show errors that are not React errors
		if (!args[0]?.includes?.('Warning: ReactDOM.render')) {
			originalError(...args);
		}
	});
});

afterAll(() => {
	console.warn = originalWarn;
	console.error = originalError;
});
