<?php
/**
 * MeowIndex Module
 *
 * Manages instant URL indexing via IndexNow and Google Indexing API.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

use MeowSEO\Contracts\Module;
use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MeowIndex module class
 *
 * Implements the Module interface to provide instant indexing.
 *
 * @since 1.0.0
 */
class MeowIndex implements Module {

	/**
	 * Module ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const MODULE_ID = 'meowindex';

	/**
	 * Options instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private Options $options;

	/**
	 * MeowIndexClient instance
	 *
	 * @since 1.0.0
	 * @var MeowIndexClient
	 */
	private MeowIndexClient $client;

	/**
	 * Submission_Queue instance
	 *
	 * @since 1.0.0
	 * @var Submission_Queue
	 */
	private Submission_Queue $queue;

	/**
	 * Submission_Logger instance
	 *
	 * @since 1.0.0
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		// Initialize queue and logger.
		$this->queue  = new Submission_Queue( $options );
		$this->logger = new Submission_Logger();

		// Initialize client.
		$this->client = new MeowIndexClient( $options, $this->queue, $this->logger );
	}

	/**
	 * Boot the module
	 *
	 * Register hooks and initialize module functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Register custom cron interval (10 seconds).
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

		// Initialize verification handler.
		$verification_handler = new Verification_Handler( $this->options );
		$verification_handler->register();

		// Boot the MeowIndexClient.
		$this->client->boot();

		// Register post row actions and bulk actions (admin only).
		if ( is_admin() ) {
			$post_actions = new MeowIndex_Post_Actions(
				$this->options,
				$this->client,
				$this->logger
			);
			$post_actions->register();
		}
	}

	/**
	 * Register custom cron interval
	 *
	 * Registers a 10-second cron interval for queue processing.
	 *
	 * @since 1.0.0
	 * @param array $schedules Existing cron schedules.
	 * @return array Updated cron schedules.
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['meowseo_meowindex_interval'] = array(
			'interval' => 10,
			'display'  => __( 'Every 10 seconds', 'meowseo' ),
		);

		return $schedules;
	}

	/**
	 * Get module ID
	 *
	 * @since 1.0.0
	 * @return string Module ID.
	 */
	public function get_id(): string {
		return self::MODULE_ID;
	}

	/**
	 * Get client instance
	 *
	 * @since 1.0.0
	 * @return MeowIndexClient Client instance.
	 */
	public function get_client(): MeowIndexClient {
		return $this->client;
	}

	/**
	 * Get queue instance
	 *
	 * @since 1.0.0
	 * @return Submission_Queue Queue instance.
	 */
	public function get_queue(): Submission_Queue {
		return $this->queue;
	}

	/**
	 * Get logger instance
	 *
	 * @since 1.0.0
	 * @return Submission_Logger Logger instance.
	 */
	public function get_logger(): Submission_Logger {
		return $this->logger;
	}
}
