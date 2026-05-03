<?php
/**
 * Book Schema Type
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
 * Book_Schema class.
 */
class Book_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Book';
		$this->label       = __( 'Book', 'meowseo' );
		$this->description = __( 'A book or publication. Perfect for book reviews, author pages, and bookstores.', 'meowseo' );
		$this->icon        = 'book';
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
				'label'       => __( 'Book Title', 'meowseo' ),
				'description' => __( 'The title of the book', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'    => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the book', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'          => array(
				'type'        => 'image',
				'label'       => __( 'Book Cover', 'meowseo' ),
				'description' => __( 'Cover image of the book', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'author'         => array(
				'type'        => 'group',
				'label'       => __( 'Author', 'meowseo' ),
				'description' => __( 'The author of the book', 'meowseo' ),
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
						'type'  => 'text',
						'label' => __( 'Name', 'meowseo' ),
					),
				),
			),
			'isbn'           => array(
				'type'        => 'text',
				'label'       => __( 'ISBN', 'meowseo' ),
				'description' => __( 'The ISBN of the book', 'meowseo' ),
			),
			'bookFormat'     => array(
				'type'        => 'select',
				'label'       => __( 'Book Format', 'meowseo' ),
				'description' => __( 'The format of the book', 'meowseo' ),
				'options'     => array(
					''                                => __( 'Select Format', 'meowseo' ),
					'https://schema.org/Hardcover'    => __( 'Hardcover', 'meowseo' ),
					'https://schema.org/Paperback'    => __( 'Paperback', 'meowseo' ),
					'https://schema.org/EBook'        => __( 'EBook', 'meowseo' ),
					'https://schema.org/AudiobookFormat' => __( 'Audiobook', 'meowseo' ),
				),
			),
			'numberOfPages'  => array(
				'type'        => 'number',
				'label'       => __( 'Number of Pages', 'meowseo' ),
				'description' => __( 'The number of pages in the book', 'meowseo' ),
			),
			'publisher'      => array(
				'type'        => 'group',
				'label'       => __( 'Publisher', 'meowseo' ),
				'description' => __( 'The publisher of the book', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'Organization',
					),
					'name'  => array(
						'type'  => 'text',
						'label' => __( 'Publisher Name', 'meowseo' ),
					),
				),
			),
			'datePublished'  => array(
				'type'        => 'date',
				'label'       => __( 'Publication Date', 'meowseo' ),
				'description' => __( 'The date the book was published', 'meowseo' ),
			),
			'bookEdition'    => array(
				'type'        => 'text',
				'label'       => __( 'Book Edition', 'meowseo' ),
				'description' => __( 'The edition of the book', 'meowseo' ),
			),
			'inLanguage'     => array(
				'type'        => 'text',
				'label'       => __( 'Language', 'meowseo' ),
				'description' => __( 'The language of the book (e.g., en, id)', 'meowseo' ),
				'default'     => 'en',
			),
			'aggregateRating' => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating of the book', 'meowseo' ),
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
						'default' => 5,
					),
					'ratingCount' => array(
						'type'  => 'number',
						'label' => __( 'Rating Count', 'meowseo' ),
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

		$defaults['author'] = array(
			'@type' => 'Person',
			'name'  => '',
		);

		$defaults['publisher'] = array(
			'@type' => 'Organization',
			'name'  => '',
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$book = new Book_Schema();
	$book->register();
} );
