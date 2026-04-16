<?php
/**
 * Database helper class.
 *
 * All $wpdb interactions go through this class with prepared statements.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Helpers
 */

namespace MeowSEO\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * DB helper class.
 *
 * Wraps all $wpdb interactions with prepared statements for security.
 */
class DB {

	/**
	 * Get exact redirect match.
	 *
	 * @param string $url Source URL to match.
	 * @return array|null Redirect row or null if not found.
	 */
	public static function get_redirect_exact( string $url ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE source_url = %s AND is_active = 1 AND is_regex = 0 LIMIT 1",
			$url
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		return $result ?: null;
	}

	/**
	 * Get all active regex redirect rules.
	 *
	 * @return array Array of redirect rows with is_regex=1.
	 */
	public static function get_redirect_regex_rules(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		$query = $wpdb->prepare(
			"SELECT id, source_url, target_url, redirect_type FROM {$table} WHERE is_regex = %d AND is_active = 1",
			1
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Increment redirect hit count.
	 *
	 * @param int $id Redirect ID.
	 * @return void
	 */
	public static function increment_redirect_hit( int $id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_redirects';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET hit_count = hit_count + 1, last_hit = NOW() WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Bulk upsert 404 log entries.
	 *
	 * @param array $rows Array of 404 log rows to insert/update.
	 * @return void
	 */
	public static function bulk_upsert_404( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_404_log';

		$values = [];
		$placeholders = [];

		foreach ( $rows as $row ) {
			$url_hash = hash( 'sha256', $row['url'] );
			$first_seen = $row['first_seen'] ?? gmdate( 'Y-m-d' );
			$last_seen = $row['last_seen'] ?? gmdate( 'Y-m-d' );
			$hit_count = $row['hit_count'] ?? 1;

			$placeholders[] = '(%s, %s, %s, %s, %d, %s, %s)';
			$values[] = $row['url'];
			$values[] = $url_hash;
			$values[] = $row['referrer'] ?? '';
			$values[] = $row['user_agent'] ?? '';
			$values[] = $hit_count;
			$values[] = $first_seen;
			$values[] = $last_seen;
		}

		$query = "INSERT INTO {$table} (url, url_hash, referrer, user_agent, hit_count, first_seen, last_seen) VALUES ";
		$query .= implode( ', ', $placeholders );
		$query .= " ON DUPLICATE KEY UPDATE hit_count = hit_count + VALUES(hit_count), last_seen = VALUES(last_seen)";

		$prepared = $wpdb->prepare( $query, $values );

		$wpdb->query( $prepared );
	}

	/**
	 * Get 404 log entries.
	 *
	 * @param array $args Query arguments (limit, offset, orderby, order).
	 * @return array Array of 404 log rows.
	 */
	public static function get_404_log( array $args ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_404_log';

		$defaults = [
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'last_seen',
			'order'   => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = [ 'id', 'url', 'hit_count', 'first_seen', 'last_seen' ];
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_seen';

		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
			absint( $args['limit'] ),
			absint( $args['offset'] )
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Get GSC queue entries ready for processing.
	 *
	 * @param int $limit Maximum number of entries to retrieve.
	 * @return array Array of queue rows.
	 */
	public static function get_gsc_queue( int $limit = 10 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = 'pending' AND (retry_after IS NULL OR retry_after <= NOW()) ORDER BY created_at ASC LIMIT %d",
			$limit
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Update GSC queue entry retry timestamp.
	 *
	 * @param int $id Queue entry ID.
	 * @param int $retry_after Unix timestamp for next retry.
	 * @return void
	 */
	public static function update_gsc_queue_retry( int $id, int $retry_after ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_queue';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'pending', attempts = attempts + 1, retry_after = FROM_UNIXTIME(%d) WHERE id = %d",
				$retry_after,
				$id
			)
		);
	}

	/**
	 * Upsert GSC data entries.
	 *
	 * @param array $rows Array of GSC data rows to insert/update.
	 * @return void
	 */
	public static function upsert_gsc_data( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_gsc_data';

		$values = [];
		$placeholders = [];

		foreach ( $rows as $row ) {
			$url_hash = hash( 'sha256', $row['url'] );

			$placeholders[] = '(%s, %s, %s, %d, %d, %f, %f)';
			$values[] = $row['url'];
			$values[] = $url_hash;
			$values[] = $row['date'];
			$values[] = $row['clicks'] ?? 0;
			$values[] = $row['impressions'] ?? 0;
			$values[] = $row['ctr'] ?? 0.0;
			$values[] = $row['position'] ?? 0.0;
		}

		$query = "INSERT INTO {$table} (url, url_hash, date, clicks, impressions, ctr, position) VALUES ";
		$query .= implode( ', ', $placeholders );
		$query .= " ON DUPLICATE KEY UPDATE clicks = VALUES(clicks), impressions = VALUES(impressions), ctr = VALUES(ctr), position = VALUES(position)";

		$prepared = $wpdb->prepare( $query, $values );

		$wpdb->query( $prepared );
	}

	/**
	 * Get link checks for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of link check rows.
	 */
	public static function get_link_checks( int $post_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_link_checks';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE source_post_id = %d ORDER BY id ASC",
			$post_id
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Upsert a link check entry.
	 *
	 * @param array $row Link check row data.
	 * @return void
	 */
	public static function upsert_link_check( array $row ): void {
		if ( empty( $row['source_post_id'] ) || empty( $row['target_url'] ) ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'meowseo_link_checks';

		$url_hash = hash( 'sha256', $row['target_url'] );

		$data = [
			'source_post_id'  => absint( $row['source_post_id'] ),
			'target_url'      => $row['target_url'],
			'target_url_hash' => $url_hash,
			'anchor_text'     => $row['anchor_text'] ?? null,
			'http_status'     => isset( $row['http_status'] ) ? absint( $row['http_status'] ) : null,
			'last_checked'    => $row['last_checked'] ?? null,
		];

		$format = [ '%d', '%s', '%s', '%s', '%d', '%s' ];

		// Check if entry exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE source_post_id = %d AND target_url_hash = %s LIMIT 1",
				$data['source_post_id'],
				$url_hash
			)
		);

		if ( $exists ) {
			// Update existing entry.
			$wpdb->update(
				$table,
				$data,
				[ 'id' => $exists ],
				$format,
				[ '%d' ]
			);
		} else {
			// Insert new entry.
			$wpdb->insert( $table, $data, $format );
		}
	}
}
