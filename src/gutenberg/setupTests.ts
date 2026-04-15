import '@testing-library/jest-dom';

// Mock @wordpress/private-apis
jest.mock('@wordpress/private-apis', () => ({
  __dangerousOptInToUnstableAPIsOnlyForCoreModules: jest.fn(() => ({
    lock: jest.fn(),
    unlock: jest.fn(() => ({})),
  })),
}));

// Mock @wordpress/data
jest.mock('@wordpress/data', () => ({
  ...jest.requireActual('@wordpress/data'),
  createRegistrySelector: jest.fn((selector) => selector),
}));
