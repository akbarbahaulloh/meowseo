<?php
/**
 * Migration class for handling plugin upgrades
 *
 * @package MeowSEO
 */

namespace MeowSEO;

use MeowSEO\Modules\Meta\Title_Patterns;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration class
 *
 * Handles data migration between plugin versions.
 */
class Migration {
	/**
	 * Current migration version
	 */
	private const MIGRATION_VERSION = '2.1.0';

	/**
	 * Migration version option key
	 */
	private const VERSION_KEY = 'meowseo_migration_version';

	/**
	 * Run all pending migrations
	 *
	 * @return void
	 */
	public static function run(): void {
		$current_version = get_option( self::VERSION_KEY, '0.0.0' );

		// Run migrations in order.
		if ( version_compare( $current_version, '2.0.0', '<' ) ) {
			self::migrate_to_2_0_0();
		}

		if ( version_compare( $current_version, '2.1.0', '<' ) ) {
			self::migrate_to_2_1_0();
		}

		// Update migration version.
		update_option( self::VERSION_KEY, self::MIGRATION_VERSION );
	}

	/**
	 * Migrate to version 2.0.0 (Meta Module rebuild)
	 *
	 * Migrates old individual options to new meowseo_options structure.
	 *
	 * @return void
	 */
	private static function migrate_to_2_0_0(): void {
		// Get old options.
		$old_separator = get_option( 'meowseo_separator', '|' );
		$old_og_image  = get_option( 'meowseo_default_og_image', '' );

		// Get current options array.
		$options = get_option( 'meowseo_options', array() );

		// Migrate separator if not already set.
		if ( ! isset( $options['separator'] ) ) {
			$options['separator'] = $old_separator;
		}

		// Migrate default OG image if not already set.
		if ( ! isset( $options['default_og_image_url'] ) ) {
			$options['default_og_image_url'] = $old_og_image;
		}

		// Initialize title patterns with defaults if not already set.
		if ( ! isset( $options['title_patterns'] ) ) {
			// Get default patterns from Title_Patterns class.
			$patterns_instance = new Title_Patterns( new Options() );
			$options['title_patterns'] = $patterns_instance->get_default_patterns();
		}

		// Initialize noindex_date_archives if not already set.
		if ( ! isset( $options['noindex_date_archives'] ) ) {
			$options['noindex_date_archives'] = false;
		}

		// Initialize robots_txt_custom if not already set.
		if ( ! isset( $options['robots_txt_custom'] ) ) {
			$options['robots_txt_custom'] = '';
		}

		// Save updated options.
		update_option( 'meowseo_options', $options );

		// Delete old option keys.
		delete_option( 'meowseo_separator' );
		delete_option( 'meowseo_default_og_image' );
	}

