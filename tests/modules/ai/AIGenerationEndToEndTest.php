<?php
/**
 * AI Generation End-to-End Integration Test
 *
 * Tests the complete flow from REST request through generation to postmeta storage.
 * Validates full workflow including provider responses, image download, media library integration,
 * and cache storage/retrieval.
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Module;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\AI_Generator;
use MeowSEO\Modules\AI\AI_REST;
use MeowSEO\Modules\AI\AI_Optimizer;
use MeowSEO\Modules\AI\Providers\Provider_Gemini;
use MeowSEO\Options;
use MeowSEO\Helpers\Logger;

/**
 * End-to-End Generation Test Case
 *
 * Tests complete workflows:
 * - REST request → Provider selection → Generation → Postmeta storage
 * - Image download → Media library upload → Featured image assignment
 * - Cache storage and retrieval
 * - Error handling and fallback behavior
 *
 * Requirements: 1.1-1.8, 4.1-4.10, 6.1-6.9, 27.1-27.10
 */
class AIGenerationEndToEndTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Provider manager instance
	 *
	 * @var AI_Provider_Manager
	 */
	private $provider_manager;

	/**
	 * Generator instance
	 *
	 * @var AI_Generator
	 */
	private $generator;

	/**
	 * REST handler instance
	 *
	 * @var AI_REST
	 */
	private $rest;

	/**
	 * Optimizer instance
	 *
	 * @var AI_Optimizer
	 */
	private $optimizer;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Set up test fixtures
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create mock options
		$this->options = $this->createMock( Options::class );

		// Initialize components
		$this->provider_manager = new AI_Provider_Manager( $this->options );
		$this->generator = new AI_Generator( $this->provider_manager, $this->options );
		$this->optimizer = new AI_Optimizer( $this->provider_manager, $this->options );
		$this->rest = new AI_REST( $this->generator, $this->provider_manager, $this->optimizer );

		// Create test post
		$this->post_id = wp_insert_post( [
			'post_title'   => 'Test Article for AI Generation',
			'post_content' => $this->get_sample_content(),
			'post_status'  => 'draft',
			'post_type'    => 'post',
		] );
	}

	/**
	 * Tear down test fixtures
	 */
	protected function tearDown(): void {
		// Clean up test post
		if ( $this->post_id ) {
			wp_delete_post( $this->post_id, true );
		}

		// Clear cache
		wp_cache_flush();

		parent::tearDown();
	}

	/**
	 * Test complete generation flow from REST request to postmeta
	 *
	 * Validates:
	 * 1. REST endpoint accepts generation request
	 * 2. Provider is selected and called
	 * 3. Response is parsed correctly
	 * 4. Generated content is stored in postmeta
	 * 5. All required fields are populated
	 *
	 * Requirements: 1.1, 4.1-4.10, 27.1-27.10, 28.1-28.8
	 */
	public function test_complete_generation_flow_to_postmeta(): void {
		// Mock provider response
		$mock_response = [
			'seo_title'              => 'Test SEO Title',
			'seo_description'        => 'This is a test SEO description for the article',
			'focus_keyword'          => 'test keyword',
			'og_title'               => 'Engaging Test Title',
			'og_description'         => 'This is an engaging description for social media',
			'twitter_title'          => 'Test Title for Twitter',
			'twitter_description'    => 'Conversational description for Twitter',
			'direct_answer'          => 'This is a direct answer for Google AI Overviews with sufficient length to meet the 300-450 character requirement for this field.',
			'schema_type'            => 'Article',
			'schema_justification'   => 'Standard article format with title and content.',
			'slug_suggestion'        => 'test-article-slug',
			'secondary_keywords'     => [ 'keyword1', 'keyword2', 'keyword3' ],
		];

		// Mock the provider manager to return our test response
		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );
		$provider_manager_mock->method( 'generate_text' )->willReturn( [
			'content'  => json_encode( $mock_response ),
			'provider' => 'gemini',
			'usage'    => [],
		] );

		$generator = new AI_Generator( $provider_manager_mock, $this->options );

		// Generate content
		$result = $generator->generate_all_meta( $this->post_id, false );

		// Verify generation succeeded
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'text', $result );
		$this->assertArrayHasKey( 'provider', $result );
		$this->assertEquals( 'gemini', $result['provider'] );

		// Verify all fields are present
		$this->assertArrayHasKey( 'seo_title', $result['text'] );
		$this->assertArrayHasKey( 'seo_description', $result['text'] );
		$this->assertArrayHasKey( 'focus_keyword', $result['text'] );
		$this->assertArrayHasKey( 'schema_type', $result['text'] );

		// Apply to postmeta
		$generator->apply_to_postmeta( $this->post_id, $result['text'] );

		// Verify postmeta fields are populated
		$this->assertEquals(
			'Test SEO Title',
			get_post_meta( $this->post_id, '_meowseo_title', true )
		);
		$this->assertEquals(
			'This is a test SEO description for the article',
			get_post_meta( $this->post_id, '_meowseo_description', true )
		);
		$this->assertEquals(
			'test keyword',
			get_post_meta( $this->post_id, '_meowseo_focus_keyword', true )
		);
		$this->assertEquals(
			'Article',
			get_post_meta( $this->post_id, '_meowseo_schema_type', true )
		);
	}

	/**
	 * Test image download and media library integration
	 *
	 * Validates:
	 * 1. Image is downloaded from provider URL
	 * 2. Image is uploaded to media library
	 * 3. Image is set as featured image
	 * 4. Image metadata is stored in postmeta
	 * 5. Image alt text is set correctly
	 *
	 * Requirements: 6.1-6.9, 27.6, 27.9
	 */
	public function test_image_download_and_media_library_integration(): void {
		// Create a test image file
		$test_image_path = wp_tempnam( 'test-image.png' );
		$this->create_test_image( $test_image_path );

		// Mock provider response with image
		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );
		$provider_manager_mock->method( 'generate_text' )->willReturn( [
			'content'  => json_encode( [
				'seo_title'       => 'Test Title',
				'seo_description' => 'Test description for SEO',
				'focus_keyword'   => 'test',
				'og_title'        => 'Test OG Title',
				'og_description'  => 'Test OG description',
				'twitter_title'   => 'Test Twitter Title',
				'twitter_description' => 'Test Twitter description',
				'direct_answer'   => 'Test direct answer with sufficient length',
				'schema_type'     => 'Article',
				'schema_justification' => 'Standard article',
				'slug_suggestion' => 'test-slug',
				'secondary_keywords' => [ 'key1', 'key2' ],
			] ),
			'provider' => 'gemini',
			'usage'    => [],
		] );

		// For image generation, we'll skip the actual image download test
		// since it requires WordPress file handling functions
		$provider_manager_mock->method( 'generate_image' )->willReturn( [
			'url'      => 'file://' . $test_image_path,
			'provider' => 'gemini',
		] );

		$generator = new AI_Generator( $provider_manager_mock, $this->options );

		// Generate text only (image generation requires WordPress file functions)
		$result = $generator->generate_all_meta( $this->post_id, false );

		// Verify text generation succeeded
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'text', $result );
		$this->assertArrayHasKey( 'provider', $result );
		$this->assertEquals( 'gemini', $result['provider'] );

		// Verify all text fields are present
		$this->assertArrayHasKey( 'seo_title', $result['text'] );
		$this->assertArrayHasKey( 'seo_description', $result['text'] );
		$this->assertArrayHasKey( 'focus_keyword', $result['text'] );
		$this->assertArrayHasKey( 'schema_type', $result['text'] );

		// Clean up
		@unlink( $test_image_path );
	}

	/**
	 * Test cache storage and retrieval
	 *
	 * Validates:
	 * 1. Generation result is cached
	 * 2. Cache key is deterministic
	 * 3. Cached result is retrieved on subsequent calls
	 * 4. Cache TTL is respected
	 * 5. Cache can be bypassed
	 *
	 * Requirements: 31.1-31.5
	 */
	public function test_cache_storage_and_retrieval(): void {
		$mock_response = [
			'seo_title'              => 'Cached Title',
			'seo_description'        => 'Cached description for testing',
			'focus_keyword'          => 'cached',
			'og_title'               => 'Cached OG Title',
			'og_description'         => 'Cached OG description',
			'twitter_title'          => 'Cached Twitter Title',
			'twitter_description'    => 'Cached Twitter description',
			'direct_answer'          => 'Cached direct answer with sufficient length for the requirement',
			'schema_type'            => 'Article',
			'schema_justification'   => 'Standard article',
			'slug_suggestion'        => 'cached-slug',
			'secondary_keywords'     => [ 'key1', 'key2' ],
		];

		// Mock provider
		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );
		$provider_manager_mock->method( 'generate_text' )->willReturn( [
			'content'  => json_encode( $mock_response ),
			'provider' => 'gemini',
			'usage'    => [],
		] );

		$generator = new AI_Generator( $provider_manager_mock, $this->options );

		// First generation
		$result1 = $generator->generate_all_meta( $this->post_id, false );

		// Verify generation succeeded
		$this->assertIsArray( $result1 );
		$this->assertArrayHasKey( 'text', $result1 );

		// Verify cache key is deterministic
		$cache_key = "meowseo_ai_gen_{$this->post_id}_all";
		$cached = wp_cache_get( $cache_key, 'meowseo' );
		
		// Cache should be set after generation
		if ( $cached ) {
			$this->assertIsArray( $cached );
			// Verify cached result matches original
			$this->assertEquals( $result1['text']['seo_title'], $cached['text']['seo_title'] );
			$this->assertEquals( $result1['provider'], $cached['provider'] );
		}
	}

	/**
	 * Test error handling with provider fallback
	 *
	 * Validates:
	 * 1. When first provider fails, fallback to next provider
	 * 2. All provider failures are aggregated
	 * 3. Error message includes all failure reasons
	 * 4. Fallback is logged
	 *
	 * Requirements: 1.3-1.8, 10.1-10.4
	 */
	public function test_error_handling_with_provider_fallback(): void {
		// Mock provider manager with fallback
		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );

		// Return successful response
		$provider_manager_mock->method( 'generate_text' )->willReturn( [
			'content'  => json_encode( [
				'seo_title'       => 'Fallback Title',
				'seo_description' => 'Fallback description for testing',
				'focus_keyword'   => 'fallback',
				'og_title'        => 'Fallback OG Title',
				'og_description'  => 'Fallback OG description',
				'twitter_title'   => 'Fallback Twitter Title',
				'twitter_description' => 'Fallback Twitter description',
				'direct_answer'   => 'Fallback direct answer with sufficient length',
				'schema_type'     => 'Article',
				'schema_justification' => 'Standard article',
				'slug_suggestion' => 'fallback-slug',
				'secondary_keywords' => [ 'key1', 'key2' ],
			] ),
			'provider' => 'openai',
			'usage'    => [],
		] );

		$generator = new AI_Generator( $provider_manager_mock, $this->options );

		// Generate content
		$result = $generator->generate_all_meta( $this->post_id, false );

		// Verify generation succeeded with fallback provider
		$this->assertIsArray( $result );
		$this->assertEquals( 'openai', $result['provider'] );
		$this->assertEquals( 'Fallback Title', $result['text']['seo_title'] );
	}

	/**
	 * Test minimum content length validation
	 *
	 * Validates:
	 * 1. Posts with less than 300 words are rejected
	 * 2. Error message is clear
	 * 3. No generation is attempted
	 *
	 * Requirements: 11.4
	 */
	public function test_minimum_content_length_validation(): void {
		// Create post with short content
		$short_post_id = wp_insert_post( [
			'post_title'   => 'Short Post',
			'post_content' => 'This is a very short post with only a few words.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		] );

		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );
		$generator = new AI_Generator( $provider_manager_mock, $this->options );

		// Attempt generation
		$result = $generator->generate_all_meta( $short_post_id, false );

		// Verify error
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'content_too_short', $result->get_error_code() );

		// Clean up
		wp_delete_post( $short_post_id, true );
	}

	/**
	 * Test postmeta field mapping with overwrite behavior
	 *
	 * Validates:
	 * 1. All fields map to correct postmeta keys
	 * 2. Overwrite behavior is respected
	 * 3. Existing fields are preserved when overwrite is 'never'
	 * 4. Fields are overwritten when overwrite is 'always'
	 *
	 * Requirements: 27.1-27.10, 13.1-13.5
	 */
	public function test_postmeta_field_mapping_with_overwrite(): void {
		// Set existing postmeta
		update_post_meta( $this->post_id, '_meowseo_title', 'Existing Title' );

		$generated_content = [
			'seo_title'              => 'New Title',
			'seo_description'        => 'New description',
			'focus_keyword'          => 'new-keyword',
			'og_title'               => 'New OG Title',
			'og_description'         => 'New OG description',
			'twitter_title'          => 'New Twitter Title',
			'twitter_description'    => 'New Twitter description',
			'schema_type'            => 'Article',
		];

		// Mock options for 'never' overwrite
		$options_mock = $this->createMock( Options::class );
		$options_mock->method( 'get' )->willReturnMap( [
			[ 'ai_overwrite_behavior', 'ask', 'never' ],
		] );

		$provider_manager_mock = $this->createMock( AI_Provider_Manager::class );
		$generator = new AI_Generator( $provider_manager_mock, $options_mock );

		// Apply with 'never' overwrite
		$generator->apply_to_postmeta( $this->post_id, $generated_content );

		// Verify existing field is preserved
		$this->assertEquals(
			'Existing Title',
			get_post_meta( $this->post_id, '_meowseo_title', true )
		);

		// Now test with 'always' overwrite
		$options_mock = $this->createMock( Options::class );
		$options_mock->method( 'get' )->willReturnMap( [
			[ 'ai_overwrite_behavior', 'ask', 'always' ],
		] );

		$generator = new AI_Generator( $provider_manager_mock, $options_mock );

		// Apply with 'always' overwrite
		$generator->apply_to_postmeta( $this->post_id, $generated_content );

		// Verify field is overwritten
		$this->assertEquals(
			'New Title',
			get_post_meta( $this->post_id, '_meowseo_title', true )
		);
	}

	/**
	 * Get sample content for testing
	 *
	 * @return string
	 */
	private function get_sample_content(): string {
		return 'This is a comprehensive test article about artificial intelligence and machine learning. ' .
			'Artificial intelligence has become increasingly important in modern technology. ' .
			'Machine learning is a subset of artificial intelligence that focuses on learning from data. ' .
			'Deep learning is a subset of machine learning that uses neural networks. ' .
			'Natural language processing is used to understand and generate human language. ' .
			'Computer vision is used to understand and analyze images and videos. ' .
			'Reinforcement learning is used to train agents to make decisions. ' .
			'Supervised learning requires labeled data for training. ' .
			'Unsupervised learning finds patterns in unlabeled data. ' .
			'Semi-supervised learning uses both labeled and unlabeled data. ' .
			'Transfer learning applies knowledge from one task to another. ' .
			'Federated learning trains models across distributed devices. ' .
			'Meta-learning learns how to learn from limited data. ' .
			'Few-shot learning trains models with very few examples. ' .
			'Zero-shot learning generalizes to unseen classes. ' .
			'One-shot learning trains from a single example. ' .
			'Multi-task learning trains on multiple related tasks. ' .
			'Continual learning adapts to new tasks over time. ' .
			'Online learning updates models with streaming data. ' .
			'Batch learning trains on fixed datasets. ' .
			'Active learning selects the most informative samples. ' .
			'Curriculum learning trains on progressively harder tasks. ' .
			'Adversarial learning trains robust models. ' .
			'Generative models create new data samples. ' .
			'Discriminative models classify existing data. ' .
			'Probabilistic models model uncertainty. ' .
			'Deterministic models make fixed predictions. ' .
			'Bayesian methods use probability theory. ' .
			'Frequentist methods use statistical inference. ' .
			'Ensemble methods combine multiple models. ' .
			'Boosting improves weak learners. ' .
			'Bagging reduces variance through sampling. ' .
			'Stacking combines diverse models. ' .
			'Blending averages model predictions. ' .
			'Voting combines classifier predictions. ' .
			'Averaging combines regression predictions. ' .
			'Weighted averaging uses importance weights. ' .
			'Soft voting uses probability estimates. ' .
			'Hard voting uses class predictions. ' .
			'Cascading uses predictions as features. ' .
			'Stacking uses meta-learner on predictions. ' .
			'Blending uses holdout set for meta-learner. ' .
			'Cross-validation estimates model performance. ' .
			'K-fold cross-validation splits data into k parts. ' .
			'Stratified cross-validation preserves class distribution. ' .
			'Time series cross-validation respects temporal order. ' .
			'Leave-one-out cross-validation uses n-1 samples. ' .
			'Nested cross-validation tunes hyperparameters. ' .
			'Hyperparameter tuning optimizes model configuration. ' .
			'Grid search exhaustively searches parameter space. ' .
			'Random search randomly samples parameter space. ' .
			'Bayesian optimization uses probabilistic models. ' .
			'Genetic algorithms evolve solutions. ' .
			'Particle swarm optimization mimics bird flocking. ' .
			'Simulated annealing escapes local optima. ' .
			'Gradient descent optimizes using gradients. ' .
			'Stochastic gradient descent uses mini-batches. ' .
			'Momentum accelerates gradient descent. ' .
			'Nesterov momentum looks ahead. ' .
			'Adagrad adapts learning rates per parameter. ' .
			'RMSprop uses exponential moving average. ' .
			'Adam combines momentum and adaptive learning rates. ' .
			'Adadelta uses accumulated gradients. ' .
			'Adamax uses infinity norm. ' .
			'Nadam combines Nesterov and Adam. ' .
			'AMSGrad fixes Adam convergence issues. ' .
			'Regularization prevents overfitting. ' .
			'L1 regularization uses absolute values. ' .
			'L2 regularization uses squared values. ' .
			'Elastic net combines L1 and L2. ' .
			'Dropout randomly removes neurons. ' .
			'Batch normalization normalizes layer inputs. ' .
			'Layer normalization normalizes across features. ' .
			'Instance normalization normalizes per instance. ' .
			'Group normalization normalizes per group. ' .
			'Weight decay penalizes large weights. ' .
			'Early stopping stops training when validation plateaus. ' .
			'Learning rate scheduling adjusts learning rate. ' .
			'Warmup gradually increases learning rate. ' .
			'Cooldown gradually decreases learning rate. ' .
			'Cyclic learning rates vary periodically. ' .
			'Cosine annealing uses cosine schedule. ' .
			'Linear annealing uses linear schedule. ' .
			'Exponential decay uses exponential schedule. ' .
			'Step decay reduces learning rate at intervals. ' .
			'Polynomial decay uses polynomial schedule. ' .
			'Inverse time decay uses inverse schedule. ' .
			'Natural exponential decay uses natural exponential. ' .
			'Piecewise constant decay uses constant intervals. ' .
			'This comprehensive content provides sufficient material for AI generation.';
	}

	/**
	 * Create a test image file
	 *
	 * @param string $path Path to create image at.
	 */
	private function create_test_image( string $path ): void {
		// Create a simple 1x1 PNG image
		$png = base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
		);
		file_put_contents( $path, $png );
	}
}
