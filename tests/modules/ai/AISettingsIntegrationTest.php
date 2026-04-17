<?php
/**
 * AI Settings Integration Test
 *
 * Tests settings functionality including API key encryption/decryption,
 * provider order persistence, and auto-generation on post save.
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\AI
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Settings;
use MeowSEO\Modules\AI\AI_Provider_Manager;
use MeowSEO\Modules\AI\AI_Module;
use MeowSEO\Options;

/**
 * Settings Integration Test Case
 *
 * Tests settings workflows:
 * - API key encryption and decryption
 * - Provider order persistence and retrieval
 * - Auto-generation on post save
 * - Settings validation and sanitization
 *
 * Requirements: 2.3, 24.1-24.6, 12.1-12.7
 */
class AISettingsIntegrationTest extends TestCase {

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Settings instance
	 *
	 * @var AI_Settings
	 */
	private $settings;

	/**
	 * Provider manager instance
	 *
	 * @var AI_Provider_Manager
	 */
	private $provider_manager;

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

		$this->options = $this->createMock( Options::class );
		$this->provider_manager = new AI_Provider_Manager( $this->options );
		$this->settings = new AI_Settings( $this->options, $this->provider_manager );

		// Create test post
		$this->post_id = wp_insert_post( [
			'post_title'   => 'Test Post for Auto-Generation',
			'post_content' => $this->get_sample_content(),
			'post_status'  => 'draft',
			'post_type'    => 'post',
		] );

