<?php
/**
 * Batch Processor class for chunked data processing.
 *
 * Processes large datasets in chunks to prevent PHP timeouts and memory exhaustion.
 * Uses WP_Query with pagination and tracks progress in transients.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Import;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Batch_Processor class.
 *
 * Processes large datasets in chunks to prevent timeout and memory issues.
 */
class Batch_Processor {

	/**
	 * Batch size (number of items per batch).
	 *
	 * @var int
	 */
	private int $batch_size;

	/**
	 * Current progress data.
	 *
	 * @var array
	 */
	private array $progress = array(
		'processed' => 0,
		'total'     => 0,
		'page'      => 1,
	);

	/**
	 * Constructor.
	 *
	 * @param int $batch_size Batch size (default: 100).
	 */
	public function __construct( int $batch_size = 100 ) {
		/**
		 * Filter the batch size for import processing.
		 *
		 * @param int $batch_size Batch size (default: 100).
		 */
		$this->batch_size = \apply_filters( 'meowseo_import_batch_size', $batch_size );
	}

	/**
	 * Process posts in batches.
	 *
	 * Iterates through all posts using WP_Query with pagination.
	 *
	 * @param callable $callback Callback function to process each post.
	 *                           Receives post ID as parameter.
	 *                           Should return true on success, false on failure.
	 * @param array    $args     WP_Query arguments (optional).
	 * @return array Processing results with structure:
	 *               [
	 *                   'processed' => 150,
	 *                   'total' => 500,
	 *                   'errors' => 3,
	 *               ]
	 */
	public function process_posts( callable $callback, array $args = array() ): array {
		// Default query args.
		$default_args = array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => $this->batch_size,
			'paged'          => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		);

		$query_args = array_merge( $default_args, $args );

		// Query current batch.
		$query = new \WP_Query( $query_args );
		$total = $query->found_posts;
		$processed = 0;
		$errors = 0;

		// Process batch.
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$result = call_user_func( $callback, $post_id );
				if ( false === $result ) {
					$errors++;
				}
				$processed++;
			}
		}

		$next_page = $query_args['paged'] + 1;
		$is_done = ( $query->max_num_pages <= $query_args['paged'] ) || ( $total === 0 );

		return array(
			'processed' => $processed,
			'total'     => $total,
			'errors'    => $errors,
			'next_page' => $next_page,
			'is_done'   => $is_done,
		);
	}

	/**
	 * Process terms in batches.
	 *
	 * Iterates through terms using get_terms with pagination for a single batch.
	 *
	 * @param callable $callback Callback function to process each term.
	 *                           Receives term ID as parameter.
	 *                           Should return true on success, false on failure.
	 * @param array    $args     get_terms arguments (optional).
	 * @return array Processing results with structure:
	 *               [
	 *                   'processed' => 50,
	 *                   'total' => 100,
	 *                   'errors' => 1,
	 *                   'next_offset' => 50,
	 *                   'is_done' => false
	 *               ]
	 */
	public function process_terms( callable $callback, array $args = array() ): array {
		// Default query args.
		$default_args = array(
			'taxonomy'   => 'category', // Base_Importer overrides this with all taxonomies
			'hide_empty' => false,
			'number'     => $this->batch_size,
			'offset'     => 0,
			'fields'     => 'ids',
		);

		$query_args = array_merge( $default_args, $args );

		// Get total count.
		$count_args              = $query_args;
		$count_args['number']    = 0;
		$count_args['offset']    = 0;
		$count_args['count']     = true;
		$total = \get_terms( $count_args );
		
		if ( \is_wp_error( $total ) ) {
			$total = 0;
		}

		$processed = 0;
		$errors = 0;
		
		// Query current batch.
		$term_ids = \get_terms( $query_args );

		if ( ! empty( $term_ids ) && ! \is_wp_error( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				$result = call_user_func( $callback, $term_id );
				if ( false === $result ) {
					$errors++;
				}
				$processed++;
			}
		}

		$next_offset = $query_args['offset'] + $this->batch_size;
		// Determine if done: if returned items < requested number, or total is 0.
		$is_done = ( count( $term_ids ) < $this->batch_size ) || ( $total === 0 );

		return array(
			'processed'   => $processed,
			'total'       => $total,
			'errors'      => $errors,
			'next_offset' => $next_offset,
			'is_done'     => $is_done,
		);
	}

	/**
	 * Get current progress.
	 *
	 * @return array Progress data with keys: processed, total, errors.
	 */
	public function get_progress(): array {
		return $this->progress;
	}

	/**
	 * Get batch size.
	 *
	 * @return int Batch size.
	 */
	public function get_batch_size(): int {
		return $this->batch_size;
	}

	/**
	 * Set batch size.
	 *
	 * @param int $batch_size Batch size.
	 * @return void
	 */
	public function set_batch_size( int $batch_size ): void {
		$this->batch_size = max( 1, $batch_size );
	}
}
