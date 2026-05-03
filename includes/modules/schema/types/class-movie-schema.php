<?php
/**
 * Movie Schema Type
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
 * Movie_Schema class.
 */
class Movie_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Movie';
		$this->label       = __( 'Movie', 'meowseo' );
		$this->description = __( 'A movie or film. Perfect for movie reviews, cinema websites, and entertainment blogs.', 'meowseo' );
		$this->icon        = 'format-video';
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
				'label'       => __( 'Movie Title', 'meowseo' ),
				'description' => __( 'The title of the movie', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the movie', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Movie Poster', 'meowseo' ),
				'description' => __( 'Poster image of the movie', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'director'       => array(
				'type'        => 'group',
				'label'       => __( 'Director', 'meowseo' ),
				'description' => __( 'The director of the movie', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'Person',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Director Name', 'meowseo' ),
					),
				),
			),
			'actor'          => array(
				'type'        => 'repeater',
				'label'       => __( 'Actors', 'meowseo' ),
				'description' => __( 'Main actors in the movie', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'Person',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Actor Name', 'meowseo' ),
					),
				),
			),
			'dateCreated'    => array(
				'type'        => 'date',
				'label'       => __( 'Release Date', 'meowseo' ),
				'description' => __( 'The date the movie was released', 'meowseo' ),
			),
			'duration'       => array(
				'type'        => 'text',
				'label'       => __( 'Duration', 'meowseo' ),
				'description' => __( 'Duration in ISO 8601 format (e.g., PT2H30M for 2 hours 30 minutes)', 'meowseo' ),
				'placeholder' => 'PT2H30M',
			),
			'genre'          => array(
				'type'        => 'text',
				'label'       => __( 'Genre', 'meowseo' ),
				'description' => __( 'The genre of the movie (e.g., Action, Drama, Comedy)', 'meowseo' ),
			),
			'contentRating'  => array(
				'type'        => 'text',
				'label'       => __( 'Content Rating', 'meowseo' ),
				'description' => __( 'The official rating (e.g., PG-13, R, G)', 'meowseo' ),
			),
			'aggregateRating' => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating of the movie', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'AggregateRating',
					),
					'ratingValue' => array(
						'type'  => 'number',
						'label' => __( 'Rating Value', 'meowseo' ),
						'step'  => 0.1,
					),
					'bestRating'  => array(
						'type'    => 'number',
						'label'   => __( 'Best Rating', 'meowseo' ),
						'default' => 10,
					),
					'ratingCount' => array(
						'type'  => 'number',
						'label' => __( 'Rating Count', 'meowseo' ),
					),
				),
			),
			'trailer'        => array(
				'type'        => 'group',
				'label'       => __( 'Trailer', 'meowseo' ),
				'description' => __( 'Movie trailer video', 'meowseo' ),
				'fields'      => array(
					'@type'     => array(
						'type'    => 'hidden',
						'default' => 'VideoObject',
					),
					'name'      => array(
						'type'  => 'text',
						'label' => __( 'Trailer Title', 'meowseo' ),
					),
					'embedUrl'  => array(
						'type'  => 'url',
						'label' => __( 'Trailer URL', 'meowseo' ),
					),
					'thumbnail' => array(
						'type'  => 'image',
						'label' => __( 'Trailer Thumbnail', 'meowseo' ),
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

		$defaults['director'] = array(
			'@type' => 'Person',
			'name'  => '',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$movie = new Movie_Schema();
	$movie->register();
} );
