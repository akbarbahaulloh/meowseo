/**
 * ErrorBoundary Component
 *
 * React Error Boundary to catch and handle errors gracefully.
 * Prevents JavaScript errors from crashing the entire sidebar.
 *
 * Requirements: 17.7 - No user-facing JavaScript errors
 */

import { Component, ReactNode } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

interface ErrorBoundaryProps {
	children: ReactNode;
}

interface ErrorBoundaryState {
	hasError: boolean;
	error: Error | null;
}

/**
 * ErrorBoundary Component
 *
 * Catches errors in child components and displays a fallback UI
 * instead of crashing the entire sidebar.
 *
 * Requirement 17.7: No user-facing JavaScript errors
 * Requirement 17.5: Console error logging for all errors
 */
export class ErrorBoundary extends Component<
	ErrorBoundaryProps,
	ErrorBoundaryState
> {
	constructor( props: ErrorBoundaryProps ) {
		super( props );
		this.state = {
			hasError: false,
			error: null,
		};
	}

	static getDerivedStateFromError( error: Error ): ErrorBoundaryState {
		// Update state so the next render will show the fallback UI
		return {
			hasError: true,
			error,
		};
	}

	componentDidCatch( error: Error, errorInfo: any ): void {
		// Requirement 17.5: Log error to console for debugging
		console.error(
			'MeowSEO Error Boundary caught an error:',
			error,
			errorInfo
		);
	}

	render(): ReactNode {
		if ( this.state.hasError ) {
			// Requirement 17.7: Display user-friendly error message instead of crashing
			return (
				<div className="meowseo-error-boundary">
					<div className="meowseo-error-boundary-content">
						<h3>{ __( 'Something went wrong', 'meowseo' ) }</h3>
						<p>
							{ __(
								'The MeowSEO sidebar encountered an error. Please refresh the page to try again.',
								'meowseo'
							) }
						</p>
						<button
							className="button button-primary"
							onClick={ () => window.location.reload() }
						>
							{ __( 'Refresh Page', 'meowseo' ) }
						</button>
					</div>
				</div>
			);
		}

		return this.props.children;
	}
}
