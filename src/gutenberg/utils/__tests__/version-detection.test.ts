/**
 * Unit Tests: WordPress Version Detection
 * 
 * Tests version detection with different WordPress versions
 * and PluginSidebar import selection.
 * 
 * Requirements: 1.3, 1.4, 1.5
 */

import { detectWordPressVersion } from '../version-detection';

describe('WordPress Version Detection', () => {
	// Store original window.wp
	let originalWp: any;

	beforeEach(() => {
		// Save original window.wp
		originalWp = (global as any).window?.wp;
	});

	afterEach(() => {
		// Restore original window.wp
		if (originalWp !== undefined) {
			(global as any).window = (global as any).window || {};
			(global as any).window.wp = originalWp;
		} else {
			if ((global as any).window) {
				delete (global as any).window.wp;
			}
		}
	});

	describe('detectWordPressVersion', () => {
		it('should return false when window.wp is not available', () => {
			// Remove window.wp
			if ((global as any).window) {
				delete (global as any).window.wp;
			}

			const result = detectWordPressVersion();

			expect(result).toBe(false);
			expect(console).toHaveWarned();
		});

		it('should return false when window is undefined', () => {
			// This test simulates server-side rendering
			const originalWindow = global.window;
			// @ts-ignore
			delete global.window;

			const result = detectWordPressVersion();

			expect(result).toBe(false);
			expect(console).toHaveWarned();

			// Restore window
			// @ts-ignore
			global.window = originalWindow;
		});

		it('should return true when wp.editor.PluginSidebar exists (WP 6.6+)', () => {
			// Mock WordPress 6.6+ environment
			(global as any).window = {
				wp: {
					editor: {
						PluginSidebar: jest.fn(),
					},
					data: {
						select: jest.fn(),
					},
				},
			};

			const result = detectWordPressVersion();

			expect(result).toBe(true);
		});

		it('should return false when wp.editor.PluginSidebar does not exist (WP < 6.6)', () => {
			// Mock WordPress < 6.6 environment
			(global as any).window = {
				wp: {
					editor: {
						// PluginSidebar not present
					},
					data: {
						select: jest.fn(),
					},
				},
			};

			const result = detectWordPressVersion();

			expect(result).toBe(false);
		});

		it('should return true when __experimentalPluginSidebar exists in editor settings', () => {
			// Mock WordPress 6.6+ with experimental flag
			const mockGetEditorSettings = jest.fn().mockReturnValue({
				__experimentalPluginSidebar: true,
			});

			(global as any).window = {
				wp: {
					data: {
						select: jest.fn().mockReturnValue({
							getEditorSettings: mockGetEditorSettings,
						}),
					},
				},
			};

			const result = detectWordPressVersion();

			expect(result).toBe(true);
		});

		it('should handle errors gracefully when accessing core/editor', () => {
			// Mock WordPress with error-throwing select
			(global as any).window = {
				wp: {
					data: {
						select: jest.fn().mockImplementation(() => {
							throw new Error('Test error');
						}),
					},
				},
			};

			// Should not throw, should return false
			const result = detectWordPressVersion();

			expect(result).toBe(false);
			expect(console).toHaveWarned();
		});

		it('should handle missing getEditorSettings method', () => {
			// Mock WordPress with core/editor but no getEditorSettings
			(global as any).window = {
				wp: {
					data: {
						select: jest.fn().mockReturnValue({
							// No getEditorSettings method
						}),
					},
				},
			};

			const result = detectWordPressVersion();

			expect(result).toBe(false);
		});

		it('should handle null editor settings', () => {
			// Mock WordPress with null editor settings
			const mockGetEditorSettings = jest.fn().mockReturnValue(null);

			(global as any).window = {
				wp: {
					data: {
						select: jest.fn().mockReturnValue({
							getEditorSettings: mockGetEditorSettings,
						}),
					},
				},
			};

			const result = detectWordPressVersion();

			expect(result).toBe(false);
		});

		it('should prioritize wp.editor.PluginSidebar check over experimental flag', () => {
			// Mock WordPress 6.6+ with both indicators
			const mockGetEditorSettings = jest.fn().mockReturnValue({
				__experimentalPluginSidebar: false, // Experimental flag is false
			});

			(global as any).window = {
				wp: {
					editor: {
						PluginSidebar: jest.fn(), // But PluginSidebar exists
					},
					data: {
						select: jest.fn().mockReturnValue({
							getEditorSettings: mockGetEditorSettings,
						}),
					},
				},
			};

			const result = detectWordPressVersion();

			// Should return true because wp.editor.PluginSidebar exists
			expect(result).toBe(true);
		});
	});

	describe('isWP66Plus flag', () => {
		it('should be a boolean value', () => {
			// Re-import to get the computed flag
			const { isWP66Plus } = require('../version-detection');

			expect(typeof isWP66Plus).toBe('boolean');
		});
	});
});
