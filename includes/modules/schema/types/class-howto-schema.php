<?php
/**
 * HowTo Schema Type
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
 * HowTo_Schema class.
 */
class HowTo_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'HowTo';
		$this->label       = __( 'HowTo', 'meowseo' );
		$this->description = __( 'Instructions that explain how to achieve a result.', 'meowseo' );
		$this->icon        = 'list-view';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'name'        => array(
				'type'        => 'text',
				'label'       => __( 'Name', 'meowseo' ),
				'description' => __( 'The name of the how-to', 'meowseo' ),
				'default'     => '%title%',
				'required'    => true,
			),
			'description' => array(
				'type'        => 'textarea',
				'label'       => __( 'Description', 'meowseo' ),
				'description' => __( 'A description of the how-to', 'meowseo' ),
				'default'     => '%excerpt%',
			),
			'image'       => array(
				'type'        => 'image',
				'label'       => __( 'Image', 'meowseo' ),
				'description' => __( 'Image of the completed how-to', 'meowseo' ),
				'default'     => '%featured_image%',
			),
			'totalTime'   => array(
				'type'        => 'text',
				'label'       => __( 'Total Time', 'meowseo' ),
				'description' => __( 'Total time required in ISO 8601 format (e.g., PT1H30M)', 'meowseo' ),
				'placeholder' => 'PT1H30M',
			),
			'estimatedCost' => array(
				'type'        => 'group',
				'label'       => __( 'Estimated Cost', 'meowseo' ),
				'description' => __( 'The estimated cost of the supplies', 'meowseo' ),
				'fields'      => array(
					'@type'    => array(
						'type'    => 'hidden',
						'default' => 'MonetaryAmount',
					),
					'currency' => array(
						'type'        => 'text',
						'label'       => __( 'Currency', 'meowseo' ),
						'placeholder' => 'USD',
						'default'     => 'USD',
					),
					'value'    => array(
						'type'        => 'number',
						'label'       => __( 'Value', 'meowseo' ),
						'placeholder' => '50',
					),
				),
			),
			'supply'      => array(
				'type'        => 'repeater',
				'label'       => __( 'Supplies', 'meowseo' ),
				'description' => __( 'Supplies needed for the how-to', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'HowToSupply',
					),
					'name'  => array(
						'type'        => 'text',
						'label'       => __( 'Supply Name', 'meowseo' ),
						'placeholder' => 'Hammer',
					),
				),
			),
			'tool'        => array(
				'type'        => 'repeater',
				'label'       => __( 'Tools', 'meowseo' ),
				'description' => __( 'Tools needed for the how-to', 'meowseo' ),
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'HowToTool',
					),
					'name'  => array(
						'type'        => 'text',
						'label'       => __( 'Tool Name', 'meowseo' ),
						'placeholder' => 'Screwdriver',
					),
				),
			),
			'step'        => array(
				'type'        => 'repeater',
				'label'       => __( 'Steps', 'meowseo' ),
				'description' => __( 'Step-by-step instructions', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type' => array(
						'type'    => 'hidden',
						'default' => 'HowToStep',
					),
					'name'  => array(
						'type'        => 'text',
						'label'       => __( 'Step Name', 'meowseo' ),
						'description' => __( 'Short name for the step', 'meowseo' ),
						'placeholder' => 'Prepare materials',
					),
					'text'  => array(
						'type'        => 'textarea',
						'label'       => __( 'Step Instructions', 'meowseo' ),
						'description' => __( 'Detailed instructions for this step', 'meowseo' ),
						'required'    => true,
						'rows'        => 3,
					),
					'image' => array(
						'type'        => 'image',
						'label'       => __( 'Step Image', 'meowseo' ),
						'description' => __( 'Image showing this step', 'meowseo' ),
					),
					'url'   => array(
						'type'        => 'url',
						'label'       => __( 'Step URL', 'meowseo' ),
						'description' => __( 'URL to a page with more details about this step', 'meowseo' ),
					),
				),
			),
			'video'       => array(
				'type'        => 'group',
				'label'       => __( 'Video', 'meowseo' ),
				'description' => __( 'Video showing how to complete the how-to', 'meowseo' ),
				'fields'      => array(
					'@type'        => array(
						'type'    => 'hidden',
						'default' => 'VideoObject',
					),
					'name'         => array(
						'type'  => 'text',
						'label' => __( 'Video Name', 'meowseo' ),
					),
					'description'  => array(
						'type'  => 'textarea',
						'label' => __( 'Video Description', 'meowseo' ),
						'rows'  => 2,
					),
					'thumbnailUrl' => array(
						'type'  => 'image',
						'label' => __( 'Thumbnail URL', 'meowseo' ),
					),
					'contentUrl'   => array(
						'type'  => 'url',
						'label' => __( 'Video URL', 'meowseo' ),
					),
					'embedUrl'     => array(
						'type'  => 'url',
						'label' => __( 'Embed URL', 'meowseo' ),
					),
					'uploadDate'   => array(
						'type'    => 'text',
						'label'   => __( 'Upload Date', 'meowseo' ),
						'default' => '%date(Y-m-d)%',
					),
					'duration'     => array(
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

		// Set default step.
		$defaults['step'] = array(
			array(
				'@type' => 'HowToStep',
				'name'  => '',
				'text'  => '',
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$howto = new HowTo_Schema();
	$howto->register();
} );
