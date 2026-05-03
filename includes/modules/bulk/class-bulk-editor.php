<?php
/**
 * Bulk Editor class for performing bulk SEO operations.
 *
 * Handles bulk actions on multiple posts and CSV export functionality.
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Bulk;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Editor class.
 *
 * Manages bulk SEO operations on posts and pages.
 */
class Bulk_Editor implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Supported post types for bulk operations.
	 *
	 * @var array
	 */
	private array $supported_post_types = array( 'post', 'page' );

	/**
	 * Available bulk actions.
	 *
	 * @var array
	 */
	private array $bulk_actions = array(
		'meowseo_set_noindex' => 'Set noindex',
		'meowseo_set_index' => 'Set index',
		'meowseo_set_nofollow' => 'Set nofollow',
		'meowseo_set_follow' => 'Set follow',
		'meowseo_remove_canonical' => 'Remove canonical URL',
		'meowseo_set_schema_article' => 'Set schema to Article',
		'meowseo_set_schema_none' => 'Set schema to None',
		'meowseo_generate_ai_meta' => 'MeowSEO: Generate SEO Meta via AI',
	);

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Boot the module.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register bulk actions for all supported post types.
		foreach ( $this->supported_post_types as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'register_bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_action' ), 10, 3 );
		}

		// Add admin notices for bulk action results.
		add_action( 'admin_notices', array( $this, 'display_bulk_action_notice' ) );

		// Register CSV export functionality.
		add_action( 'admin_init', array( $this, 'handle_csv_export' ) );

		// Enqueue JS for bulk AI generation.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_bulk_scripts' ) );
	}

	/**
	 * Enqueue scripts for the bulk editor.
	 *
	 * @param string $hook_suffix Hook suffix.
	 */
	public function enqueue_bulk_scripts( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'meowseo-bulk-editor',
			\MEOWSEO_URL . 'assets/js/bulk-editor.js',
			array( 'jquery', 'wp-api-fetch' ),
			\MEOWSEO_VERSION,
			true
		);

		wp_localize_script(
			'meowseo-bulk-editor',
			'meowseoBulk',
			array(
				'restUrl' => esc_url_raw( rest_url( 'meowseo/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'strings' => array(
					'processing' => __( 'Generating AI Meta... Please do not close this page.', 'meowseo' ),
					'completed'  => __( 'Bulk AI generation completed!', 'meowseo' ),
					'failed'     => __( 'Failed to generate meta for some posts.', 'meowseo' ),
				),
			)
		);
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'bulk';
	}

	/**
	 * Register bulk actions in post list table.
	 *
	 * Validates: Requirement 5.1
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Updated bulk actions.
	 */
	public function register_bulk_actions( array $bulk_actions ): array {
		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $bulk_actions;
		}

		// Add MeowSEO bulk actions.
		return array_merge( $bulk_actions, $this->bulk_actions );
	}

	/**
	 * Handle bulk action execution.
	 *
	 * Validates: Requirements 5.2, 5.3, 5.9
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $action       The bulk action name.
	 * @param array  $post_ids     Array of post IDs.
	 * @return string Updated redirect URL with result parameters.
	 */
	public function handle_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
		// Check if this is a MeowSEO bulk action.
		if ( ! isset( $this->bulk_actions[ $action ] ) ) {
			return $redirect_url;
		}

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $redirect_url;
		}

		// Sanitize post IDs.
		$post_ids = array_map( 'intval', $post_ids );

		// Execute the bulk action.
		$count = $this->apply_bulk_action( $action, $post_ids );

		// Log the operation.
		Logger::info(
			'Bulk action executed: ' . $action,
			array(
				'action' => $action,
				'post_count' => $count,
				'post_ids' => $post_ids,
			)
		);

		// Add result parameters to redirect URL.
		$redirect_url = add_query_arg(
			array(
				'meowseo_bulk_action' => $action,
				'meowseo_bulk_count' => $count,
			),
			$redirect_url
		);

		return $redirect_url;
	}

	/**
	 * Apply bulk action to posts.
	 *
	 * @param string $action   The bulk action name.
	 * @param array  $post_ids Array of post IDs.
	 * @return int Number of posts modified.
	 */
	private function apply_bulk_action( string $action, array $post_ids ): int {
		$count = 0;

		foreach ( $post_ids as $post_id ) {
			// Verify post exists and user can edit it.
			if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			switch ( $action ) {
				case 'meowseo_set_noindex':
					update_post_meta( $post_id, '_meowseo_noindex', '1' );
					$count++;
					break;

				case 'meowseo_set_index':
					delete_post_meta( $post_id, '_meowseo_noindex' );
					$count++;
					break;

				case 'meowseo_set_nofollow':
					update_post_meta( $post_id, '_meowseo_nofollow', '1' );
					$count++;
					break;

				case 'meowseo_set_follow':
					delete_post_meta( $post_id, '_meowseo_nofollow' );
					$count++;
					break;

				case 'meowseo_remove_canonical':
					delete_post_meta( $post_id, '_meowseo_canonical_url' );
					$count++;
					break;

				case 'meowseo_set_schema_article':
					update_post_meta( $post_id, '_meowseo_schema_type', 'Article' );
					$count++;
					break;

				case 'meowseo_set_schema_none':
					delete_post_meta( $post_id, '_meowseo_schema_type' );
					$count++;
					break;
			}
		}

		return $count;
	}

	/**
	 * Display admin notice for bulk action results.
	 *
	 * @return void
	 */
	public function display_bulk_action_notice(): void {
		// Check if we're on the post list page.
		if ( ! isset( $_GET['meowseo_bulk_action'] ) || ! isset( $_GET['meowseo_bulk_count'] ) ) {
			return;
		}

		// Verify nonce (WordPress adds this automatically for bulk actions).
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['meowseo_bulk_action'] ) );
		$count = intval( $_GET['meowseo_bulk_count'] );

		// Get action label.
		$action_label = $this->bulk_actions[ $action ] ?? $action;

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %1$s is the action name, %2$d is the number of posts */
						_n(
							'%1$s applied to %2$d post.',
							'%1$s applied to %2$d posts.',
							$count,
							'meowseo'
						),
						$action_label,
						$count
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle CSV export request.
	 *
	 * Validates: Requirements 5.4, 5.5, 5.6, 5.7
	 *
	 * @return void
	 */
	public function handle_csv_export(): void {
		// Check if CSV export is requested.
		if ( ! isset( $_GET['meowseo_export_csv'] ) ) {
			return;
		}

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'meowseo_export_csv' ) ) {
			wp_die( 'Nonce verification failed' );
		}

		// Get post type filter.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';

		// Validate post type.
		if ( ! in_array( $post_type, $this->supported_post_types, true ) ) {
			$post_type = 'post';
		}

		// Get posts for export.
		$posts = get_posts(
			array(
				'post_type' => $post_type,
				'posts_per_page' => -1,
				'post_status' => 'publish',
			)
		);

		// Generate CSV.
		$csv = $this->export_to_csv( $posts );

		// Set headers for CSV download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="meowseo-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output CSV.
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Export posts to CSV format.
	 *
	 * Validates: Requirements 5.4, 5.5, 5.6, 5.7
	 *
	 * @param array $posts Array of WP_Post objects.
	 * @return string CSV formatted string.
	 */
	public function export_to_csv( array $posts ): string {
		$csv_generator = new CSV_Generator();

		// Add header row.
		$csv_generator->add_row(
			array(
				'ID',
				'Title',
				'URL',
				'Focus Keyword',
				'Meta Description',
				'SEO Score',
				'Noindex',
				'Nofollow',
				'Canonical URL',
				'Schema Type',
			)
		);

		// Add data rows.
		foreach ( $posts as $post ) {
			$noindex = get_post_meta( $post->ID, '_meowseo_noindex', true );
			$nofollow = get_post_meta( $post->ID, '_meowseo_nofollow', true );
			$canonical = get_post_meta( $post->ID, '_meowseo_canonical_url', true );
			$schema_type = get_post_meta( $post->ID, '_meowseo_schema_type', true );
			$focus_keyword = get_post_meta( $post->ID, '_meowseo_focus_keyword', true );
			$description = get_post_meta( $post->ID, '_meowseo_description', true );

			// Calculate SEO score (placeholder - would use actual analysis engine).
			$seo_score = $this->calculate_seo_score( $post->ID );

			$csv_generator->add_row(
				array(
					$post->ID,
					$post->post_title,
					get_permalink( $post->ID ),
					$focus_keyword ?: '',
					$description ?: '',
					$seo_score,
					$noindex ? 'Yes' : 'No',
					$nofollow ? 'Yes' : 'No',
					$canonical ?: '',
					$schema_type ?: 'None',
				)
			);
		}

		return $csv_generator->generate();
	}

	/**
	 * Calculate SEO score for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int SEO score (0-100).
	 */
	private function calculate_seo_score( int $post_id ): int {
		// Placeholder implementation - would use actual analysis engine.
		$score = 0;

		// Check for focus keyword.
		if ( get_post_meta( $post_id, '_meowseo_focus_keyword', true ) ) {
			$score += 20;
		}

		// Check for meta description.
		if ( get_post_meta( $post_id, '_meowseo_description', true ) ) {
			$score += 20;
		}

		// Check for title.
		$post = get_post( $post_id );
		if ( $post && $post->post_title ) {
			$score += 20;
		}

		// Check for content.
		if ( $post && strlen( $post->post_content ) > 300 ) {
			$score += 20;
		}

		// Check for schema.
		if ( get_post_meta( $post_id, '_meowseo_schema_type', true ) ) {
			$score += 20;
		}

		return min( $score, 100 );
	}

	/**
	 * Get supported post types.
	 *
	 * Validates: Requirement 5.8
	 *
	 * @return array Array of supported post types.
	 */
	public function get_supported_post_types(): array {
		return $this->supported_post_types;
	}

	/**
	 * Add custom post type to supported types.
	 *
	 * @param string $post_type Post type to add.
	 * @return bool True on success, false on failure.
	 */
	public function add_supported_post_type( string $post_type ): bool {
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		if ( ! in_array( $post_type, $this->supported_post_types, true ) ) {
			$this->supported_post_types[] = $post_type;
		}

		return true;
	}
}