	/**
	 * Migrate to version 2.1.0 (Multiple Focus Keywords)
	 *
	 * Adds default values for new postmeta keys required for multiple focus keywords feature.
	 * - _meowseo_secondary_keywords: Empty JSON array "[]"
	 * - _meowseo_keyword_analysis: Empty JSON object "{}"
	 *
	 * Requirements: 2.1, 2.3
	 *
	 * @return void
	 */
	private static function migrate_to_2_1_0(): void {
		global $wpdb, $wp_posts_storage;

		// Log migration start.
		error_log( 'MeowSEO Migration 2.1.0: Starting migration for multiple focus keywords postmeta keys' );

		// In test environment, use wp_posts_storage directly
		if ( isset( $wp_posts_storage ) && ! empty( $wp_posts_storage ) ) {
			$post_ids = array_keys( $wp_posts_storage );
			$total_posts = count( $post_ids );
			
			if ( ! $total_posts ) {
				error_log( 'MeowSEO Migration 2.1.0: No posts found to migrate' );
				return;
			}

			error_log( "MeowSEO Migration 2.1.0: Found {$total_posts} posts to process" );

			$processed = 0;
			$secondary_keywords_added = 0;
			$keyword_analysis_added = 0;

			foreach ( $post_ids as $post_id ) {
				// Add _meowseo_secondary_keywords if it doesn't exist.
				$existing_secondary = get_post_meta( $post_id, '_meowseo_secondary_keywords', true );
				if ( empty( $existing_secondary ) ) {
					add_post_meta( $post_id, '_meowseo_secondary_keywords', '[]', true );
					$secondary_keywords_added++;
				}

				// Add _meowseo_keyword_analysis if it doesn't exist.
				$existing_analysis = get_post_meta( $post_id, '_meowseo_keyword_analysis', true );
				if ( empty( $existing_analysis ) ) {
					add_post_meta( $post_id, '_meowseo_keyword_analysis', '{}', true );
					$keyword_analysis_added++;
				}

				$processed++;
			}

			error_log( "MeowSEO Migration 2.1.0: Migration complete. Processed {$processed} posts, added {$secondary_keywords_added} secondary_keywords entries, {$keyword_analysis_added} keyword_analysis entries" );
			return;
		}

		// Production environment: use database queries
		// Get all post IDs that don't have the new postmeta keys.
		// We'll process all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		
		// Build safe SQL for post types.
		$post_types_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Count total posts to migrate.
		$total_posts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p
				WHERE p.post_type IN ({$post_types_placeholders})
				AND p.post_status IN ('publish', 'draft', 'pending', 'private', 'future')",
				...$post_types
			)
		);

		if ( ! $total_posts ) {
			error_log( 'MeowSEO Migration 2.1.0: No posts found to migrate' );
			return;
		}

		error_log( "MeowSEO Migration 2.1.0: Found {$total_posts} posts to process" );

		// Process in batches to prevent memory issues.
		$batch_size = 100;
		$offset = 0;
		$processed = 0;
		$secondary_keywords_added = 0;
		$keyword_analysis_added = 0;

		while ( $offset < $total_posts ) {
			// Get batch of post IDs.
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID 
					FROM {$wpdb->posts}
					WHERE post_type IN ({$post_types_placeholders})
					AND post_status IN ('publish', 'draft', 'pending', 'private', 'future')
					ORDER BY ID ASC
					LIMIT %d OFFSET %d",
					array_merge( $post_types, array( $batch_size, $offset ) )
				)
			);

			if ( empty( $post_ids ) ) {
				break;
			}

			foreach ( $post_ids as $post_id ) {
				// Add _meowseo_secondary_keywords if it doesn't exist.
				$existing_secondary = get_post_meta( $post_id, '_meowseo_secondary_keywords', true );
				if ( empty( $existing_secondary ) ) {
					add_post_meta( $post_id, '_meowseo_secondary_keywords', '[]', true );
					$secondary_keywords_added++;
				}

				// Add _meowseo_keyword_analysis if it doesn't exist.
				$existing_analysis = get_post_meta( $post_id, '_meowseo_keyword_analysis', true );
				if ( empty( $existing_analysis ) ) {
					add_post_meta( $post_id, '_meowseo_keyword_analysis', '{}', true );
					$keyword_analysis_added++;
				}

				$processed++;
			}

			$offset += $batch_size;

			// Log progress every 500 posts.
			if ( $processed % 500 === 0 ) {
				error_log( "MeowSEO Migration 2.1.0: Processed {$processed}/{$total_posts} posts" );
			}
		}

		// Log migration completion.
		error_log( "MeowSEO Migration 2.1.0: Migration complete. Processed {$processed} posts, added {$secondary_keywords_added} secondary_keywords entries, {$keyword_analysis_added} keyword_analysis entries" );
	}

	/**
	 * Get current migration version
	 *
	 * @return string Current migration version.
	 */
	public static function get_version(): string {
		return get_option( self::VERSION_KEY, '0.0.0' );
	}

	/**
	 * Check if migration is needed
	 *
	 * @return bool True if migration is needed, false otherwise.
	 */
	public static function is_migration_needed(): bool {
		$current_version = self::get_version();
		return version_compare( $current_version, self::MIGRATION_VERSION, '<' );
	}
}
