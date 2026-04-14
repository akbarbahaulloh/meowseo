<?php
/**
 * Meta Postmeta Registration Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

/**
 * Meta_Postmeta class
 *
 * Responsible for registering all SEO postmeta fields with WordPress.
 */
class Meta_Postmeta {
	/**
	 * Postmeta keys with their types
	 *
	 * @var array
	 */
	private const META_KEYS = array(
		'title'               => 'string',
		'description'         => 'string',
		'robots_noindex'      => 'boolean',
		'robots_nofollow'     => 'boolean',
		'canonical'           => 'string',
		'og_title'            => 'string',
		'og_description'      => 'string',
		'og_image'            => 'integer',
		'twitter_title'       => 'string',
		'twitter_description' => 'string',
		'twitter_image'       => 'integer',
		'focus_keyword'       => 'string',
		'direct_answer'       => 'string',
		'schema_type'         => 'string',
		'schema_config'       => 'string',
		'gsc_last_submit'     => 'integer',
	);

	/**
	 * Register all postmeta fields
	 *
	 * @return void
	 */
	public function register(): void {
		$post_types = $this->get_post_types();

		foreach ( self::META_KEYS as $key => $type ) {
			$meta_key = '_meowseo_' . $key;
			$args     = $this->get_meta_args( $key, $type );

			foreach ( $post_types as $post_type ) {
				register_post_meta( $post_type, $meta_key, $args );
			}
		}
	}

	/**
	 * Get all public post types
	 *
	 * @return array Post types.
	 */
	private function get_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			)
		);

		return $post_types;
	}

	/**
	 * Get meta registration args
	 *
	 * @param string $key  Meta key.
	 * @param string $type Meta type.
	 * @return array Registration args.
	 */
	private function get_meta_args( string $key, string $type ): array {
		$args = array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => $type,
		);

		// Add sanitize callback based on type.
		switch ( $type ) {
			case 'string':
				$args['sanitize_callback'] = 'sanitize_text_field';
				break;
			case 'boolean':
				// Boolean type has built-in sanitization.
				break;
			case 'integer':
				// Integer type has built-in sanitization.
				break;
		}

		return $args;
	}
}
