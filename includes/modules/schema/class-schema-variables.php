<?php
/**
 * Schema Variables Replacement
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Variables class.
 */
class Schema_Variables {

	/**
	 * Replace variables in text.
	 *
	 * @param string      $text   Text with variables.
	 * @param object|null $object Post or term object.
	 * @return string Text with variables replaced.
	 */
	public static function replace( string $text, $object = null ): string {
		if ( empty( $text ) || false === strpos( $text, '%' ) ) {
			return $text;
		}

		// Get current object if not provided.
		if ( null === $object ) {
			$object = get_queried_object();
		}

		// Get post object if it's a post ID.
		if ( is_numeric( $object ) ) {
			$object = get_post( $object );
		}

		// Replace variables.
		$variables = self::get_variables( $object );

		foreach ( $variables as $var => $value ) {
			$text = str_replace( $var, $value, $text );
		}

		// Handle date variables with custom format.
		$text = self::replace_date_variables( $text, $object );

		return $text;
	}

	/**
	 * Get all available variables and their values.
	 *
	 * @param object|null $object Post or term object.
	 * @return array Array of variables and values.
	 */
	public static function get_variables( $object = null ): array {
		$variables = array();

		if ( $object instanceof \WP_Post ) {
			$variables = self::get_post_variables( $object );
		} elseif ( $object instanceof \WP_Term ) {
			$variables = self::get_term_variables( $object );
		} elseif ( $object instanceof \WP_User ) {
			$variables = self::get_user_variables( $object );
		}

		// Add global variables.
		$variables = array_merge( $variables, self::get_global_variables() );

		return apply_filters( 'meowseo_schema_variables', $variables, $object );
	}

	/**
	 * Get post-specific variables.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Array of variables.
	 */
	private static function get_post_variables( \WP_Post $post ): array {
		$author = get_userdata( $post->post_author );

		return array(
			'%title%'        => get_the_title( $post ),
			'%excerpt%'      => self::get_excerpt( $post ),
			'%content%'      => wp_strip_all_tags( $post->post_content ),
			'%post_id%'      => $post->ID,
			'%post_type%'    => $post->post_type,
			'%post_author%'  => $author ? $author->display_name : '',
			'%author%'       => $author ? $author->display_name : '',
			'%name%'         => $author ? $author->display_name : '',
			'%date%'         => get_the_date( '', $post ),
			'%modified%'     => get_the_modified_date( '', $post ),
			'%year%'         => get_the_date( 'Y', $post ),
			'%month%'        => get_the_date( 'm', $post ),
			'%day%'          => get_the_date( 'd', $post ),
			'%permalink%'    => get_permalink( $post ),
			'%url%'          => get_permalink( $post ),
			'%featured_image%' => get_the_post_thumbnail_url( $post, 'full' ),
		);
	}

	/**
	 * Get term-specific variables.
	 *
	 * @param \WP_Term $term Term object.
	 * @return array Array of variables.
	 */
	private static function get_term_variables( \WP_Term $term ): array {
		return array(
			'%title%'       => $term->name,
			'%name%'        => $term->name,
			'%description%' => $term->description,
			'%term_id%'     => $term->term_id,
			'%taxonomy%'    => $term->taxonomy,
			'%slug%'        => $term->slug,
			'%permalink%'   => get_term_link( $term ),
			'%url%'         => get_term_link( $term ),
		);
	}

	/**
	 * Get user-specific variables.
	 *
	 * @param \WP_User $user User object.
	 * @return array Array of variables.
	 */
	private static function get_user_variables( \WP_User $user ): array {
		return array(
			'%name%'        => $user->display_name,
			'%first_name%'  => $user->first_name,
			'%last_name%'   => $user->last_name,
			'%user_email%'  => $user->user_email,
			'%user_login%'  => $user->user_login,
			'%author_url%'  => get_author_posts_url( $user->ID ),
			'%description%' => get_user_meta( $user->ID, 'description', true ),
		);
	}

