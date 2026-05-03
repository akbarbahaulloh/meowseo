<?php
/**
 * Article Schema Type
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
 * Article_Schema class.
 */
class Article_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Article';
		$this->label       = __( 'Article', 'meowseo' );
		$this->description = __( 'An article, such as a news article or piece of investigative report.', 'meowseo' );
		$this->icon        = 'media-document';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'headline'    => array(
				'type'        => 'text',
				'label'       => __( 'Headline', 'meowseo' ),
				'description' => __( 'The headline of the article', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description' => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A short description of the article', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'       => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Featured image of the article', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'author'      => array(
				'type'        => 'group',
				'label'       => __( 'Author', 'meowseo' ),
				'description' => __( 'The author of the article', 'meowseo' ),
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
			'publisher'   => array(
				'type'        => 'group',
				'label'       => __( 'Publisher', 'meowseo' ),
				'description' => __( 'The publisher of the article', 'meowseo' ),
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
			'datePublished' => array(
				'type'        => 'text',
				'label'       => __( 'Date Published', 'meowseo' ),
				'description' => __( 'The date the article was published', 'meowseo' ),
				'default'     => '%date(Y-m-d\TH:i:sP)%',
				'required'    => true,
			),
			'dateModified' => array(
				'type'        => 'text',
				'label'       => __( 'Date Modified', 'meowseo' ),
				'description' => __( 'The date the article was last modified', 'meowseo' ),
				'default'     => '%modified(Y-m-d\TH:i:sP)%',
			),
			'articleBody' => array(
				'type'        => 'textarea',
				'label'       => __( 'Article Body', 'meowseo' ),
				'description' => __( 'The actual body of the article', 'meowseo' ),
				'default'     => '%content%',
			),
			'keywords'    => array(
				'type'        => 'text',
				'label'       => __( 'Keywords', 'meowseo' ),
				'description' => __( 'Keywords or tags used to describe the article', 'meowseo' ),
			),
			'articleSection' => array(
				'type'        => 'text',
				'label'       => __( 'Article Section', 'meowseo' ),
				'description' => __( 'The section of the article (e.g., Technology, Sports)', 'meowseo' ),
			),
			'wordCount'   => array(
				'type'        => 'number',
				'label'       => __( 'Word Count', 'meowseo' ),
				'description' => __( 'The number of words in the article', 'meowseo' ),
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
	$article = new Article_Schema();
	$article->register();
} );
