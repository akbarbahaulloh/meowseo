/**
 * TwitterSubTab Component
 *
 * Provides inputs for Twitter Card metadata with preview card.
 * Allows customization of title, description, and image for Twitter sharing.
 * Includes toggle to use Open Graph data for Twitter.
 *
 * Requirements: 12.3, 12.5, 12.6, 12.7, 12.8
 */

import {
	TextControl,
	TextareaControl,
	Button,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/block-editor';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import { useSelect } from '@wordpress/data';

/**
 * TwitterSubTab Component
 *
 * Requirements:
 * - 12.3: Provide inputs for Twitter title, description, and image
 * - 12.5: Persist to _meowseo_twitter_title, _meowseo_twitter_description, _meowseo_twitter_image_id, _meowseo_use_og_for_twitter
 * - 12.6: Display "Use Open Graph for Twitter" toggle
 * - 12.7: Disable Twitter-specific inputs when toggle is enabled
 * - 12.8: Display Twitter preview card
 */
const TwitterSubTab: React.FC = () => {
	// Requirement 12.5: Use useEntityPropBinding for Twitter metadata
	const [ twitterTitle, setTwitterTitle ] = useEntityPropBinding(
		'_meowseo_twitter_title'
	);
	const [ twitterDescription, setTwitterDescription ] = useEntityPropBinding(
		'_meowseo_twitter_description'
	);
	const [ twitterImageId, setTwitterImageId ] = useEntityPropBinding(
		'_meowseo_twitter_image_id'
	);
	const [ useOgForTwitter, setUseOgForTwitter ] = useEntityPropBinding(
		'_meowseo_use_og_for_twitter'
	);

	// Get Open Graph values for fallback when toggle is enabled
	const [ ogTitle ] = useEntityPropBinding( '_meowseo_og_title' );
	const [ ogDescription ] = useEntityPropBinding( '_meowseo_og_description' );
	const [ ogImageId ] = useEntityPropBinding( '_meowseo_og_image_id' );

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

	// Determine if toggle is enabled (check for '1' or 'true')
	const isUsingOg = useOgForTwitter === '1' || useOgForTwitter === 'true';

	// Determine which values to use based on toggle
	const effectiveTitle = isUsingOg ? ogTitle || twitterTitle : twitterTitle;
	const effectiveDescription = isUsingOg
		? ogDescription || twitterDescription
		: twitterDescription;
	const effectiveImageId = isUsingOg
		? ogImageId || twitterImageId
		: twitterImageId;

	// Get image URL from image ID
	const imageUrl = useSelect(
		( select: any ) => {
			try {
				if ( ! effectiveImageId ) {
					return '';
				}
				const media = select( 'core' ).getMedia(
					parseInt( effectiveImageId )
				);
				return media?.source_url || '';
			} catch ( error ) {
				console.error( 'MeowSEO: Error reading media:', error );
				return '';
			}
		},
		[ effectiveImageId ]
	);

	// Use fallbacks for preview
	const previewTitle =
		effectiveTitle || postTitle || __( '(No title set)', 'meowseo' );
	const previewDescription =
		effectiveDescription ||
		postExcerpt ||
		__( '(No description set)', 'meowseo' );

	return (
		<div className="meowseo-twitter-subtab">
			{ /* Requirement 12.6: Display "Use Open Graph for Twitter" toggle */ }
			<ToggleControl
				label={ __( 'Use Open Graph for Twitter', 'meowseo' ) }
				help={ __(
					'When enabled, Twitter will use the same title, description, and image as Facebook (Open Graph).',
					'meowseo'
				) }
				checked={ isUsingOg }
				onChange={ ( checked ) =>
					setUseOgForTwitter( checked ? '1' : '' )
				}
			/>

			{ /* Requirement 12.3: Display TextControl for title */ }
			{ /* Requirement 12.7: Disable Twitter-specific inputs when toggle is enabled */ }
			<TextControl
				label={ __( 'Twitter Title', 'meowseo' ) }
				value={ twitterTitle }
				onChange={ setTwitterTitle }
				help={ __(
					'The title that appears when your content is shared on Twitter. Leave empty to use the post title.',
					'meowseo'
				) }
				placeholder={ postTitle }
				disabled={ isUsingOg }
			/>

			{ /* Requirement 12.3: Display TextareaControl for description */ }
			{ /* Requirement 12.7: Disable Twitter-specific inputs when toggle is enabled */ }
			<TextareaControl
				label={ __( 'Twitter Description', 'meowseo' ) }
				value={ twitterDescription }
				onChange={ setTwitterDescription }
				help={ __(
					'The description that appears when your content is shared on Twitter. Leave empty to use the post excerpt.',
					'meowseo'
				) }
				placeholder={ postExcerpt }
				rows={ 3 }
				disabled={ isUsingOg }
			/>

			{ /* Requirement 12.3: Display MediaUpload for image */ }
			{ /* Requirement 12.7: Disable Twitter-specific inputs when toggle is enabled */ }
			<div className="meowseo-media-upload">
				<label className="components-base-control__label">
					{ __( 'Twitter Image', 'meowseo' ) }
				</label>
				<MediaUpload
					onSelect={ ( media: any ) =>
						setTwitterImageId( String( media.id ) )
					}
					allowedTypes={ [ 'image' ] }
					value={
						twitterImageId ? parseInt( twitterImageId ) : undefined
					}
					render={ ( { open } ) => (
						<div className="meowseo-media-upload-content">
							{ ! isUsingOg && imageUrl && (
								<img
									src={ imageUrl }
									alt={ __(
										'Twitter preview image',
										'meowseo'
									) }
									className="meowseo-media-preview"
								/>
							) }
							{ isUsingOg && imageUrl && (
								<img
									src={ imageUrl }
									alt={ __(
										'Twitter preview image (from Open Graph)',
										'meowseo'
									) }
									className="meowseo-media-preview"
									style={ { opacity: 0.6 } }
								/>
							) }
							<div className="meowseo-media-buttons">
								<Button
									variant="secondary"
									onClick={ open }
									disabled={ isUsingOg }
								>
									{ imageUrl
										? __( 'Change Image', 'meowseo' )
										: __( 'Select Image', 'meowseo' ) }
								</Button>
								{ imageUrl && ! isUsingOg && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () =>
											setTwitterImageId( '' )
										}
									>
										{ __( 'Remove Image', 'meowseo' ) }
									</Button>
								) }
							</div>
						</div>
					) }
				/>
				<p className="components-base-control__help">
					{ isUsingOg
						? __(
								'Using Open Graph image. Disable the toggle above to set a Twitter-specific image.',
								'meowseo'
						  )
						: __(
								'The image that appears when your content is shared on Twitter. Recommended size: 1200x675 pixels.',
								'meowseo'
						  ) }
				</p>
			</div>

			{ /* Requirement 12.8: Display Twitter preview card */ }
			<div className="meowseo-preview-card">
				<h3 className="meowseo-preview-card-heading">
					{ __( 'Twitter Preview', 'meowseo' ) }
				</h3>
				<div className="meowseo-twitter-preview">
					{ imageUrl && (
						<div className="meowseo-twitter-preview-image">
							<img src={ imageUrl } alt="" />
						</div>
					) }
					<div className="meowseo-twitter-preview-content">
						<div className="meowseo-twitter-preview-title">
							{ previewTitle }
						</div>
						<div className="meowseo-twitter-preview-description">
							{ previewDescription }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default TwitterSubTab;
