<?php
/**
 * MeowIndex Post Actions
 *
 * Adds "Submit to IndexNow" and "Submit to Google" row action links and
 * bulk actions to WordPress post list tables. Mirrors the RankMath Instant
 * Indexing plugin's approach but uses native MeowSEO structures.
 *
 * @package MeowSEO
 * @since 1.0.0
 */

namespace MeowSEO\Modules\MeowIndex;

use MeowSEO\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MeowIndex_Post_Actions class
 *
 * Registers row actions and bulk actions on post list screens that let
 * administrators manually trigger instant-indexing submissions.
 *
 * @since 1.0.0
 */
class MeowIndex_Post_Actions {

	/**
	 * Nonce action for row-action requests.
	 */
	private const ROW_NONCE_ACTION = 'meowseo_meowindex_row_action';

	/**
	 * Options instance.
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * MeowIndexClient instance.
	 *
	 * @var MeowIndexClient
	 */
	private MeowIndexClient $client;

	/**
	 * Submission_Logger instance.
	 *
	 * @var Submission_Logger
	 */
	private Submission_Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param Options           $options Options instance.
	 * @param MeowIndexClient   $client  Indexing client.
	 * @param Submission_Logger $logger  Submission logger.
	 */
	public function __construct( Options $options, MeowIndexClient $client, Submission_Logger $logger ) {
		$this->options = $options;
		$this->client  = $client;
		$this->logger  = $logger;
	}

