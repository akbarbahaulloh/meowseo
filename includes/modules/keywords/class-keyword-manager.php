<?php
/**
 * Keyword Manager class for managing focus keywords.
 *
 * Handles storage and retrieval of primary and secondary keywords,
 * enforcing validation rules and keyword count limits.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Keywords;

use MeowSEO\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword_Manager class.
 *
 * Manages the storage and retrieval of primary + secondary keywords.
 */
class Keyword_Manager {

	/**
	 * Maximum number of keywords allowed (1 primary + 4 secondary).
	 */
	const MAX_KEYWORDS = 5;

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Get all keywords for a post.
	 *
	 * Returns an array with primary keyword and secondary keywords.
	 *
	 * @param int $post_id Post ID.
	 * @return array Array with structure:
	 *               [
	 *                   'primary' => 'wordpress seo',
	 *                   'secondary' => ['seo plugin', 'search optimization'],
	 *               ]
	 */
	public function get_keywords( int $post_id ): array {
		$primary   = get_post_meta( $post_id, '_meowseo_focus_keyword', true );
		$secondary = get_post_meta( $post_id, '_meowseo_secondary_keywords', true );

		// Decode secondary keywords JSON.
		if ( ! empty( $secondary ) && is_string( $secondary ) ) {
			$secondary = json_decode( $secondary, true );
			if ( ! is_array( $secondary ) ) {
				$secondary = array();
			}
		} else {
			$secondary = array();
		}

		return array(
			'primary'   => $primary ?: '',
			'secondary' => $secondary,
		);
	}

	/**
	 * Set primary keyword for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $keyword Keyword to set.
	 * @return bool True on success, false on failure.
	 */
	public function set_primary_keyword( int $post_id, string $keyword ): bool {
		// Validate keyword.
		$keyword = trim( $keyword );

		// Allow empty keyword (user can clear it).
		if ( empty( $keyword ) ) {
			return delete_post_meta( $post_id, '_meowseo_focus_keyword' );
		}

		// Update postmeta.
		return (bool) update_post_meta( $post_id, '_meowseo_focus_keyword', $keyword );
	}

	/**
	 * Add a secondary keyword.
	 *
	 * Appends to the secondary keywords JSON array.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $keyword Keyword to add.
	 * @return bool|array True on success, array with error on failure.
	 */
	public function add_secondary_keyword( int $post_id, string $keyword ): bool|array {
		// Validate keyword.
		$keyword = trim( $keyword );

		if ( empty( $keyword ) ) {
			return array(
				'error'   => true,
				'message' => __( 'Keyword cannot be empty.', 'meowseo' ),
			);
		}

		// Get current keywords.
		$keywords = $this->get_keywords( $post_id );

		// Check if keyword already exists.
		if ( $keywords['primary'] === $keyword ) {
			return array(
				'error'   => true,
				'message' => __( 'This keyword is already set as the primary keyword.', 'meowseo' ),
			);
		}

		if ( in_array( $keyword, $keywords['secondary'], true ) ) {
			return array(
				'error'   => true,
				'message' => __( 'This keyword already exists in secondary keywords.', 'meowseo' ),
			);
		}

		// Validate keyword count.
		$total_count = ( ! empty( $keywords['primary'] ) ? 1 : 0 ) + count( $keywords['secondary'] );
		if ( $total_count >= self::MAX_KEYWORDS ) {
			return array(
				'error'   => true,
				'message' => sprintf(
					/* translators: %d: maximum keyword count */
					__( 'Maximum of %d keywords allowed.', 'meowseo' ),
					self::MAX_KEYWORDS
				),
			);
		}

		// Add keyword to secondary array.
		$keywords['secondary'][] = $keyword;

		// Update postmeta.
		$result = update_post_meta( $post_id, '_meowseo_secondary_keywords', wp_json_encode( $keywords['secondary'] ) );

		return (bool) $result;
	}

	/**
	 * Remove a secondary keyword.
	 *
	 * Removes the keyword from the secondary keywords array.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $keyword Keyword to remove.
	 * @return bool True on success, false on failure.
	 */
	public function remove_secondary_keyword( int $post_id, string $keyword ): bool {
		// Get current keywords.
		$keywords = $this->get_keywords( $post_id );

		// Find and remove keyword.
		$key = array_search( $keyword, $keywords['secondary'], true );
		if ( false === $key ) {
			return false;
		}

		unset( $keywords['secondary'][ $key ] );

		// Re-index array.
		$keywords['secondary'] = array_values( $keywords['secondary'] );

		// Update postmeta.
		if ( empty( $keywords['secondary'] ) ) {
			return delete_post_meta( $post_id, '_meowseo_secondary_keywords' );
		}

		return (bool) update_post_meta( $post_id, '_meowseo_secondary_keywords', wp_json_encode( $keywords['secondary'] ) );
	}

	/**
	 * Reorder secondary keywords.
	 *
	 * Updates the array order in postmeta.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $keywords Array of keywords in desired order.
	 * @return bool|array True on success, array with error on failure.
	 */
	public function reorder_secondary_keywords( int $post_id, array $keywords ): bool|array {
		// Get current keywords.
		$current = $this->get_keywords( $post_id );

		// Validate that all keywords in the new order exist in current secondary keywords.
		foreach ( $keywords as $keyword ) {
			if ( ! in_array( $keyword, $current['secondary'], true ) ) {
				return array(
					'error'   => true,
					'message' => __( 'Invalid keyword in reorder list.', 'meowseo' ),
				);
			}
		}

		// Validate that all current keywords are in the new order.
		if ( count( $keywords ) !== count( $current['secondary'] ) ) {
			return array(
				'error'   => true,
				'message' => __( 'Keyword count mismatch in reorder list.', 'meowseo' ),
			);
		}

		// Update postmeta with new order.
		return (bool) update_post_meta( $post_id, '_meowseo_secondary_keywords', wp_json_encode( $keywords ) );
	}

	/**
	 * Validate keyword count.
	 *
	 * Enforces the 5 keyword maximum (1 primary + 4 secondary).
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if valid, false if exceeds maximum.
	 */
	public function validate_keyword_count( int $post_id ): bool {
		$keywords = $this->get_keywords( $post_id );

		$total_count = ( ! empty( $keywords['primary'] ) ? 1 : 0 ) + count( $keywords['secondary'] );

		return $total_count <= self::MAX_KEYWORDS;
	}

	/**
	 * Get total keyword count for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Total keyword count.
	 */
	public function get_keyword_count( int $post_id ): int {
		$keywords = $this->get_keywords( $post_id );

		return ( ! empty( $keywords['primary'] ) ? 1 : 0 ) + count( $keywords['secondary'] );
	}
}
