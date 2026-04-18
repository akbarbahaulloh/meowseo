/**
 * CanonicalURLInput Component
 *
 * Provides input for custom canonical URL with display of resolved canonical URL.
 * Uses useEntityPropBinding for automatic persistence to postmeta.
 *
 * Requirements: 14.4, 14.5, 14.6, 15.11
 */

import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * CanonicalURLInput Component
 *
 * Requirements:
 * - 14.4: Display canonical URL input field
 * - 14.5: Persist canonical URL to _meowseo_canonical postmeta
 * - 14.6: Display resolved canonical URL (read-only)
 * - 15.11: Use Entity_Prop for canonical URL persistence
 */
const CanonicalURLInput: React.FC = () => {
	// Bind to postmeta field
	// Requirements: 14.5, 15.11
	const [ canonical, setCanonical ] =
		useEntityPropBinding( '_meowseo_canonical' );

	// Get the post's permalink as the default/resolved canonical URL
	// Requirement: 14.6
	const resolvedCanonical = useSelect(
		( select: any ) => {
			try {
				const editorSelect = select( 'core/editor' );
				if ( ! editorSelect ) {
					return canonical || '';
				}
				const permalink = editorSelect.getPermalink() || '';
				return canonical || permalink;
			} catch ( error ) {
				console.error(
					'MeowSEO: Error reading permalink for canonical URL:',
					error
				);
				return canonical || '';
			}
		},
		[ canonical ]
	);

	return (
		<div className="meowseo-canonical-url">
			<h3>{ __( 'Canonical URL', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Custom Canonical URL', 'meowseo' ) }
				help={ __(
					'Override the default canonical URL for this page. Leave empty to use the default.',
					'meowseo'
				) }
				value={ canonical }
				onChange={ setCanonical }
				type="url"
				placeholder={ __(
					'https://example.com/custom-url',
					'meowseo'
				) }
			/>

			<div className="meowseo-resolved-canonical">
				<label className="components-base-control__label">
					{ __( 'Resolved Canonical URL', 'meowseo' ) }
				</label>
				<div className="meowseo-resolved-canonical-value">
					<code>{ resolvedCanonical }</code>
				</div>
				<p className="components-base-control__help">
					{ __(
						'This is the canonical URL that will be output in the page head.',
						'meowseo'
					) }
				</p>
			</div>
		</div>
	);
};

export default CanonicalURLInput;
