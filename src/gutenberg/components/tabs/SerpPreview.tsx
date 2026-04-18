/**
 * SerpPreview Component
 *
 * Displays a preview of how the content will appear in search engine results.
 * Supports desktop and mobile preview modes with appropriate truncation rules.
 * Updates are debounced by 800ms to prevent excessive re-renders.
 *
 * Optimized with React.memo and useCallback for performance.
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 16.7, 16.8
 */

import {
	useState,
	useEffect,
	memo,
	useCallback,
	useMemo,
} from '@wordpress/element';
import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

type PreviewMode = 'desktop' | 'mobile';

/**
 * Truncate text to specified length with ellipsis
 * @param text
 * @param maxLength
 */
const truncateText = ( text: string, maxLength: number ): string => {
	if ( text.length <= maxLength ) {
		return text;
	}
	return text.substring( 0, maxLength ) + '...';
};

/**
 * SerpPreview Component
 *
 * Requirements:
 * - 10.1: Display SEO title, meta description, and URL
 * - 10.2: Support desktop and mobile preview modes
 * - 10.3: Implement 800ms debounce for updates
 * - 10.4: Truncate title at 60 chars (desktop)
 * - 10.5: Truncate description at 160 chars (desktop)
 * - 10.6: Update preview when SEO title or description changes
 * - 10.7: Update display format when preview mode changes
 * - 16.7: Use React.memo for pure components
 * - 16.8: Use useCallback for event handlers
 */
const SerpPreview: React.FC = memo( () => {
	const [ mode, setMode ] = useState< PreviewMode >( 'desktop' );
	const [ debouncedTitle, setDebouncedTitle ] = useState( '' );
	const [ debouncedDescription, setDebouncedDescription ] = useState( '' );

	// Get SEO title and description from postmeta
	const [ seoTitle ] = useEntityPropBinding( '_meowseo_title' );
	const [ seoDescription ] = useEntityPropBinding( '_meowseo_description' );

	// Get permalink from core/editor via store
	const permalink = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return '';
			}
			return editorSelect.getPermalink() || '';
		} catch ( error ) {
			console.error( 'MeowSEO: Error reading permalink:', error );
			return '';
		}
	}, [] );

	// Get post title as fallback
	const postTitle = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return '';
			}
			return editorSelect.getEditedPostAttribute( 'title' ) || '';
		} catch ( error ) {
			console.error( 'MeowSEO: Error reading post title:', error );
			return '';
		}
	}, [] );

	// Requirement 10.3: Implement 800ms debounce for updates
	useEffect( () => {
		const timeoutId = setTimeout( () => {
			setDebouncedTitle( seoTitle || postTitle );
		}, 800 );

		return () => clearTimeout( timeoutId );
	}, [ seoTitle, postTitle ] );

	useEffect( () => {
		const timeoutId = setTimeout( () => {
			setDebouncedDescription( seoDescription );
		}, 800 );

		return () => clearTimeout( timeoutId );
	}, [ seoDescription ] );

	// Requirement 16.8: Use useCallback for event handlers
	const handleModeChange = useCallback( ( newMode: PreviewMode ) => {
		setMode( newMode );
	}, [] );

	// Memoize truncated values to prevent recalculation on every render
	const displayTitle = useMemo( () => {
		return mode === 'desktop'
			? truncateText( debouncedTitle, 60 )
			: truncateText( debouncedTitle, 78 ); // Mobile allows slightly more
	}, [ mode, debouncedTitle ] );

	const displayDescription = useMemo( () => {
		return mode === 'desktop'
			? truncateText( debouncedDescription, 160 )
			: truncateText( debouncedDescription, 120 ); // Mobile shows less
	}, [ mode, debouncedDescription ] );

	// Format URL for display (remove protocol)
	const displayUrl = useMemo( () => {
		return permalink.replace( /^https?:\/\//, '' );
	}, [ permalink ] );

	return (
		<div className="meowseo-serp-preview">
			<div className="meowseo-serp-preview-header">
				<label className="meowseo-serp-preview-label">
					{ __( 'Search Preview', 'meowseo' ) }
				</label>
				{ /* Requirement 10.2: Support desktop and mobile preview modes */ }
				<ButtonGroup>
					<Button
						variant={ mode === 'desktop' ? 'primary' : 'secondary' }
						onClick={ () => handleModeChange( 'desktop' ) }
						size="small"
					>
						{ __( 'Desktop', 'meowseo' ) }
					</Button>
					<Button
						variant={ mode === 'mobile' ? 'primary' : 'secondary' }
						onClick={ () => handleModeChange( 'mobile' ) }
						size="small"
					>
						{ __( 'Mobile', 'meowseo' ) }
					</Button>
				</ButtonGroup>
			</div>

			{ /* Requirement 10.1: Display SEO title, meta description, and URL */ }
			<div
				className={ `meowseo-serp-preview-content meowseo-serp-preview-${ mode }` }
			>
				<div className="meowseo-serp-url">{ displayUrl }</div>
				<div className="meowseo-serp-title">
					{ displayTitle || __( '(No title set)', 'meowseo' ) }
				</div>
				<div className="meowseo-serp-description">
					{ displayDescription ||
						__( '(No description set)', 'meowseo' ) }
				</div>
			</div>
		</div>
	);
} );

export default SerpPreview;
