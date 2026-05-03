<?php
/**
 * FAQ Schema Type
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
 * FAQ_Schema class.
 */
class FAQ_Schema extends Schema_Type {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type        = 'FAQPage';
		$this->label       = __( 'FAQ', 'meowseo' );
		$this->description = __( 'A page containing a list of questions and answers.', 'meowseo' );
		$this->icon        = 'editor-help';
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	public function get_fields(): array {
		return array(
			'mainEntity' => array(
				'type'        => 'repeater',
				'label'       => __( 'Questions & Answers', 'meowseo' ),
				'description' => __( 'List of frequently asked questions', 'meowseo' ),
				'required'    => true,
				'fields'      => array(
					'@type'          => array(
						'type'    => 'hidden',
						'default' => 'Question',
					),
					'name'           => array(
						'type'        => 'textarea',
						'label'       => __( 'Question', 'meowseo' ),
						'description' => __( 'The question being asked', 'meowseo' ),
						'required'    => true,
						'rows'        => 2,
					),
					'acceptedAnswer' => array(
						'type'   => 'group',
						'label'  => __( 'Answer', 'meowseo' ),
						'fields' => array(
							'@type' => array(
								'type'    => 'hidden',
								'default' => 'Answer',
							),
							'text'  => array(
								'type'        => 'textarea',
								'label'       => __( 'Answer Text', 'meowseo' ),
								'description' => __( 'The answer to the question', 'meowseo' ),
								'required'    => true,
								'rows'        => 4,
							),
						),
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

		// Set default FAQ items.
		$defaults['mainEntity'] = array(
			array(
				'@type'          => 'Question',
				'name'           => '',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => '',
				),
			),
		);

		return $defaults;
	}
}

// Register the schema type.
add_action( 'meowseo_schema_types_loaded', function() {
	$faq = new FAQ_Schema();
	$faq->register();
} );
