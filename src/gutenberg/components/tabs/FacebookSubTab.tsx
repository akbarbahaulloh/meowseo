/**
 * FacebookSubTab Component
 *
 * Provides inputs for Facebook Open Graph metadata with preview card.
 * Allows customization of title, description, and image for Facebook sharing.
 *
 * Requirements: 12.2, 12.4, 12.8
 */

import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/block-editor';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

/**
 * FacebookSubTab Component
 *
 * Requirements:
 * - 12.2: Provide inputs for Open Graph title, description, and image
 * - 12.4: Persist to _meowseo_og_title, _meowseo_og_description, _meowseo_og_image_id
 * - 12.8: Display Facebook preview card
 */
const FacebookSubTab: React.FC = () => {
	// Requirement 12.4: Use useEntityPropBinding for Open Graph metadata
	const [ ogTitle, setOgTitle ] = useEntityPropBinding( '_meowseo_og_title' );
	const [ ogDescription, setOgDescription ] = useEntityPropBinding(
		'_meowseo_og_description'
	);
	const [ ogImageId, setOgImageId ] = useEntityPropBinding(
		'_meowseo_og_image_id'
	);

	// Get post title and excerpt as fallbacks
	const { postTitle, postExcerpt } = useSelect( ( select: any ) => {
		try {
			const editorSelect = select( 'core/editor' );
			if ( ! editorSelect ) {
				return {
					postTitle: '',
					postExcerpt: '',
				};
			}
			return {
				postTitle: editorSelect.getEditedPostAttribute( 'title' ) || '',
				postExcerpt:
					editorSelect.getEditedPostAttribute( 'excerpt' ) || '',
			};
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading post title/excerpt:',
				error
			);
			return {
				postTitle: '',
				postExcerpt: '',
			};
		}
	}, [] );

	// Get image URL from image ID
	const imageUrl = useSelect(
		( select: any ) => {
			try {
				if ( ! ogImageId ) {
					return '';
				}
				const media = select( 'core' ).getMedia(
					parseInt( ogImageId )
				);
				return media?.source_url || '';
			} catch ( error ) {
				console.error( 'MeowSEO: Error reading media:', error );
				return '';
			}
		},
		[ ogImageId ]
	);

	// Use fallbacks for preview
	const previewTitle =
		ogTitle || postTitle || __( '(No title set)', 'meowseo' );
	const previewDescription =
		ogDescription || postExcerpt || __( '(No description set)', 'meowseo' );

	return (
		<div className="meowseo-facebook-subtab">
			{ /* Requirement 12.2: Display TextControl for title */ }
			<TextControl
				label={ __( 'Facebook Title', 'meowseo' ) }
				value={ ogTitle }
				onChange={ setOgTitle }
				help={ __(
					'The title that appears when your content is shared on Facebook. Leave empty to use the post title.',
					'meowseo'
				) }
				placeholder={ postTitle }
			/>

			{ /* Requirement 12.2: Display TextareaControl for description */ }
			<TextareaControl
				label={ __( 'Facebook Description', 'meowseo' ) }
				value={ ogDescription }
				onChange={ setOgDescription }
				help={ __(
					'The description that appears when your content is shared on Facebook. Leave empty to use the post excerpt.',
					'meowseo'
				) }
				placeholder={ postExcerpt }
				rows={ 3 }
			/>

			{ /* Requirement 12.2: Display MediaUpload for image */ }
			<div className="meowseo-media-upload">
				<label className="components-base-control__label">
					{ __( 'Facebook Image', 'meowseo' ) }
				</label>
				<MediaUpload
					onSelect={ ( media: any ) =>
						setOgImageId( String( media.id ) )
					}
					allowedTypes={ [ 'image' ] }
					value={ ogImageId ? parseInt( ogImageId ) : undefined }
					render={ ( { open } ) => (
						<div className="meowseo-media-upload-content">
							{ imageUrl && (
								<img
									src={ imageUrl }
									alt={ __(
										'Facebook preview image',
										'meowseo'
									) }
									className="meowseo-media-preview"
								/>
							) }
							<div className="meowseo-media-buttons">
								<Button variant="secondary" onClick={ open }>
									{ imageUrl
										? __( 'Change Image', 'meowseo' )
										: __( 'Select Image', 'meowseo' ) }
								</Button>
								{ imageUrl && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => setOgImageId( '' ) }
									>
										{ __( 'Remove Image', 'meowseo' ) }
									</Button>
								) }
							</div>
						</div>
					) }
				/>
				<p className="components-base-control__help">
					{ __(
						'The image that appears when your content is shared on Facebook. Recommended size: 1200x630 pixels.',
						'meowseo'
					) }
				</p>
			</div>

			{ /* Requirement 12.8: Display Facebook preview card */ }
			<div className="meowseo-preview-card">
				<h3 className="meowseo-preview-card-heading">
					{ __( 'Facebook Preview', 'meowseo' ) }
				</h3>
				<div className="meowseo-facebook-preview">
					{ imageUrl && (
						<div className="meowseo-facebook-preview-image">
							<img src={ imageUrl } alt="" />
						</div>
					) }
					<div className="meowseo-facebook-preview-content">
						<div className="meowseo-facebook-preview-title">
							{ previewTitle }
						</div>
						<div className="meowseo-facebook-preview-description">
							{ previewDescription }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default FacebookSubTab;
