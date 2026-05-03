<?php
/**
 * Schema REST API
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_REST class.
 */
class Schema_REST {

	/**
	 * Namespace for REST API.
	 *
	 * @var string
	 */
	private $namespace = 'meowseo/v1';

	/**
	 * Schema DB instance.
	 *
	 * @var Schema_DB
	 */
	private $schema_db;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->schema_db = new Schema_DB();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		// Get all schemas for a post.
		register_rest_route(
			$this->namespace,
			'/schemas/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schemas' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get single schema.
		register_rest_route(
			$this->namespace,
			'/schemas/(?P<post_id>\d+)/(?P<schema_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'schema_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Create or update schema.
		register_rest_route(
			$this->namespace,
			'/schemas/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'schema'  => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);

		// Update schema.
		register_rest_route(
			$this->namespace,
			'/schemas/(?P<post_id>\d+)/(?P<schema_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'schema_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'schema'    => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);

		// Delete schema.
		register_rest_route(
			$this->namespace,
			'/schemas/(?P<post_id>\d+)/(?P<schema_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'schema_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get available schema types.
		register_rest_route(
			$this->namespace,
			'/schema-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schema_types' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get schema type fields.
		register_rest_route(
			$this->namespace,
			'/schema-types/(?P<type>[a-zA-Z0-9_-]+)/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schema_type_fields' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get schema type defaults.
		register_rest_route(
			$this->namespace,
			'/schema-types/(?P<type>[a-zA-Z0-9_-]+)/defaults',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schema_type_defaults' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'type'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'post_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Validate schema.
		register_rest_route(
			$this->namespace,
			'/schemas/validate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'schema' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);

		// Preview schema JSON-LD.
		register_rest_route(
			$this->namespace,
			'/schemas/preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview_schema' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'schema'  => array(
						'required' => true,
						'type'     => 'object',
					),
					'post_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get available variables.
		register_rest_route(
			$this->namespace,
			'/schema-variables',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_variables' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permission for REST API requests.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_permission( $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get all schemas for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_schemas( $request ) {
		$post_id = $request->get_param( 'post_id' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post ID', 'meowseo' ), array( 'status' => 404 ) );
		}

		$schemas = $this->schema_db->get_schemas( $post_id );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $schemas,
		) );
	}

	/**
	 * Get single schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_schema( $request ) {
		$post_id   = $request->get_param( 'post_id' );
		$schema_id = $request->get_param( 'schema_id' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post ID', 'meowseo' ), array( 'status' => 404 ) );
		}

		$schema = $this->schema_db->get_schema( $post_id, $schema_id );

		if ( ! $schema ) {
			return new \WP_Error( 'schema_not_found', __( 'Schema not found', 'meowseo' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $schema,
		) );
	}

	/**
	 * Save schema (create new).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_schema( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$schema  = $request->get_param( 'schema' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post ID', 'meowseo' ), array( 'status' => 404 ) );
		}

		// Validate schema.
		$validation = $this->validate_schema_data( $schema );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Save schema.
		$schema_id = $this->schema_db->save_schema( $post_id, $schema );

		if ( ! $schema_id ) {
			return new \WP_Error( 'save_failed', __( 'Failed to save schema', 'meowseo' ), array( 'status' => 500 ) );
		}

		// Get saved schema.
		$saved_schema = $this->schema_db->get_schema( $post_id, $schema_id );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Schema saved successfully', 'meowseo' ),
			'data'    => $saved_schema,
		) );
	}

	/**
	 * Update schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_schema( $request ) {
		$post_id   = $request->get_param( 'post_id' );
		$schema_id = $request->get_param( 'schema_id' );
		$schema    = $request->get_param( 'schema' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post ID', 'meowseo' ), array( 'status' => 404 ) );
		}

		// Check if schema exists.
		$existing = $this->schema_db->get_schema( $post_id, $schema_id );
		if ( ! $existing ) {
			return new \WP_Error( 'schema_not_found', __( 'Schema not found', 'meowseo' ), array( 'status' => 404 ) );
		}

		// Validate schema.
		$validation = $this->validate_schema_data( $schema );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Ensure schema ID is preserved.
		$schema['id'] = $schema_id;

		// Update schema.
		$updated_id = $this->schema_db->save_schema( $post_id, $schema );

		if ( ! $updated_id ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update schema', 'meowseo' ), array( 'status' => 500 ) );
		}

		// Get updated schema.
		$updated_schema = $this->schema_db->get_schema( $post_id, $schema_id );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Schema updated successfully', 'meowseo' ),
			'data'    => $updated_schema,
		) );
	}

	/**
	 * Delete schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_schema( $request ) {
		$post_id   = $request->get_param( 'post_id' );
		$schema_id = $request->get_param( 'schema_id' );

		if ( ! get_post( $post_id ) ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post ID', 'meowseo' ), array( 'status' => 404 ) );
		}

		// Check if schema exists.
		$existing = $this->schema_db->get_schema( $post_id, $schema_id );
		if ( ! $existing ) {
			return new \WP_Error( 'schema_not_found', __( 'Schema not found', 'meowseo' ), array( 'status' => 404 ) );
		}

		// Delete schema.
		$deleted = $this->schema_db->delete_schema( $post_id, $schema_id );

		if ( ! $deleted ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete schema', 'meowseo' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Schema deleted successfully', 'meowseo' ),
		) );
	}

	/**
	 * Get available schema types.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_schema_types( $request ) {
		$types = Schema_Registry::get_all();

		$formatted_types = array();
		foreach ( $types as $type_id => $type ) {
			$formatted_types[] = array(
				'id'          => $type_id,
				'type'        => $type->get_type(),
				'label'       => $type->get_label(),
				'description' => $type->get_description(),
				'icon'        => $type->get_icon(),
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $formatted_types,
		) );
	}

	/**
	 * Get schema type fields.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_schema_type_fields( $request ) {
		$type     = $request->get_param( 'type' );
		$schema = Schema_Registry::get( $type );

		if ( ! $schema ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid schema type', 'meowseo' ), array( 'status' => 404 ) );
		}

		$fields = $schema->get_fields();

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $fields,
		) );
	}

	/**
	 * Get schema type defaults.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_schema_type_defaults( $request ) {
		$type     = $request->get_param( 'type' );
		$post_id  = $request->get_param( 'post_id' );
		$schema = Schema_Registry::get( $type );

		if ( ! $schema ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid schema type', 'meowseo' ), array( 'status' => 404 ) );
		}

		$object   = $post_id ? get_post( $post_id ) : null;
		$defaults = $schema->get_defaults( $object );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $defaults,
		) );
	}

	/**
	 * Validate schema.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function validate_schema( $request ) {
		$schema = $request->get_param( 'schema' );

		$validation = $this->validate_schema_data( $schema );

		if ( is_wp_error( $validation ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'errors'  => $validation->get_error_messages(),
			) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Schema is valid', 'meowseo' ),
		) );
	}

	/**
	 * Preview schema JSON-LD.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function preview_schema( $request ) {
		$schema  = $request->get_param( 'schema' );
		$post_id = $request->get_param( 'post_id' );

		if ( empty( $schema['type'] ) ) {
			return new \WP_Error( 'missing_type', __( 'Schema type is required', 'meowseo' ), array( 'status' => 400 ) );
		}

		$schema_type = Schema_Registry::get( $schema['type'] );

		if ( ! $schema_type ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid schema type', 'meowseo' ), array( 'status' => 404 ) );
		}

		$object = $post_id ? get_post( $post_id ) : null;
		$data   = isset( $schema['data'] ) ? $schema['data'] : array();
		$jsonld = $schema_type->generate( $data, $object );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $jsonld,
		) );
	}

	/**
	 * Get available variables.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_variables( $request ) {
		$variables = Schema_Variables::get_available_variables();

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $variables,
		) );
	}

	/**
	 * Validate schema data.
	 *
	 * @param array $schema Schema data.
	 * @return true|\WP_Error
	 */
	private function validate_schema_data( $schema ) {
		if ( empty( $schema['type'] ) ) {
			return new \WP_Error( 'missing_type', __( 'Schema type is required', 'meowseo' ), array( 'status' => 400 ) );
		}

		$schema_type = Schema_Registry::get( $schema['type'] );

		if ( ! $schema_type ) {
			return new \WP_Error( 'invalid_type', __( 'Invalid schema type', 'meowseo' ), array( 'status' => 400 ) );
		}

		// Validate required fields.
		$fields = $schema_type->get_fields();
		$data   = isset( $schema['data'] ) ? $schema['data'] : array();
		$errors = array();

		foreach ( $fields as $field_id => $field ) {
			if ( ! empty( $field['required'] ) && empty( $data[ $field_id ] ) ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( '%s is required', 'meowseo' ),
					$field['label']
				);
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'validation_failed', implode( ', ', $errors ), array( 'status' => 400 ) );
		}

		return true;
	}
}
