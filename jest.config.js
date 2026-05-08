/**
 * Jest configuration for MeowSEO.
 *
 * @see https://jestjs.io/docs/configuration
 */

module.exports = {
	...require('@wordpress/scripts/config/jest-unit.config'),
	
	// Test environment
	testEnvironment: 'jsdom',
	
	// Setup files
	setupFilesAfterEnv: [
		'<rootDir>/tests/js/setup.js',
	],
	
	// Test match patterns
	testMatch: [
		'<rootDir>/tests/js/**/*.test.js',
		'<rootDir>/tests/js/**/*.test.jsx',
		'<rootDir>/tests/js/**/*.test.ts',
		'<rootDir>/tests/js/**/*.test.tsx',
	],
	
	// Module paths
	modulePaths: [
		'<rootDir>/src',
	],
	
	// Module name mapper for CSS and assets
	moduleNameMapper: {
		'\\.(css|less|scss|sass)$': '<rootDir>/tests/js/__mocks__/styleMock.js',
		'\\.(jpg|jpeg|png|gif|svg)$': '<rootDir>/tests/js/__mocks__/fileMock.js',
	},
	
	// Coverage configuration
	collectCoverageFrom: [
		'src/**/*.{js,jsx,ts,tsx}',
		'!src/**/*.d.ts',
		'!src/**/index.js',
		'!src/**/*.stories.{js,jsx,ts,tsx}',
	],
	
	coverageThreshold: {
		global: {
			branches: 50,
			functions: 50,
			lines: 50,
			statements: 50,
		},
	},
	
	// Ignore patterns
	testPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'/build/',
	],
	
	// Verbose output
	verbose: true,
};