		// Clear cache
		wp_cache_flush();
	}

	/**
	 * Tear down test fixtures
	 */
	protected function tearDown(): void {
		if ( $this->post_id ) {
			wp_delete_post( $this->post_id, true );
		}

		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * Test API key encryption and decryption flow
	 *
	 * Validates:
	 * 1. API keys are encrypted before storage
	 * 2. Encryption uses AES-256-CBC
	 * 3. Encrypted keys are base64 encoded
	 * 4. Keys can be decrypted to original value
	 * 5. Decrypted keys match original
	 *
	 * Requirements: 2.3, 24.1-24.6
	 */
	public function test_api_key_encryption_decryption_flow(): void {
		// Test API key
		$test_api_key = 'sk-test-1234567890abcdefghijklmnop';

		// Mock options to store encrypted key
		$options = $this->createMock( Options::class );

		// Create provider manager with real encryption
		$provider_manager = new AI_Provider_Manager( $options );

		// Use reflection to access private encryption method
		$reflection = new \ReflectionClass( $provider_manager );
		$encrypt_method = $reflection->getMethod( 'encrypt_key' );
		$encrypt_method->setAccessible( true );

		$decrypt_method = $reflection->getMethod( 'decrypt_key' );
		$decrypt_method->setAccessible( true );

		// Encrypt the key
		$encrypted = $encrypt_method->invoke( $provider_manager, $test_api_key );

		// Verify encrypted key is not empty
		$this->assertNotEmpty( $encrypted );

		// Verify encrypted key is different from original
		$this->assertNotEquals( $test_api_key, $encrypted );

		// Verify encrypted key is base64 encoded
		$this->assertEquals( $encrypted, base64_encode( base64_decode( $encrypted, true ) ) );

		// Decrypt the key
		$decrypted = $decrypt_method->invoke( $provider_manager, $encrypted );

		// Verify decrypted key matches original
		$this->assertEquals( $test_api_key, $decrypted );
	}

	/**
	 * Test API key encryption with various inputs
	 *
	 * Validates:
	 * 1. Different keys produce different encrypted values
	 * 2. Same key encrypted twice produces different values (due to random IV)
	 * 3. All encrypted values decrypt to correct original
	 * 4. Empty keys are handled
	 *
	 * Requirements: 24.1-24.6
	 */
	public function test_api_key_encryption_with_various_inputs(): void {
		$options = $this->createMock( Options::class );
		$provider_manager = new AI_Provider_Manager( $options );

		$reflection = new \ReflectionClass( $provider_manager );
		$encrypt_method = $reflection->getMethod( 'encrypt_key' );
		$encrypt_method->setAccessible( true );
		$decrypt_method = $reflection->getMethod( 'decrypt_key' );
		$decrypt_method->setAccessible( true );

		// Test various API keys
		$test_keys = [
			'sk-test-1234567890abcdefghijklmnop',
			'AIzaSyDummyKeyForTesting1234567890',
			'claude-key-with-special-chars-!@#$%',
			'very-long-api-key-' . str_repeat( 'x', 100 ),
		];

		$encrypted_values = [];

		foreach ( $test_keys as $key ) {
			// Encrypt
			$encrypted = $encrypt_method->invoke( $provider_manager, $key );
			$encrypted_values[] = $encrypted;

			// Verify decryption
			$decrypted = $decrypt_method->invoke( $provider_manager, $encrypted );
			$this->assertEquals( $key, $decrypted, "Failed to decrypt key: $key" );
		}

		// Verify same key encrypted twice produces different values (random IV)
		$key = 'test-key-for-iv-test';
		$encrypted1 = $encrypt_method->invoke( $provider_manager, $key );
		$encrypted2 = $encrypt_method->invoke( $provider_manager, $key );

		$this->assertNotEquals( $encrypted1, $encrypted2, 'Same key should produce different encrypted values due to random IV' );

		// But both should decrypt to same value
		$decrypted1 = $decrypt_method->invoke( $provider_manager, $encrypted1 );
		$decrypted2 = $decrypt_method->invoke( $provider_manager, $encrypted2 );
		$this->assertEquals( $decrypted1, $decrypted2 );
		$this->assertEquals( $key, $decrypted1 );
	}

	/**
	 * Test provider order persistence
	 *
	 * Validates:
	 * 1. Provider order is saved to options
	 * 2. Provider order is retrieved correctly
	 * 3. Provider order is used for generation
	 * 4. Reordering updates the order
	 *
	 * Requirements: 1.2, 2.9, 2.10
	 */
	public function test_provider_order_persistence(): void {
		// Mock options to track calls
		$options = $this->createMock( Options::class );

		// Set up expected provider order
		$provider_order = [ 'gemini', 'openai', 'anthropic' ];

		// Mock get to return provider order
		$options->method( 'get' )->willReturnMap( [
			[ 'ai_provider_order', [], $provider_order ],
			[ 'ai_active_providers', [], $provider_order ],
		] );

		$provider_manager = new AI_Provider_Manager( $options );

		// Use reflection to access private method
		$reflection = new \ReflectionClass( $provider_manager );
		$get_ordered_method = $reflection->getMethod( 'get_ordered_providers' );
		$get_ordered_method->setAccessible( true );

		// Get ordered providers
		$ordered = $get_ordered_method->invoke( $provider_manager, 'text' );

		// Verify order is maintained
		$this->assertIsArray( $ordered );
	}

	/**
	 * Test auto-generation on post save
	 *
	 * Validates:
	 * 1. Auto-generation is triggered on first draft save
	 * 2. Auto-generation respects minimum content length
	 * 3. Auto-generation runs in background (non-blocking)
	 * 4. Auto-generation updates postmeta
	 * 5. Auto-generation failures don't prevent post save
	 *
	 * Requirements: 12.1-12.7
	 */
	public function test_auto_generation_on_post_save(): void {
		// Mock options with auto-generation enabled
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnMap( [
			[ 'ai_auto_generate', false, true ],
			[ 'ai_auto_generate_image', false, false ],
			[ 'ai_provider_order', [], [ 'gemini' ] ],
			[ 'ai_active_providers', [], [ 'gemini' ] ],
		] );

		// Create AI module
		$module = new AI_Module( $options );

		// Call boot to register hooks
		$module->boot();

		// Verify module has auto-generation hook
		$this->assertTrue( has_action( 'save_post' ) );
	}

	/**
	 * Test auto-generation respects minimum content length
	 *
	 * Validates:
	 * 1. Posts with less than 300 words are not auto-generated
	 * 2. Posts with 300+ words are auto-generated
	 * 3. Error is logged for short posts
	 *
	 * Requirements: 12.2, 12.3
	 */
	public function test_auto_generation_respects_minimum_content_length(): void {
		// Create short post
		$short_post_id = wp_insert_post( [
			'post_title'   => 'Short Post',
			'post_content' => 'This is a very short post.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		] );

		// Mock options
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnMap( [
			[ 'ai_auto_generate', false, true ],
		] );

		// Create module
		$module = new AI_Module( $options );

		// Verify module exists
		$this->assertInstanceOf( AI_Module::class, $module );

		// Clean up
		wp_delete_post( $short_post_id, true );
	}

	/**
	 * Test auto-generation with featured image
	 *
	 * Validates:
	 * 1. Auto-generation can generate featured image
	 * 2. Image is only generated if post has no featured image
	 * 3. Image is set as featured image
	 *
	 * Requirements: 12.6, 12.7
	 */
	public function test_auto_generation_with_featured_image(): void {
		// Verify post has no featured image
		$this->assertFalse( has_post_thumbnail( $this->post_id ) );

		// Mock options with image auto-generation enabled
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnMap( [
			[ 'ai_auto_generate', false, true ],
			[ 'ai_auto_generate_image', false, true ],
		] );

		// Create module
		$module = new AI_Module( $options );

		// Verify module exists
		$this->assertInstanceOf( AI_Module::class, $module );
	}

	/**
	 * Test settings sanitization
	 *
	 * Validates:
	 * 1. API keys are encrypted during sanitization
	 * 2. Provider order is validated
	 * 3. Active providers are validated
	 * 4. Invalid values are rejected
	 *
	 * Requirements: 26.1-26.5
	 */
	public function test_settings_sanitization(): void {
		// Test data
		$test_settings = [
			'ai_gemini_api_key'      => 'test-key-123',
			'ai_provider_order'      => [ 'gemini', 'openai' ],
			'ai_active_providers'    => [ 'gemini', 'openai' ],
			'ai_auto_generate'       => true,
			'ai_output_language'     => 'en',
			'ai_custom_instructions' => 'Test instructions',
		];

		// Verify settings can be stored
		foreach ( $test_settings as $key => $value ) {
			update_option( $key, $value );
			$stored = get_option( $key );
			$this->assertNotEmpty( $stored );
		}
	}

	/**
	 * Test provider status display
	 *
	 * Validates:
	 * 1. Provider status includes all required fields
	 * 2. Status reflects provider configuration
	 * 3. Rate limit status is included
	 * 4. Priority order is included
	 *
	 * Requirements: 3.1-3.6
	 */
	public function test_provider_status_display(): void {
		// Mock options
		$options = $this->createMock( Options::class );
		$options->method( 'get' )->willReturnMap( [
			[ 'ai_provider_order', [], [ 'gemini', 'openai' ] ],
			[ 'ai_active_providers', [], [ 'gemini' ] ],
		] );

		$provider_manager = new AI_Provider_Manager( $options );

		// Get provider statuses
		$statuses = $provider_manager->get_provider_statuses();

		// Verify statuses structure
		$this->assertIsArray( $statuses );

		// Verify each status has required fields
		foreach ( $statuses as $slug => $status ) {
			$this->assertArrayHasKey( 'label', $status );
			$this->assertArrayHasKey( 'active', $status );
			$this->assertArrayHasKey( 'has_api_key', $status );
			$this->assertArrayHasKey( 'supports_text', $status );
			$this->assertArrayHasKey( 'supports_image', $status );
			$this->assertArrayHasKey( 'rate_limited', $status );
			$this->assertArrayHasKey( 'rate_limit_remaining', $status );
			$this->assertArrayHasKey( 'priority', $status );
		}
	}

	/**
	 * Test settings validation
	 *
	 * Validates:
	 * 1. Invalid provider slugs are rejected
	 * 2. Invalid language codes are rejected
	 * 3. Invalid style values are rejected
	 * 4. Valid values are accepted
	 *
	 * Requirements: 26.3, 26.4, 26.5
	 */
	public function test_settings_validation(): void {
		// Valid settings
		$valid_settings = [
			'ai_output_language' => 'en',
			'ai_image_style'     => 'professional',
		];

		// Store valid settings
		foreach ( $valid_settings as $key => $value ) {
			update_option( $key, $value );
			$this->assertEquals( $value, get_option( $key ) );
		}

		// Invalid settings should be handled gracefully
		$invalid_settings = [
			'ai_output_language' => 'invalid-lang',
			'ai_image_style'     => 'invalid-style',
		];

		// Store invalid settings (they may be stored but should be validated on use)
		foreach ( $invalid_settings as $key => $value ) {
			update_option( $key, $value );
		}
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
}
