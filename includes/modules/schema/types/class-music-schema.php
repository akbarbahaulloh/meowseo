<?php
/**
 * Music Schema Type
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
 * Music_Schema class.
 */
class Music_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'MusicRecording';
		$this->label       = __( 'Music', 'meowseo' );
		$this->description = __( 'A music recording (track). Perfect for music websites, artist pages, and music reviews.', 'meowseo' );
		$this->icon        = 'format-audio';
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
				'label'       => __( 'Track Name', 'meowseo' ),
				'description' => __( 'The name of the music track', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the music', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Album Art', 'meowseo' ),
				'description' => __( 'Album cover or track artwork', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'byArtist'       => array(
				'type'        => 'group',
				'label'       => __( 'Artist', 'meowseo' ),
				'description' => __( 'The artist who performed the music', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'meowseo' ),
						'default' => 'MusicGroup',
						'options' => array(
							'MusicGroup' => __( 'Music Group', 'meowseo' ),
							'Person'     => __( 'Person', 'meowseo' ),
						),
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Artist Name', 'meowseo' ),
					),
				),
			),
			'inAlbum'        => array(
				'type'        => 'group',
				'label'       => __( 'Album', 'meowseo' ),
				'description' => __( 'The album this track is from', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'MusicAlbum',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Album Name', 'meowseo' ),
					),
				),
			),
			'duration'       => array(
				'type'        => 'text',
				'label'       => __( 'Duration', 'meowseo' ),
				'description' => __( 'Duration in ISO 8601 format (e.g., PT3M45S for 3 minutes 45 seconds)', 'meowseo' ),
				'placeholder' => 'PT3M45S',
			),
			'isrcCode'       => array(
				'type'        => 'text',
				'label'       => __( 'ISRC Code', 'meowseo' ),
				'description' => __( 'International Standard Recording Code', 'meowseo' ),
			),
			'recordingOf'    => array(
				'type'        => 'group',
				'label'       => __( 'Composition', 'meowseo' ),
				'description' => __( 'The composition that was recorded', 'meowseo' ),
				'fields'      => array(
					'@type'    => array(
						'type'    => 'hidden',
						'default' => 'MusicComposition',
					),
					'name'     => array(
						'type'  => 'text',
						'label' => __( 'Composition Name', 'meowseo' ),
					),
					'composer' => array(
						'type'   => 'group',
						'label'  => __( 'Composer', 'meowseo' ),
						'fields' => array(
							'@type' => array(
								'type'    => 'hidden',
								'default' => 'Person',
							),
							'name'  => array(
								'type'  => 'text',
								'label' => __( 'Composer Name', 'meowseo' ),
							),
						),
					),
				),
			),
			'datePublished'  => array(
				'type'        => 'date',
				'label'       => __( 'Release Date', 'meowseo' ),
				'description' => __( 'The date the track was released', 'meowseo' ),
			),
			'genre'          => array(
				'type'        => 'text',
				'label'       => __( 'Genre', 'meowseo' ),
				'description' => __( 'The genre of the music', 'meowseo' ),
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

		$defaults['byArtist'] = array(
			'@type' => 'MusicGroup',
			'name'  => '',
		);

		$defaults['inAlbum'] = array(
			'@type' => 'MusicAlbum',
			'name'  => '',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$music = new Music_Schema();
	$music->register();
} );
