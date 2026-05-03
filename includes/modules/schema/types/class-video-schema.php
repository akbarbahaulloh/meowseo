<?php
/**
 * Video Schema Type
 *
 * @package MeowSEO
 * @subpackage Modules\Schema\Types
 */

namespace MeowSEO\Modules\Schema\Types;

use MeowSEO\Modules\Schema\Schema_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Video_Schema class.
 */
class Video_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'VideoObject';
		$this->label       = __( 'Video', 'meowseo' );
		$this->description = __( 'A video file. Helps your videos appear in Google Video Search results.', 'meowseo' );
		$this->icon        = 'video-alt3';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'           => array(
				'type'        => 'text',
				'label'       => __( 'Name', 'meowseo' ),
				'description' => __( 'The title of the video', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the video', 'meowseo' ),
				'default'     => '%excerpt%',
				'required'    => true,
			),
			'thumbnailUrl'   => array(
				'type'        => 'image',
				'label'       => __( 'Thumbnail URL', 'meowseo' ),
				'description' => __( 'A URL pointing to the video thumbnail image', 'meowseo' ),
				'default'     => '%featured_image%',
				'required'    => true,
			),
			'uploadDate'     => array(
				'type'        => 'text',
				'label'       => __( 'Upload Date', 'meowseo' ),
				'description' => __( 'The date the video was first published', 'meowseo' ),
				'default'     => '%date(Y-m-d\TH:i:sP)%',
				'required'    => true,
			),
			'duration'       => array(
				'type'        => 'text',
				'label'       => __( 'Duration', 'meowseo' ),
				'description' => __( 'The duration of the video in ISO 8601 format (e.g., PT1H30M for 1 hour 30 minutes)', 'meowseo' ),
				'placeholder' => 'PT1H30M',
			),
			'contentUrl'     => array(
				'type'        => 'url',
				'label'       => __( 'Content URL', 'meowseo' ),
				'description' => __( 'A URL pointing to the actual video media file', 'meowseo' ),
			),
			'embedUrl'       => array(
				'type'        => 'url',
				'label'       => __( 'Embed URL', 'meowseo' ),
				'description' => __( 'A URL pointing to a player for the video', 'meowseo' ),
			),
			'transcript'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Transcript', 'meowseo' ),
				'description' => __( 'The transcript of the video', 'meowseo' ),
			),
			'interactionStatistic' => array(
				'type'        => 'group',
				'label'       => __( 'Interaction Statistics', 'meowseo' ),
				'description' => __( 'Statistics about the video', 'meowseo' ),
				'fields'      => array(
					'@type'                => array(
						'type'    => 'hidden',
						'default' => 'InteractionCounter',
					),
					'interactionType'      => array(
						'type'    => 'hidden',
						'default' => 'https://schema.org/WatchAction',
					),
					'userInteractionCount' => array(
						'type'        => 'number',
						'label'       => __( 'View Count', 'meowseo' ),
						'description' => __( 'The number of times the video has been viewed', 'meowseo' ),
					),
				),
			),
			'author'         => array(
				'type'        => 'group',
				'label'       => __( 'Author', 'meowseo' ),
				'description' => __( 'The creator of the video', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Person',
						'options' => array(
							'Person'       => __( 'Person', 'meowseo' ),
							'Organization' => __( 'Organization', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'    => 'text',
						'label'   => __( 'Name', 'meowseo' ),
						'default' => '%author%',
					),
					'url'   => array(
						'type'    => 'url',
						'label'   => __( 'URL', 'meowseo' ),
						'default' => '%author_url%',
					),
				),
			),
			'publisher'      => array(
				'type'        => 'group',
				'label'       => __( 'Publisher', 'meowseo' ),
				'description' => __( 'The publisher of the video', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'Organization',
						'options' => array(
							'Organization' => __( 'Organization', 'meowseo' ),
							'Person'       => __( 'Person', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'    => 'text',
						'label'   => __( 'Name', 'meowseo' ),
						'default' => '%sitename%',
					),
					'logo'  => array(
						'type'        => 'group',
						'label'       => __( 'Logo', 'meowseo' ),
						'description' => __( 'Logo of the publisher', 'meowseo' ),
						'fields'      => array(
							'@type' => array(
								'type'    => 'hidden',
								'default' => 'ImageObject',
							),
							'url'   => array(
								'type'  => 'image',
								'label' => __( 'Logo URL', 'meowseo' ),
							),
						),
					),
				),
			),
			'hasPart'        => array(
				'type'        => 'repeater',
				'label'       => __( 'Video Segments', 'meowseo' ),
				'description' => __( 'Key moments or chapters in the video', 'meowseo' ),
				'fields'      => array(
					'@type'      => array(
						'type'    => 'hidden',
						'default' => 'Clip',
					),
					'name'       => array(
						'type'        => 'text',
						'label'       => __( 'Segment Name', 'meowseo' ),
						'description' => __( 'The name of this video segment', 'meowseo' ),
					),
					'startOffset' => array(
						'type'        => 'number',
						'label'       => __( 'Start Time (seconds)', 'meowseo' ),
						'description' => __( 'When this segment starts in seconds', 'meowseo' ),
					),
					'endOffset'  => array(
						'type'        => 'number',
						'label'       => __( 'End Time (seconds)', 'meowseo' ),
						'description' => __( 'When this segment ends in seconds', 'meowseo' ),
					),
					'url'        => array(
						'type'        => 'url',
						'label'       => __( 'URL', 'meowseo' ),
						'description' => __( 'URL to this specific segment', 'meowseo' ),
					),
				),
			),
		);
	}

	/**
	 * Get default schema data.
	 *
	 * @param object|null $object Post or term object.
	 * @return array
	 */
	public function get_defaults( $object = null ): array {
		$defaults = parent::get_defaults( $object );

		// Set default author.
		$defaults['author'] = array(
			'@type' => 'Person',
			'name'  => '%author%',
		);

		// Set default publisher.
		$defaults['publisher'] = array(
			'@type' => 'Organization',
			'name'  => '%sitename%',
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => '',
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$video = new Video_Schema();
	$video->register();
} );
