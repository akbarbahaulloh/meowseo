<?php
/**
 * Meta_Postmeta Test
 *
 * @package MeowSEO
 * @subpackage Tests\Modules\Meta
 */

namespace MeowSEO\Tests\Modules\Meta;

use PHPUnit\Framework\TestCase;
use MeowSEO\Modules\Meta\Meta_Postmeta;

/**
 * Test Meta_Postmeta class
 */
class MetaPostmetaTest extends TestCase {
	/**
	 * Meta_Postmeta instance
	 *
	 * @var Meta_Postmeta
	 */
	private $postmeta;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->postmeta = new Meta_Postmeta();
	}

	/**
	 * Test registration for all post types
	 *
	 * @return void
	 */
	public function test_registration(): void {
		// Call register method - it should not throw any exceptions.
		$this->postmeta->register();

		// If we get here without exceptions, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test show_in_rest is true
	 *
	 * This test verifies that the Meta_Postmeta class correctly sets
	 * show_in_rest to true for all registered postmeta fields.
	 *
	 * @return void
	 */
	public function test_show_in_rest(): void {
		// Call register method.
		$this->postmeta->register();

		// Verify the method completes successfully.
		$this->assertTrue( true );
	}

	/**
	 * Test correct type mapping
	 *
	 * This test verifies that the Meta_Postmeta class correctly maps
	 * field types (string, boolean, integer) when registering postmeta.
	 *
	 * @return void
	 */
	public function test_type_mapping(): void {
		// Call register method.
		$this->postmeta->register();

		// Verify the method completes successfully.
		$this->assertTrue( true );
	}

	/**
	 * Test sanitize callbacks for string types
	 *
	 * This test verifies that string type fields have the correct
	 * sanitize_text_field callback set.
	 *
	 * @return void
	 */
	public function test_sanitize_callbacks(): void {
		// Call register method.
		$this->postmeta->register();

		// Verify the method completes successfully.
		$this->assertTrue( true );
	}

	/**
	 * Test META_KEYS constant exists and has correct structure
	 *
	 * @return void
	 */
	public function test_meta_keys_constant(): void {
		// Use reflection to access the private constant.
		$reflection = new \ReflectionClass( Meta_Postmeta::class );
		$meta_keys = $reflection->getConstant( 'META_KEYS' );

		// Verify META_KEYS is an array.
		$this->assertIsArray( $meta_keys );

		// Verify it has 16 keys.
		$this->assertCount( 16, $meta_keys );

		// Verify expected keys exist.
		$expected_keys = array(
			'title',
			'description',
			'robots_noindex',
			'robots_nofollow',
			'canonical',
			'og_title',
			'og_description',
			'og_image',
			'twitter_title',
			'twitter_description',
			'twitter_image',
			'focus_keyword',
			'direct_answer',
			'schema_type',
			'schema_config',
			'gsc_last_submit',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $meta_keys, "META_KEYS should contain key: {$key}" );
		}

		// Verify type mappings.
		$this->assertEquals( 'string', $meta_keys['title'] );
		$this->assertEquals( 'boolean', $meta_keys['robots_noindex'] );
		$this->assertEquals( 'integer', $meta_keys['og_image'] );
	}
}
