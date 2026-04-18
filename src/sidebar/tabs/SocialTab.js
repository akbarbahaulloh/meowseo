/**
 * Social Tab Component
 *
 * Open Graph and Twitter Card overrides.
 * Reads from meowseo/data store and persists via useEntityProp.
 *
 * @package
 * @since 1.0.0
 */

import { TextControl, TextareaControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Social Tab Component
 */
export default function SocialTab() {
	const { updateMeta } = useDispatch( 'meowseo/data' );

	// Get current post type
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// Use useEntityProp for postmeta persistence
	const [ socialTitle, setSocialTitle ] = useEntityProp(
		'postType',
		postType,
		'meta',
		'meowseo_social_title'
	);
	const [ socialDescription, setSocialDescription ] = useEntityProp(
		'postType',
		postType,
		'meta',
		'meowseo_social_description'
	);
	const [ socialImageId, setSocialImageId ] = useEntityProp(
		'postType',
		postType,
		'meta',
		'meowseo_social_image_id'
	);

	// Get social image URL
	const socialImageUrl = useSelect(
		( select ) => {
			if ( ! socialImageId ) {
				return null;
			}
			const media = select( 'core' ).getMedia( socialImageId );
			return media?.source_url || null;
		},
		[ socialImageId ]
	);

	/**
	 * Handle field change
	 *
	 * @param {string}   field  Field name
	 * @param {*}        value  Field value
	 * @param {Function} setter useEntityProp setter
	 */
	const handleChange = ( field, value, setter ) => {
		updateMeta( field, value );
		setter( value );
	};

	/**
	 * Handle image select
	 *
	 * @param {Object} media Media object
	 */
	const handleImageSelect = ( media ) => {
		handleChange( 'socialImageId', media.id, setSocialImageId );
	};

	/**
	 * Handle image remove
	 */
	const handleImageRemove = () => {
		handleChange( 'socialImageId', 0, setSocialImageId );
	};

	return (
		<div className="meowseo-social-tab">
			<h3>{ __( 'Open Graph & Twitter Card', 'meowseo' ) }</h3>
			<p className="description">
				{ __(
					'Customize how your content appears when shared on social media.',
					'meowseo'
				) }
			</p>

			<TextControl
				label={ __( 'Social Title', 'meowseo' ) }
				value={ socialTitle || '' }
				onChange={ ( value ) =>
					handleChange( 'socialTitle', value, setSocialTitle )
				}
				help={ __( 'Leave empty to use SEO title', 'meowseo' ) }
			/>

			<TextareaControl
				label={ __( 'Social Description', 'meowseo' ) }
				value={ socialDescription || '' }
				onChange={ ( value ) =>
					handleChange(
						'socialDescription',
						value,
						setSocialDescription
					)
				}
				help={ __( 'Leave empty to use meta description', 'meowseo' ) }
				rows={ 3 }
			/>

			<div className="meowseo-social-image">
				<label className="components-base-control__label">
					{ __( 'Social Image', 'meowseo' ) }
				</label>
				<p className="description">
					{ __( 'Leave empty to use featured image', 'meowseo' ) }
				</p>

				{ socialImageUrl && (
					<div className="meowseo-social-image-preview">
						<img
							src={ socialImageUrl }
							alt={ __( 'Social preview', 'meowseo' ) }
						/>
						<Button
							isDestructive
							isSmall
							onClick={ handleImageRemove }
						>
							{ __( 'Remove', 'meowseo' ) }
						</Button>
					</div>
				) }

				<MediaUploadCheck>
					<MediaUpload
						onSelect={ handleImageSelect }
						allowedTypes={ [ 'image' ] }
						value={ socialImageId }
						render={ ( { open } ) => (
							<Button onClick={ open } variant="secondary">
								{ socialImageId
									? __( 'Change Image', 'meowseo' )
									: __( 'Select Image', 'meowseo' ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
			</div>
		</div>
	);
}
