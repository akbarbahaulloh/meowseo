<?php
/**
 * AI_Generator Analysis Context Integration Test
 *
 * Unit tests for the AI Generator analysis context integration.
 *
 * @package MeowSEO\Tests\Modules\AI
 * @since 1.0.0
 */

namespace MeowSEO\Tests\Modules\AI;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\AI\AI_Generator;

/**
 * AI_Generator Analysis Context test case
 *
 * Tests that the Generator correctly integrates analysis results
 * from the readability-keyword-analysis-engine into generation prompts.
 *
 * Requirements: 32.1, 32.2, 32.3, 32.4, 32.5
 *
 * @since 1.0.0
 */
class AnalysisContextIntegrationTest extends TestCase {

	/**
	 * AI_Generator instance.
	 *
	 * @var AI_Generator
	 */
	private $generator;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create mock dependencies.
		$provider_manager = $this->createMock( 'MeowSEO\Modules\AI\AI_Provider_Manager' );
		$options = $this->createMock( 'MeowSEO\Options' );

		// Create generator instance.
		$this->generator = new AI_Generator( $provider_manager, $options );
	}

	/**
	 * Test that analysis context is formatted correctly with high scores.
	 */
	public function test_format_analysis_context_high_scores() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		$analysis = [
			'seo_score'           => 85,
			'readability_score'   => 80,
			'keyword_density'     => 1.8,
			'flesch_score'        => 75,
			'passive_voice_pct'   => 5,
			'transition_words_pct' => 40,
			'keyword_in_title'    => true,
			'keyword_in_description' => true,
			'keyword_in_first_paragraph' => true,
		];

		$context = $method->invoke( $this->generator, $analysis );

		// Verify high score recommendations.
		$this->assertStringContainsString( 'SEO Score: 85/100', $context );
		$this->assertStringContainsString( 'SEO score is good', $context );
		$this->assertStringContainsString( 'Readability Score: 80/100', $context );
		$this->assertStringContainsString( 'Readability is good', $context );
		$this->assertStringContainsString( 'Keyword density is optimal', $context );
		$this->assertStringContainsString( 'Easy to read', $context );
	}

	/**
	 * Test that analysis context is formatted correctly with low scores.
	 */
	public function test_format_analysis_context_low_scores() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		$analysis = [
			'seo_score'           => 35,
			'readability_score'   => 40,
			'keyword_density'     => 0.2,
			'flesch_score'        => 30,
			'passive_voice_pct'   => 18,
			'transition_words_pct' => 10,
			'keyword_in_title'    => false,
			'keyword_in_description' => false,
			'keyword_in_first_paragraph' => false,
		];

		$context = $method->invoke( $this->generator, $analysis );

		// Verify low score recommendations.
		$this->assertStringContainsString( 'SEO Score: 35/100', $context );
		$this->assertStringContainsString( 'SEO score is low', $context );
		$this->assertStringContainsString( 'Readability Score: 40/100', $context );
		$this->assertStringContainsString( 'Readability is low', $context );
		$this->assertStringContainsString( 'Increase keyword usage', $context );
		$this->assertStringContainsString( 'Difficult to read', $context );
		$this->assertStringContainsString( 'High passive voice usage', $context );
		$this->assertStringContainsString( 'Low transition word usage', $context );
		$this->assertStringContainsString( 'Add keyword to title', $context );
	}

	/**
	 * Test that analysis context is formatted correctly with moderate scores.
	 */
	public function test_format_analysis_context_moderate_scores() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		$analysis = [
			'seo_score'           => 60,
			'readability_score'   => 55,
			'keyword_density'     => 0.4,
			'flesch_score'        => 50,
			'passive_voice_pct'   => 12,
			'transition_words_pct' => 25,
			'keyword_in_title'    => true,
			'keyword_in_description' => false,
			'keyword_in_first_paragraph' => true,
		];

		$context = $method->invoke( $this->generator, $analysis );

		// Verify moderate score recommendations.
		$this->assertStringContainsString( 'SEO Score: 60/100', $context );
		$this->assertStringContainsString( 'SEO score is moderate', $context );
		$this->assertStringContainsString( 'Readability Score: 55/100', $context );
		$this->assertStringContainsString( 'Readability is moderate', $context );
		$this->assertStringContainsString( 'Increase keyword usage', $context );
		$this->assertStringContainsString( 'Moderate difficulty', $context );
		$this->assertStringContainsString( 'Moderate passive voice', $context );
		$this->assertStringContainsString( 'Moderate transition word usage', $context );
	}

	/**
	 * Test that keyword density recommendations are accurate.
	 */
	public function test_keyword_density_recommendations() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		// Test low keyword density.
		$analysis = [
			'seo_score'           => 50,
			'readability_score'   => 50,
			'keyword_density'     => 0.2,
			'flesch_score'        => 0,
			'passive_voice_pct'   => 0,
			'transition_words_pct' => 0,
		];

		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Increase keyword usage', $context );

		// Test high keyword density.
		$analysis['keyword_density'] = 4.5;
		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Reduce keyword usage', $context );

		// Test optimal keyword density.
		$analysis['keyword_density'] = 1.8;
		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Keyword density is optimal', $context );
	}

	/**
	 * Test that passive voice recommendations are accurate.
	 */
	public function test_passive_voice_recommendations() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		// Test high passive voice.
		$analysis = [
			'seo_score'           => 50,
			'readability_score'   => 50,
			'keyword_density'     => 0,
			'flesch_score'        => 0,
			'passive_voice_pct'   => 18,
			'transition_words_pct' => 0,
		];

		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'High passive voice usage', $context );
		$this->assertStringContainsString( 'Use more active voice', $context );

		// Test moderate passive voice.
		$analysis['passive_voice_pct'] = 12;
		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Moderate passive voice', $context );
	}

	/**
	 * Test that transition words recommendations are accurate.
	 */
	public function test_transition_words_recommendations() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		// Test low transition words.
		$analysis = [
			'seo_score'           => 50,
			'readability_score'   => 50,
			'keyword_density'     => 0,
			'flesch_score'        => 0,
			'passive_voice_pct'   => 0,
			'transition_words_pct' => 10,
		];

		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Low transition word usage', $context );
		$this->assertStringContainsString( 'Add more connecting words', $context );

		// Test moderate transition words.
		$analysis['transition_words_pct'] = 25;
		$context = $method->invoke( $this->generator, $analysis );
		$this->assertStringContainsString( 'Moderate transition word usage', $context );
	}

	/**
	 * Test that analysis context includes all required sections.
	 */
	public function test_analysis_context_includes_all_sections() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		$analysis = [
			'seo_score'           => 72,
			'readability_score'   => 65,
			'keyword_density'     => 1.2,
			'flesch_score'        => 68,
			'passive_voice_pct'   => 8.5,
			'transition_words_pct' => 35,
			'keyword_in_title'    => true,
			'keyword_in_description' => true,
			'keyword_in_first_paragraph' => true,
		];

		$context = $method->invoke( $this->generator, $analysis );

		// Verify all sections are present.
		$this->assertStringContainsString( 'CURRENT CONTENT ANALYSIS', $context );
		$this->assertStringContainsString( 'SEO Score:', $context );
		$this->assertStringContainsString( 'Readability Score:', $context );
		$this->assertStringContainsString( 'Keyword Density:', $context );
		$this->assertStringContainsString( 'Flesch Reading Ease:', $context );
		$this->assertStringContainsString( 'Passive Voice:', $context );
		$this->assertStringContainsString( 'Transition Words:', $context );
		$this->assertStringContainsString( 'Keyword Placement:', $context );
		$this->assertStringContainsString( 'END ANALYSIS', $context );
	}

	/**
	 * Test that keyword placement analysis is included.
	 */
	public function test_keyword_placement_analysis() {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->generator );
		$method = $reflection->getMethod( 'format_analysis_context' );
		$method->setAccessible( true );

		$analysis = [
			'seo_score'           => 50,
			'readability_score'   => 50,
			'keyword_density'     => 0,
			'flesch_score'        => 0,
			'passive_voice_pct'   => 0,
			'transition_words_pct' => 0,
			'keyword_in_title'    => true,
			'keyword_in_description' => false,
			'keyword_in_first_paragraph' => true,
		];

		$context = $method->invoke( $this->generator, $analysis );

		// Verify keyword placement analysis.
		$this->assertStringContainsString( 'Keyword appears in title', $context );
		$this->assertStringContainsString( 'Add keyword to meta description', $context );
		$this->assertStringContainsString( 'Keyword appears in first paragraph', $context );
	}
}