	/**
	 * Register all post list hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only do anything if at least one indexing engine is enabled.
		if ( ! $this->client->is_enabled() && ! $this->client->is_google_enabled() ) {
			return;
		}

		// Row action links.
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_row_actions' ), 10, 2 );

		// Handle row action request (admin_action_ hook).
		add_action( 'admin_action_meowseo_meowindex_submit_row', array( $this, 'handle_row_action' ) );

		// Bulk actions.
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $post_types as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'register_bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_actions' ), 10, 3 );
		}

		// Admin notice after bulk/row action.
		add_action( 'admin_notices', array( $this, 'display_action_notice' ) );
	}

	// -------------------------------------------------------------------------
	// Row actions
	// -------------------------------------------------------------------------

	/**
	 * Add MeowIndex row action links to the post list table.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Post $post    Current post.
	 * @return array Modified row actions.
	 */
	public function add_row_actions( array $actions, \WP_Post $post ): array {
		// Only for published posts.
		if ( 'publish' !== $post->post_status ) {
			return $actions;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$nonce = wp_create_nonce( self::ROW_NONCE_ACTION );
		$base  = array(
			'action'   => 'meowseo_meowindex_submit_row',
			'post_id'  => $post->ID,
			'_wpnonce' => $nonce,
		);

		// IndexNow link.
		if ( $this->client->is_enabled() && $this->client->is_post_type_allowed( $post->post_type, 'indexnow' ) ) {
			$url = add_query_arg( array_merge( $base, array( 'api' => 'indexnow' ) ), admin_url( 'admin.php' ) );
			$actions['meowindex_indexnow'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Index (IndexNow)', 'meowseo' )
			);
		}

		// Google link.
		if ( $this->client->is_google_enabled() && $this->client->is_post_type_allowed( $post->post_type, 'google' ) ) {
			$url = add_query_arg( array_merge( $base, array( 'api' => 'google' ) ), admin_url( 'admin.php' ) );
			$actions['meowindex_google'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Index (Google)', 'meowseo' )
			);
		}

		return $actions;
	}

	/**
	 * Handle a row action click (admin_action_ hook).
	 *
	 * Submits a single post URL to the requested API, then redirects back.
	 *
	 * @return void
	 */
	public function handle_row_action(): void {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), self::ROW_NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'meowseo' ), '', array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'meowseo' ), '', array( 'response' => 403 ) );
		}

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$api     = isset( $_GET['api'] ) ? sanitize_key( $_GET['api'] ) : '';
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_safe_redirect( add_query_arg( 'meowindex_notice', 'invalid_post', wp_get_referer() ) );
			exit;
		}

		$url = get_permalink( $post );
		if ( ! $url ) {
			wp_safe_redirect( add_query_arg( 'meowindex_notice', 'no_url', wp_get_referer() ) );
			exit;
		}

		$submitted = false;

		if ( 'indexnow' === $api && $this->client->is_enabled() ) {
			$result    = $this->client->submit_urls( array( $url ), 'URL_UPDATED' );
			$submitted = true;
		} elseif ( 'google' === $api && $this->client->is_google_enabled() ) {
			$result    = $this->client->submit_urls( array( $url ), 'URL_UPDATED' );
			$submitted = true;
		}

		$notice = $submitted ? 'submitted_1' : 'not_submitted';

		wp_safe_redirect(
			add_query_arg(
				array(
					'meowindex_notice' => $notice,
					'meowindex_api'    => $api,
					'post_type'        => $post->post_type,
				),
				wp_get_referer() ?: admin_url( 'edit.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Bulk actions
	// -------------------------------------------------------------------------

	/**
	 * Register MeowIndex bulk actions.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function register_bulk_actions( array $bulk_actions ): array {
		if ( $this->client->is_enabled() ) {
			$bulk_actions['meowindex_indexnow_submit'] = __( 'MeowIndex: Submit to IndexNow', 'meowseo' );
		}

		if ( $this->client->is_google_enabled() ) {
			$bulk_actions['meowindex_google_update'] = __( 'MeowIndex: Submit to Google', 'meowseo' );
		}

		return $bulk_actions;
	}

	/**
	 * Handle bulk actions for MeowIndex submissions.
	 *
	 * @param string $redirect_url Redirect URL after action.
	 * @param string $doaction     Action being performed.
	 * @param int[]  $post_ids     Array of selected post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( string $redirect_url, string $doaction, array $post_ids ): string {
		if ( ! in_array( $doaction, array( 'meowindex_indexnow_submit', 'meowindex_google_update' ), true ) ) {
			return $redirect_url;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return $redirect_url;
		}

		$api  = ( 'meowindex_indexnow_submit' === $doaction ) ? 'indexnow' : 'google';
		$urls = array();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( absint( $post_id ) );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			if ( ! $this->client->is_post_type_allowed( $post->post_type, $api ) ) {
				continue;
			}

			$url = get_permalink( $post );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		$submitted = 0;

		if ( ! empty( $urls ) ) {
			// Submit in batches of 10 (IndexNow API limit).
			$batches = array_chunk( $urls, 10 );
			foreach ( $batches as $batch ) {
				$result    = $this->client->submit_urls( $batch, 'URL_UPDATED' );
				$submitted += count( $batch );
			}
		}

		$redirect_url = remove_query_arg( array( 'meowindex_notice', 'meowindex_api' ), $redirect_url );
		$redirect_url = add_query_arg(
			array(
				'meowindex_notice' => 'submitted_' . $submitted,
				'meowindex_api'    => $api,
			),
			$redirect_url
		);

		return $redirect_url;
	}

	// -------------------------------------------------------------------------
	// Admin notice
	// -------------------------------------------------------------------------

	/**
	 * Display admin notice after a row or bulk action.
	 *
	 * @return void
	 */
	public function display_action_notice(): void {
		if ( ! isset( $_GET['meowindex_notice'] ) ) {
			return;
		}

		$raw_notice = sanitize_key( $_GET['meowindex_notice'] );
		$api        = isset( $_GET['meowindex_api'] ) ? sanitize_key( $_GET['meowindex_api'] ) : '';
		$api_label  = ( 'google' === $api ) ? 'Google' : 'IndexNow';

		if ( str_starts_with( $raw_notice, 'submitted_' ) ) {
			$count = absint( str_replace( 'submitted_', '', $raw_notice ) );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: count, 2: API name */
						_n(
							'MeowIndex: %1$d URL submitted to %2$s.',
							'MeowIndex: %1$d URLs submitted to %2$s.',
							$count,
							'meowseo'
						),
						$count,
						$api_label
					)
				)
			);
			return;
		}

		$error_messages = array(
			'invalid_post'  => __( 'MeowIndex: Post not found or not published.', 'meowseo' ),
			'no_url'        => __( 'MeowIndex: Could not retrieve post URL.', 'meowseo' ),
			'not_submitted' => __( 'MeowIndex: Submission failed — check that the API is enabled in settings.', 'meowseo' ),
		);

		if ( isset( $error_messages[ $raw_notice ] ) ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $error_messages[ $raw_notice ] )
			);
		}
	}
}
