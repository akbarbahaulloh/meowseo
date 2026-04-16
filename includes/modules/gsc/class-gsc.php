<?php
/**
 * GSC (Google Search Console) Module
 *
 * Integrates with Google Search Console API via rate-limited queue processing.
 * All API calls are enqueued and processed asynchronously via WP-Cron.
 *
 * @package    MeowSEO
 * @subpackage MeowSEO\Modules\GSC
 */

namespace MeowSEO\Modules\GSC;

use MeowSEO\Contracts\Module;
use MeowSEO\Helpers\DB;
use MeowSEO\Options;

defined( 'ABSPATH' ) || exit;

/**
 * GSC Module class
 *
 * Implements Google Search Console integration with rate-limited API queue.
 */
class GSC implements Module {

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * REST API handler instance.
	 *
	 * @var GSC_REST
	 */
	private GSC_REST $rest;

	/**
	 * GSC Auth instance.
	 *
	 * @var GSC_Auth
	 */
	private GSC_Auth $auth;

	/**
	 * GSC API instance.
	 *
	 * @var GSC_API
	 */
	private GSC_API $api;

	/**
	 * GSC Queue instance.
	 *
	 * @var GSC_Queue
	 */
	private GSC_Queue $queue;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
		$this->auth    = new GSC_Auth( $options );
		$this->api     = new GSC_API( $this->auth );
		$this->queue   = new GSC_Queue( $options, $this->api );
		$this->rest    = new GSC_REST( $options, $this->auth );
	}

	/**
	 * Boot the module.
	 *
	 * Register hooks for GSC queue processing, automatic indexing, and REST API.
	 * Requirement 10.1: Register hooks for transition_post_status.
	 * Requirement 10.3: Register cron for queue processing.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register custom cron interval for 5 minutes.
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

		// Register hook for automatic indexing on post publish (Requirement 11.1).
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 10, 3 );

		// Register WP-Cron hook for queue processing (Requirement 10.3).
		add_action( 'meowseo_gsc_process_queue', array( $this, 'process_queue' ) );

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register cron schedule.
		$this->register_cron();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->rest->register_routes();
	}

	/**
	 * Get module ID.
	 *
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return 'gsc';
	}

	/**
	 * Register custom cron interval.
	 *
	 * Adds a 5-minute interval for queue processing.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['meowseo_five_minutes'] = array(
			'interval' => 300, // 5 minutes in seconds.
			'display'  => __( 'Every 5 Minutes', 'meowseo' ),
		);

		return $schedules;
	}

	/**
	 * Register cron schedule for queue processing.
	 *
	 * Schedules WP-Cron event to process queue every 5 minutes.
	 * Requirement 10.3: Schedule queue processing every 5 minutes.
	 *
	 * @return void
	 */
	public function register_cron(): void {
		// Schedule cron event if not already scheduled.
		if ( ! wp_next_scheduled( 'meowseo_gsc_process_queue' ) ) {
			// Schedule to run every 5 minutes.
			wp_schedule_event( time(), 'meowseo_five_minutes', 'meowseo_gsc_process_queue' );
		}
	}

	/**
	 * Handle post status transition.
	 *
	 * Enqueues indexing job when post transitions to 'publish' or when published post is updated.
	 * Requirement 11.1: Enqueue indexing job when post transitions to 'publish' from any other status.
	 * Requirement 11.2: Check _meowseo_gsc_last_submit postmeta for published post updates.
	 * Requirement 11.3: Enqueue new job and update postmeta timestamp if modified since last submission.
	 * Requirement 11.4: Only process public and indexable post types.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_post_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only process when transitioning to 'publish' status.
		if ( 'publish' !== $new_status ) {
			return;
		}

		// Check if post type is public and indexable (Requirement 11.4).
		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object || ! $post_type_object->public ) {
			return;
		}

		// Get post permalink.
		$permalink = get_permalink( $post->ID );
		if ( ! $permalink ) {
			return;
		}

		// Case 1: Post transitioning to 'publish' from any other status (Requirement 11.1).
		if ( 'publish' !== $old_status ) {
			// Enqueue indexing job.
			$this->queue->enqueue( $permalink, 'indexing' );

			// Update last submit timestamp.
			update_post_meta( $post->ID, '_meowseo_gsc_last_submit', time() );

			return;
		}

		// Case 2: Published post being updated (Requirement 11.2, 11.3).
		// Check if post has been modified since last submission.
		$last_submit = get_post_meta( $post->ID, '_meowseo_gsc_last_submit', true );
		$modified_time = strtotime( $post->post_modified_gmt );

		// If never submitted or modified since last submission, enqueue new job.
		if ( empty( $last_submit ) || $modified_time > (int) $last_submit ) {
			// Enqueue indexing job.
			$this->queue->enqueue( $permalink, 'indexing' );

			// Update last submit timestamp.
			update_post_meta( $post->ID, '_meowseo_gsc_last_submit', time() );
		}
	}

	/**
	 * Process GSC queue.
	 *
	 * Delegates to GSC_Queue for batch processing.
	 * Requirement 10.3: Process queue batch.
	 * Requirement 10.4: Process up to 10 queue entries per batch.
	 *
	 * @return void
	 */
	public function process_queue(): void {
		$this->queue->process_batch();
	}

	/**
	 * Enqueue a GSC API call.
	 *
	 * Public method to add jobs to the queue.
	 *
	 * @param string $job_type Job type.
	 * @param array  $payload  Job payload.
	 * @return bool True on success, false on failure.
	 */
	public function enqueue_api_call( string $job_type, array $payload ): bool {
		// Extract URL from payload.
		$url = $payload['url'] ?? '';

		if ( empty( $url ) ) {
			return false;
		}

		return $this->queue->enqueue( $url, $job_type );
	}
}

