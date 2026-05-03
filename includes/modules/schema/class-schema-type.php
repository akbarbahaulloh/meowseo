<?php
/**
 * Base Schema Type Class
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Type abstract class.
 */
abstract class Schema_Type {

	/**
	 * Schema type name.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Schema type label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Schema type description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Schema type icon.
	 *
	 * @var string
	 */
	protected $icon = 'admin-generic';

	/**
	 * Get schema type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get schema label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get schema description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get schema icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return $this->icon;
	}

	/**
	 * Get schema fields configuration.
	 *
	 * @return array
	 */
	abstract public function get_fields(): array;

	/**
	 * Get default schema data.
	 *
	 * @param object|null $object Post or term object.
	 * @return array
	 */
	public function get_defaults( $object = null ): array {
		$defaults = array(
			'@type'    => $this->type,
			'metadata' => array(
				'title'       => $this->label,
				'type'        => 'template',
				'shortcode'   => Schema_DB::generate_shortcode_id(),
				'isPrimary'   => true,
				'name'        => '%title%',
				'description' => '%excerpt%',
			),
		);

		// Add default values from fields.
		foreach ( $this->get_fields() as $key => $field ) {
			if ( isset( $field['default'] ) ) {
				$defaults[ $key ] = $field['default'];
			}
		}

		return apply_filters( 'meowseo_schema_defaults', $defaults, $this->type, $object );
	}

	/**
	 * Generate JSON-LD from schema data.
	 *
	 * @param array       $data   Schema data.
	 * @param object|null $object Post or term object.
	 * @return array JSON-LD array.
	 */
	public function generate( array $data, $object = null ): array {
		// Remove metadata.
		$jsonld = $data;
		unset( $jsonld['metadata'] );

		// Replace variables.
		$jsonld = $this->replace_variables( $jsonld, $object );

		// Remove empty values.
		$jsonld = $this->remove_empty_values( $jsonld );

		// Add @context if not exists.
		if ( ! isset( $jsonld['@context'] ) ) {
			$jsonld['@context'] = 'https://schema.org';
		}

		return apply_filters( 'meowseo_schema_generate', $jsonld, $data, $this->type, $object );
	}

	/**
	 * Replace variables in schema data.
	 *
	 * @param array       $data   Schema data.
	 * @param object|null $object Post or term object.
	 * @return array Schema data with variables replaced.
	 */
	protected function replace_variables( array $data, $object = null ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->replace_variables( $value, $object );
			} elseif ( is_string( $value ) && strpos( $value, '%' ) !== false ) {
				$data[ $key ] = Schema_Variables::replace( $value, $object );
			}
		}

		return $data;
	}

	/**
	 * Remove empty values from array.
	 *
	 * @param array $data Array to clean.
	 * @return array Cleaned array.
	 */
	protected function remove_empty_values( array $data ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->remove_empty_values( $value );
				
				// Remove empty arrays.
				if ( empty( $data[ $key ] ) ) {
					unset( $data[ $key ] );
				}
			} elseif ( '' === $value || null === $value ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Validate schema data.
	 *
	 * @param array $data Schema data.
	 * @return array Array with 'valid' boolean and 'errors' array.
	 */
	public function validate( array $data ): array {
		$errors = array();

		// Check required fields.
		foreach ( $this->get_fields() as $key => $field ) {
			if ( ! empty( $field['required'] ) && empty( $data[ $key ] ) ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( '%s is required', 'meowseo' ),
					$field['label']
				);
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Get schema info for registration.
	 *
	 * @return array
	 */
	public function get_info(): array {
		return array(
			'type'        => $this->type,
			'label'       => $this->label,
			'description' => $this->description,
			'icon'        => $this->icon,
			'fields'      => $this->get_fields(),
		);
	}

	/**
	 * Register this schema type.
	 */
	public function register(): void {
		Schema_Registry::register( $this );
	}
}
