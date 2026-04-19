<?php
/**
 * Recipe Schema Generator
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema\Generators;

/**
 * Recipe_Schema_Generator class
 *
 * Generates Recipe schema markup with cooking instructions and nutrition information.
 */
class Recipe_Schema_Generator {
	/**
	 * Generate Recipe schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $config  Schema configuration.
	 * @return array Schema data.
	 */
	public function generate( int $post_id, array $config ): array {
		// Implementation will be added in task 2.1
		return array();
	}

	/**
	 * Get required fields
	 *
	 * @return array Required field names.
	 */
	public function get_required_fields(): array {
		return array( 'name', 'description', 'recipeIngredient', 'recipeInstructions' );
	}

	/**
	 * Get optional fields
	 *
	 * @return array Optional field names.
	 */
	public function get_optional_fields(): array {
		return array(
			'prepTime',
			'cookTime',
			'totalTime',
			'recipeYield',
			'recipeCategory',
			'recipeCuisine',
			'nutrition',
			'image',
		);
	}

	/**
	 * Validate configuration
	 *
	 * @param array $config Schema configuration.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_config( array $config ) {
		// Implementation will be added in task 2.1
		return true;
	}
}