	/**
	 * Get global variables.
	 *
	 * @return array Array of variables.
	 */
	private static function get_global_variables(): array {
		return array(
			'%sitename%'     => get_bloginfo( 'name' ),
			'%sitedesc%'     => get_bloginfo( 'description' ),
			'%siteurl%'      => home_url(),
			'%currentdate%'  => current_time( get_option( 'date_format' ) ),
			'%currentyear%'  => current_time( 'Y' ),
			'%currentmonth%' => current_time( 'm' ),
			'%currentday%'   => current_time( 'd' ),
			'%sep%'          => '|',
		);
	}

	/**
	 * Replace date variables with custom format.
	 *
	 * @param string      $text   Text with date variables.
	 * @param object|null $object Post or term object.
	 * @return string Text with date variables replaced.
	 */
	private static function replace_date_variables( string $text, $object = null ): string {
		// Match %date(format)% and %modified(format)%.
		if ( ! preg_match_all( '/%(?:date|modified)\(([^)]+)\)%/', $text, $matches ) ) {
			return $text;
		}

		if ( ! $object instanceof \WP_Post ) {
			return $text;
		}

		foreach ( $matches[0] as $index => $match ) {
			$format = $matches[1][ $index ];
			
			if ( strpos( $match, 'modified' ) !== false ) {
				$value = get_the_modified_date( $format, $object );
			} else {
				$value = get_the_date( $format, $object );
			}

			$text = str_replace( $match, $value, $text );
		}

		return $text;
	}

	/**
	 * Get post excerpt.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Post excerpt.
	 */
	private static function get_excerpt( \WP_Post $post ): string {
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		// Generate excerpt from content.
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );

		return $excerpt;
	}

	/**
	 * Get list of available variables for documentation.
	 *
	 * @return array Array of variables with descriptions.
	 */
	public static function get_available_variables(): array {
		return array(
			'Post Variables'   => array(
				'%title%'          => __( 'Post title', 'meowseo' ),
				'%excerpt%'        => __( 'Post excerpt', 'meowseo' ),
				'%content%'        => __( 'Post content (stripped)', 'meowseo' ),
				'%post_id%'        => __( 'Post ID', 'meowseo' ),
				'%post_type%'      => __( 'Post type', 'meowseo' ),
				'%author%'         => __( 'Post author name', 'meowseo' ),
				'%date%'           => __( 'Post publish date', 'meowseo' ),
				'%date(format)%'   => __( 'Post date with custom format', 'meowseo' ),
				'%modified%'       => __( 'Post modified date', 'meowseo' ),
				'%modified(format)%' => __( 'Modified date with custom format', 'meowseo' ),
				'%permalink%'      => __( 'Post URL', 'meowseo' ),
				'%featured_image%' => __( 'Featured image URL', 'meowseo' ),
			),
			'Site Variables'   => array(
				'%sitename%'    => __( 'Site name', 'meowseo' ),
				'%sitedesc%'    => __( 'Site description', 'meowseo' ),
				'%siteurl%'     => __( 'Site URL', 'meowseo' ),
				'%currentdate%' => __( 'Current date', 'meowseo' ),
				'%currentyear%' => __( 'Current year', 'meowseo' ),
				'%sep%'         => __( 'Separator', 'meowseo' ),
			),
			'Term Variables'   => array(
				'%name%'        => __( 'Term name', 'meowseo' ),
				'%description%' => __( 'Term description', 'meowseo' ),
				'%term_id%'     => __( 'Term ID', 'meowseo' ),
				'%taxonomy%'    => __( 'Taxonomy name', 'meowseo' ),
			),
			'Author Variables' => array(
				'%name%'        => __( 'Author display name', 'meowseo' ),
				'%first_name%'  => __( 'Author first name', 'meowseo' ),
				'%last_name%'   => __( 'Author last name', 'meowseo' ),
				'%author_url%'  => __( 'Author archive URL', 'meowseo' ),
			),
		);
	}
}
