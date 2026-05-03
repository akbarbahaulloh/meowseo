<?php
/**
 * Recipe Schema Type
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
 * Recipe_Schema class.
 */
class Recipe_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'Recipe';
		$this->label       = __( 'Recipe', 'meowseo' );
		$this->description = __( 'A recipe with ingredients and instructions.', 'meowseo' );
		$this->icon        = 'carrot';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'              => array(
				'type'        => 'text',
				'label'       => __( 'Recipe Name', 'meowseo' ),
				'description' => __( 'The name of the recipe', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description'       => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A short description of the recipe', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'             => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image of the finished dish', 'meowseo' ),
				'default'     => '%featured_image%',
				'required'    => true,
			),
			'author'            => array(
				'type'        => 'group',
				'label'       => __( 'Author', 'meowseo' ),
				'description' => __( 'The author of the recipe', 'meowseo' ),
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
				),
			),
			'datePublished'     => array(
				'type'        => 'text',
				'label'       => __( 'Date Published', 'meowseo' ),
				'description' => __( 'The date the recipe was published', 'meowseo' ),
				'default'     => '%date(Y-m-d)%',
			),
			'prepTime'          => array(
				'type'        => 'text',
				'label'       => __( 'Prep Time', 'meowseo' ),
				'description' => __( 'Preparation time in ISO 8601 format (e.g., PT30M for 30 minutes)', 'meowseo' ),
				'placeholder' => 'PT30M',
			),
			'cookTime'          => array(
				'type'        => 'text',
				'label'       => __( 'Cook Time', 'meowseo' ),
				'description' => __( 'Cooking time in ISO 8601 format (e.g., PT1H for 1 hour)', 'meowseo' ),
				'placeholder' => 'PT1H',
			),
			'totalTime'         => array(
				'type'        => 'text',
				'label'       => __( 'Total Time', 'meowseo' ),
				'description' => __( 'Total time in ISO 8601 format (e.g., PT1H30M for 1 hour 30 minutes)', 'meowseo' ),
				'placeholder' => 'PT1H30M',
			),
			'recipeYield'       => array(
				'type'        => 'text',
				'label'       => __( 'Recipe Yield', 'meowseo' ),
				'description' => __( 'The quantity produced by the recipe (e.g., "4 servings")', 'meowseo' ),
				'placeholder' => '4 servings',
			),
			'recipeCategory'    => array(
				'type'        => 'text',
				'label'       => __( 'Recipe Category', 'meowseo' ),
				'description' => __( 'The category of the recipe (e.g., "Dessert", "Main Course")', 'meowseo' ),
			),
			'recipeCuisine'     => array(
				'type'        => 'text',
				'label'       => __( 'Recipe Cuisine', 'meowseo' ),
				'description' => __( 'The cuisine of the recipe (e.g., "Italian", "Mexican")', 'meowseo' ),
			),
			'keywords'          => array(
				'type'        => 'text',
				'label'       => __( 'Keywords', 'meowseo' ),
				'description' => __( 'Keywords or tags for the recipe', 'meowseo' ),
			),
			'recipeIngredient'  => array(
				'type'        => 'repeater',
				'label'       => __( 'Ingredients', 'meowseo' ),
				'description' => __( 'List of ingredients', 'meowseo' ),
				'item_type'   => 'text',
				'placeholder' => '2 cups flour',
			),
			'recipeInstructions' => array(
				'type'        => 'repeater',
				'label'       => __( 'Instructions', 'meowseo' ),
				'description' => __( 'Step-by-step instructions', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'HowToStep',
					),
					'name'  => array(
						'type'        => 'text',
						'label'       => __( 'Step Name', 'meowseo' ),
						'placeholder' => 'Mix ingredients',
					),
					'text'  => array(
						'type'        => 'textarea',
						'label'       => __( 'Step Instructions', 'meowseo' ),
						'placeholder' => 'Mix flour and sugar in a bowl...',
					),
					'url'   => array(
						'type'        => 'url',
						'label'       => __( 'Step Image/Video URL', 'meowseo' ),
						'placeholder' => 'https://example.com/step1.jpg',
					),
				),
			),
			'nutrition'         => array(
				'type'        => 'group',
				'label'       => __( 'Nutrition Information', 'meowseo' ),
				'description' => __( 'Nutritional information per serving', 'meowseo' ),
				'fields'      => array(
					'@type'             => array(
						'type'    => 'hidden',
						'default' => 'NutritionInformation',
					),
					'calories'          => array(
						'type'        => 'text',
						'label'       => __( 'Calories', 'meowseo' ),
						'placeholder' => '240 calories',
					),
					'carbohydrateContent' => array(
						'type'        => 'text',
						'label'       => __( 'Carbohydrates', 'meowseo' ),
						'placeholder' => '30 grams',
					),
					'proteinContent'    => array(
						'type'        => 'text',
						'label'       => __( 'Protein', 'meowseo' ),
						'placeholder' => '10 grams',
					),
					'fatContent'        => array(
						'type'        => 'text',
						'label'       => __( 'Fat', 'meowseo' ),
						'placeholder' => '5 grams',
					),
					'saturatedFatContent' => array(
						'type'        => 'text',
						'label'       => __( 'Saturated Fat', 'meowseo' ),
						'placeholder' => '2 grams',
					),
					'cholesterolContent' => array(
						'type'        => 'text',
						'label'       => __( 'Cholesterol', 'meowseo' ),
						'placeholder' => '50 milligrams',
					),
					'sodiumContent'     => array(
						'type'        => 'text',
						'label'       => __( 'Sodium', 'meowseo' ),
						'placeholder' => '200 milligrams',
					),
					'fiberContent'      => array(
						'type'        => 'text',
						'label'       => __( 'Fiber', 'meowseo' ),
						'placeholder' => '3 grams',
					),
					'sugarContent'      => array(
						'type'        => 'text',
						'label'       => __( 'Sugar', 'meowseo' ),
						'placeholder' => '10 grams',
					),
				),
			),
			'aggregateRating'   => array(
				'type'        => 'group',
				'label'       => __( 'Aggregate Rating', 'meowseo' ),
				'description' => __( 'The overall rating based on multiple ratings', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'AggregateRating',
					),
					'ratingValue' => array(
						'type'        => 'number',
						'label'       => __( 'Rating Value', 'meowseo' ),
						'description' => __( 'The rating value (e.g., 4.5)', 'meowseo' ),
						'step'        => 0.1,
					),
					'ratingCount' => array(
						'type'        => 'number',
						'label'       => __( 'Rating Count', 'meowseo' ),
						'description' => __( 'The total number of ratings', 'meowseo' ),
					),
				),
			),
			'video'             => array(
				'type'        => 'group',
				'label'       => __( 'Video', 'meowseo' ),
				'description' => __( 'Video showing how to make the recipe', 'meowseo' ),
				'fields'      => array(
					'@type'       => array(
						'type'    => 'hidden',
						'default' => 'VideoObject',
					),
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Video Name', 'meowseo' ),
					),
					'description' => array(
						'type'  => 'textarea',
						'label' => __( 'Video Description', 'meowseo' ),
					),
					'thumbnailUrl' => array(
						'type'  => 'image',
						'label' => __( 'Thumbnail URL', 'meowseo' ),
					),
					'contentUrl'  => array(
						'type'  => 'url',
						'label' => __( 'Video URL', 'meowseo' ),
					),
					'embedUrl'    => array(
						'type'  => 'url',
						'label' => __( 'Embed URL', 'meowseo' ),
					),
					'uploadDate'  => array(
						'type'    => 'text',
						'label'   => __( 'Upload Date', 'meowseo' ),
						'default' => '%date(Y-m-d)%',
					),
					'duration'    => array(
						'type'        => 'text',
						'label'       => __( 'Duration', 'meowseo' ),
						'placeholder' => 'PT5M',
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

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$recipe = new Recipe_Schema();
	$recipe->register();
} );
