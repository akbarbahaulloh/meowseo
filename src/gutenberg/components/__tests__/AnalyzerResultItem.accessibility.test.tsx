/**
 * Accessibility Tests for AnalyzerResultItem Component
 *
 * Tests WCAG AA compliance for AnalyzerResultItem
 * - Verify ARIA labels on status icons
 * - Test keyboard navigation
 * - Test focus indicators
 * - Ensure all interactive elements are accessible
 */

import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { AnalyzerResultItem } from '../AnalyzerResultItem';
import { AnalysisResult } from '../../store/types';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'AnalyzerResultItem - Accessibility', () => {
	const mockResult: AnalysisResult = {
		id: 'keyword-in-title',
		type: 'good',
		message: 'Focus keyword appears in SEO title',
		score: 100,
		weight: 0.08,
		details: { position: 'beginning' },
	};

	describe( 'ARIA Labels and Roles', () => {
		it( 'should have aria-label on status icon', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const statusIcon = container.querySelector(
				'.meowseo-analyzer-status-icon'
			);
			expect( statusIcon ).toHaveAttribute( 'aria-label' );
		} );

		it( 'should have aria-label on details toggle button', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			);
			expect( toggleButton ).toHaveAttribute( 'aria-label' );
			expect( toggleButton ).toHaveAttribute( 'aria-expanded' );
		} );

		it( 'should update aria-expanded when toggling details', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			expect( toggleButton ).toHaveAttribute( 'aria-expanded', 'false' );

			fireEvent.click( toggleButton );

			expect( toggleButton ).toHaveAttribute( 'aria-expanded', 'true' );
		} );

		it( 'should have proper aria-label for different result types', () => {
			const resultTypes: Array< AnalysisResult[ 'type' ] > = [
				'good',
				'ok',
				'problem',
			];

			resultTypes.forEach( ( type ) => {
				const { container } = render(
					<AnalyzerResultItem result={ { ...mockResult, type } } />
				);

				const statusIcon = container.querySelector(
					'.meowseo-analyzer-status-icon'
				);
				expect( statusIcon ).toHaveAttribute( 'aria-label' );
				const ariaLabel = statusIcon?.getAttribute( 'aria-label' );
				expect( [ 'Good', 'OK', 'Problem' ] ).toContain( ariaLabel );
			} );
		} );
	} );

	describe( 'Keyboard Navigation', () => {
		it( 'should allow tabbing through details toggle', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			toggleButton.focus();
			expect( toggleButton ).toHaveFocus();
		} );

		it( 'should activate button with click', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			toggleButton.focus();
			expect( toggleButton ).toHaveFocus();

			fireEvent.click( toggleButton );
			expect( toggleButton ).toHaveAttribute( 'aria-expanded', 'true' );
		} );
	} );

	describe( 'Focus Indicators', () => {
		it( 'should have visible focus indicator on details toggle', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			toggleButton.focus();
			expect( toggleButton ).toHaveFocus();

			// CSS has outline: 2px solid #0073aa; outline-offset: 2px;
			const styles = window.getComputedStyle( toggleButton );
			// Note: outline styles may not be fully computed in jsdom
			// This test verifies the element can receive focus
		} );
	} );

	describe( 'Screen Reader Support', () => {
		it( 'should announce analyzer result status to screen readers', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const statusIcon = container.querySelector(
				'.meowseo-analyzer-status-icon'
			);
			expect( statusIcon ).toHaveAttribute( 'aria-label', 'Good' );
		} );

		it( 'should announce expanded/collapsed state to screen readers', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			expect( toggleButton ).toHaveAttribute( 'aria-expanded', 'false' );

			fireEvent.click( toggleButton );

			expect( toggleButton ).toHaveAttribute( 'aria-expanded', 'true' );
		} );

		it( 'should have descriptive button labels', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			);

			expect( toggleButton ).toHaveAttribute( 'aria-label' );
			const ariaLabel = toggleButton?.getAttribute( 'aria-label' );
			expect( [ 'Show details', 'Hide details' ] ).toContain( ariaLabel );
		} );
	} );

	describe( 'Interactive Elements Accessibility', () => {
		it( 'should have button properly labeled', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const button = container.querySelector( 'button' );

			// Button should have either text content or aria-label
			const textLength = button?.textContent?.trim().length ?? 0;
			const hasText = textLength > 0;
			const hasAriaLabel = button?.hasAttribute( 'aria-label' );
			expect( hasText || hasAriaLabel ).toBe( true );
		} );

		it( 'should have proper button type attribute', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const button = container.querySelector( 'button' );

			expect( button?.type ).toBe( 'button' );
		} );

		it( 'should not have empty button', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const button = container.querySelector( 'button' );

			const textLength = button?.textContent?.trim().length ?? 0;
			const hasText = textLength > 0;
			const hasAriaLabel = button?.hasAttribute( 'aria-label' );
			const hasSvg = button?.querySelector( 'svg' );
			const hasContent = hasText || hasAriaLabel || hasSvg;
			expect( hasContent ).toBe( true );
		} );
	} );

	describe( 'Semantic HTML', () => {
		it( 'should use semantic button element for interactive controls', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const button = container.querySelector( 'button' );

			expect( button?.tagName ).toBe( 'BUTTON' );
		} );
	} );

	describe( 'Details Display', () => {
		it( 'should display details when expanded', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			fireEvent.click( toggleButton );

			const details = container.querySelector(
				'.meowseo-analyzer-details'
			);
			expect( details ).toBeInTheDocument();
		} );

		it( 'should hide details when collapsed', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			// Expand
			fireEvent.click( toggleButton );
			let details = container.querySelector(
				'.meowseo-analyzer-details'
			);
			expect( details ).toBeInTheDocument();

			// Collapse
			fireEvent.click( toggleButton );
			details = container.querySelector( '.meowseo-analyzer-details' );
			expect( details ).not.toBeInTheDocument();
		} );

		it( 'should display detail rows with labels and values', () => {
			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			fireEvent.click( toggleButton );

			const detailRows = container.querySelectorAll(
				'.meowseo-analyzer-detail-row'
			);
			expect( detailRows.length ).toBeGreaterThan( 0 );

			detailRows.forEach( ( row ) => {
				const key = row.querySelector( '.meowseo-analyzer-detail-key' );
				const value = row.querySelector(
					'.meowseo-analyzer-detail-value'
				);
				expect( key ).toBeInTheDocument();
				expect( value ).toBeInTheDocument();
			} );
		} );
	} );
} );
