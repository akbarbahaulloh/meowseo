<?php
/**
 * PHPUnit Bootstrap
 *
 * @package MeowSEO
 * @since 1.0.0
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize Brain\Monkey for WordPress function mocking
require_once __DIR__ . '/../vendor/brain/monkey/inc/patchwork-loader.php';

// Define WordPress constants for testing
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// Define plugin directory constant
if ( ! defined( 'MEOWSEO_PLUGIN_DIR' ) ) {
	define( 'MEOWSEO_PLUGIN_DIR', __DIR__ . '/../' );
}

// Define MEOWSEO_PATH constant
if ( ! defined( 'MEOWSEO_PATH' ) ) {
	define( 'MEOWSEO_PATH', __DIR__ . '/../' );
}

// Register custom autoloader for WordPress naming convention
require_once MEOWSEO_PLUGIN_DIR . 'includes/class-autoloader.php';
\MeowSEO\Autoloader::register();

// Mock WordPress functions used in the code
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags( $string );

		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}

		return trim( $string );
	}
}

if ( ! function_exists( 'strip_shortcodes' ) ) {
	function strip_shortcodes( $content ) {
		return preg_replace( '/\[.*?\]/', '', $content );
	}
}

// Note: wp_upload_dir() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// Note: trailingslashit() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// Note: wp_mkdir_p() is intentionally not defined here to allow Brain\Monkey to mock it in tests

if ( ! function_exists( 'add_rewrite_rule' ) ) {
	function add_rewrite_rule( $regex, $query, $after = 'bottom' ) {
		// Mock function
	}
}

if ( ! function_exists( 'add_rewrite_tag' ) ) {
	function add_rewrite_tag( $tag, $regex ) {
		// Mock function
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock function
	}
}

if ( ! function_exists( 'status_header' ) ) {
	function status_header( $code ) {
		// Mock function
	}
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
	function wp_is_post_autosave( $post_id ) {
		return false;
	}
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
	function wp_is_post_revision( $post_id ) {
		return false;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		static $options = array();
		return $options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		static $options = array();
		$options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		static $options = array();
		unset( $options[ $option ] );
		return true;
	}
}

// In-memory cache storage for testing
global $wp_cache_storage;
if ( ! isset( $wp_cache_storage ) ) {
	$wp_cache_storage = array();
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		global $wp_cache_storage;
		$cache_key = $group ? "{$group}:{$key}" : $key;
		return isset( $wp_cache_storage[ $cache_key ] ) ? $wp_cache_storage[ $cache_key ] : false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
		global $wp_cache_storage;
		$cache_key = $group ? "{$group}:{$key}" : $key;
		$wp_cache_storage[ $cache_key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		global $wp_cache_storage;
		$cache_key = $group ? "{$group}:{$key}" : $key;
		if ( isset( $wp_cache_storage[ $cache_key ] ) ) {
			unset( $wp_cache_storage[ $cache_key ] );
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'wp_cache_add' ) ) {
	function wp_cache_add( $key, $value, $group = '', $expire = 0 ) {
		global $wp_cache_storage;
		$cache_key = $group ? "{$group}:{$key}" : $key;
		if ( isset( $wp_cache_storage[ $cache_key ] ) ) {
			return false;
		}
		$wp_cache_storage[ $cache_key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
	function wp_using_ext_object_cache() {
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return 'http://example.com/wp-content/plugins/' . ltrim( $path, '/' );
	}
}

// Note: get_bloginfo() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// Note: get_site_url() is intentionally not defined here to allow Brain\Monkey to mock it in tests

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post ) {
		return 'http://example.com/post-' . ( is_object( $post ) ? $post->ID : $post );
	}
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post_id ) {
		return 0;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail' ) {
		return false;
	}
}

if ( ! function_exists( 'gmdate' ) ) {
	function gmdate( $format, $timestamp = null ) {
		return \gmdate( $format, $timestamp ?? time() );
	}
}

// Mock global $wpdb
global $wpdb;
if ( ! isset( $wpdb ) ) {
	$wpdb = new class {
		public $posts = 'wp_posts';
		public $postmeta = 'wp_postmeta';
		public $prefix = 'wp_';

		public function prepare( $query, ...$args ) {
			return vsprintf( str_replace( '%s', "'%s'", str_replace( '%d', '%d', $query ) ), $args );
		}

		public function get_results( $query ) {
			return array();
		}

		public function get_var( $query ) {
			return 0;
		}

		public function query( $query ) {
			return 0;
		}
	};
}

// In-memory post storage for testing
global $wp_posts_storage;
if ( ! isset( $wp_posts_storage ) ) {
	$wp_posts_storage = array();
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr = array(), $wp_error = false ) {
		global $wp_posts_storage;
		static $post_id = 0;
		$post_id++;

		$post = new WP_Post( array_merge(
			array(
				'ID'           => $post_id,
				'post_title'   => '',
				'post_content' => '',
				'post_excerpt' => '',
				'post_status'  => 'draft',
				'post_name'    => '',
				'post_type'    => 'post',
				'post_author'  => 0,
				'post_date'    => gmdate( 'Y-m-d H:i:s' ),
				'post_modified' => gmdate( 'Y-m-d H:i:s' ),
			),
			$postarr
		) );

		$wp_posts_storage[ $post_id ] = $post;
		return $post_id;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
		global $wp_posts_storage;
		if ( is_null( $post ) || $post === '' ) {
			return null;
		}

		$post_id = is_object( $post ) ? $post->ID : (int) $post;
		return isset( $wp_posts_storage[ $post_id ] ) ? $wp_posts_storage[ $post_id ] : null;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		$post = get_post( $post );
		return $post ? $post->post_title : '';
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() {
		return 0;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $post_types = '' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_delete_post' ) ) {
	function wp_delete_post( $postid = 0, $force_delete = false ) {
		global $wp_posts_storage;
		if ( isset( $wp_posts_storage[ $postid ] ) ) {
			unset( $wp_posts_storage[ $postid ] );
			return true;
		}
		return false;
	}
}

// In-memory postmeta storage for testing
global $wp_postmeta_storage;
if ( ! isset( $wp_postmeta_storage ) ) {
	$wp_postmeta_storage = array();
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		global $wp_postmeta_storage;
		$post_id = (int) $post_id;

		if ( ! isset( $wp_postmeta_storage[ $post_id ] ) ) {
			return $single ? '' : array();
		}

		if ( empty( $key ) ) {
			return $wp_postmeta_storage[ $post_id ];
		}

		if ( $single ) {
			return isset( $wp_postmeta_storage[ $post_id ][ $key ] ) ? $wp_postmeta_storage[ $post_id ][ $key ][0] : '';
		}

		return isset( $wp_postmeta_storage[ $post_id ][ $key ] ) ? $wp_postmeta_storage[ $post_id ][ $key ] : array();
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
		global $wp_postmeta_storage;
		$post_id = (int) $post_id;

		if ( ! isset( $wp_postmeta_storage[ $post_id ] ) ) {
			$wp_postmeta_storage[ $post_id ] = array();
		}

		$wp_postmeta_storage[ $post_id ][ $meta_key ] = array( $meta_value );
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $meta_key = '', $meta_value = '' ) {
		global $wp_postmeta_storage;
		$post_id = (int) $post_id;

		if ( ! isset( $wp_postmeta_storage[ $post_id ] ) ) {
			return false;
		}

		if ( empty( $meta_key ) ) {
			unset( $wp_postmeta_storage[ $post_id ] );
			return true;
		}

		if ( isset( $wp_postmeta_storage[ $post_id ][ $meta_key ] ) ) {
			unset( $wp_postmeta_storage[ $post_id ][ $meta_key ] );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		return 'http://example.com' . $path;
	}
}

if ( ! function_exists( 'get_term_link' ) ) {
	function get_term_link( $term, $taxonomy = '' ) {
		return 'http://example.com/term/test/';
	}
}

if ( ! function_exists( 'get_queried_object' ) ) {
	function get_queried_object() {
		return null;
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( $var, $default = '' ) {
		return $default;
	}
}

if ( ! function_exists( 'get_search_query' ) ) {
	function get_search_query( $escaped = true ) {
		return '';
	}
}

if ( ! function_exists( 'is_category' ) ) {
	function is_category( $category = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_tag' ) ) {
	function is_tag( $tag = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $taxonomy = '', $term = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		return false;
	}
}

if ( ! function_exists( 'is_home' ) ) {
	function is_home() {
		return false;
	}
}

if ( ! function_exists( 'is_author' ) ) {
	function is_author( $author = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_post_type_archive' ) ) {
	function is_post_type_archive( $post_types = '' ) {
		return false;
	}
}

if ( ! function_exists( 'is_search' ) ) {
	function is_search() {
		return false;
	}
}

if ( ! function_exists( 'is_attachment' ) ) {
	function is_attachment() {
		return false;
	}
}

if ( ! function_exists( 'is_date' ) ) {
	function is_date() {
		return false;
	}
}

if ( ! function_exists( 'wp_attachment_is_image' ) ) {
	function wp_attachment_is_image( $post_id = 0 ) {
		return false;
	}
}

if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
	function wp_get_attachment_metadata( $attachment_id, $unfiltered = false ) {
		return false;
	}
}

if ( ! function_exists( 'attachment_url_to_postid' ) ) {
	function attachment_url_to_postid( $url ) {
		return 0;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		if ( $show === 'name' ) {
			return 'Test Site';
		}
		return '';
	}
}

if ( ! function_exists( 'register_post_meta' ) ) {
	function register_post_meta( $post_type, $meta_key, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'register_rest_field' ) ) {
	function register_rest_field( $object_type, $attribute, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = array(), $deprecated = false ) {
		return true;
	}
}

// Global test override for current_user_can
global $test_current_user_can_override;
$test_current_user_can_override = null;

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		global $test_current_user_can_override;
		
		// Allow tests to override the capability check
		if ( $test_current_user_can_override !== null ) {
			return $test_current_user_can_override;
		}
		
		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

// Global test override for wp_verify_nonce
global $test_wp_verify_nonce_override;
$test_wp_verify_nonce_override = null;

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		global $test_wp_verify_nonce_override;
		
		// Allow tests to override nonce verification
		if ( $test_wp_verify_nonce_override !== null ) {
			return $test_wp_verify_nonce_override;
		}
		
		return true;
	}
}

// Define WordPress constants
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof \WP_Error );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->errors[ $code ][] = $message;
				if ( ! empty( $data ) ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return isset( $this->errors[ $code ] ) ? $this->errors[ $code ][0] : '';
		}

		public function get_error_data( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID = 0;
		public $post_title = '';
		public $post_content = '';
		public $post_excerpt = '';
		public $post_status = 'draft';
		public $post_name = '';
		public $post_type = 'post';
		public $post_author = 0;
		public $post_date = '';
		public $post_modified = '';

		public function __construct( $data = array() ) {
			foreach ( $data as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}
	}
}

if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
		return 'http://example.com';
	}
}

if ( ! function_exists( 'get_the_date' ) ) {
	function get_the_date( $format = '', $post = null ) {
		return gmdate( 'c' );
	}
}

if ( ! function_exists( 'get_the_modified_date' ) ) {
	function get_the_modified_date( $format = '', $post = null ) {
		return gmdate( 'c' );
	}
}

if ( ! function_exists( 'get_the_author_meta' ) ) {
	function get_the_author_meta( $field = '', $user_id = null ) {
		return 'Test Author';
	}
}

if ( ! function_exists( 'get_author_posts_url' ) ) {
	function get_author_posts_url( $author_id, $author_nicename = '' ) {
		return 'http://example.com/author/test-author/';
	}
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
	function has_post_thumbnail( $post = null ) {
		return false;
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( $post_type ) {
		$obj = new \stdClass();
		$obj->has_archive = true;
		$obj->labels = new \stdClass();
		$obj->labels->name = ucfirst( $post_type ) . 's';
		return $obj;
	}
}

if ( ! function_exists( 'get_post_type_archive_link' ) ) {
	function get_post_type_archive_link( $post_type ) {
		return 'http://example.com/' . $post_type . '/';
	}
}

if ( ! function_exists( 'get_the_category' ) ) {
	function get_the_category( $post_id = 0 ) {
		return array();
	}
}

if ( ! function_exists( 'get_category_link' ) ) {
	function get_category_link( $category_id ) {
		return 'http://example.com/category/test/';
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir( $time = null ) {
		return array(
			'path'    => '/tmp/wp-content/uploads',
			'url'     => 'http://example.com/wp-content/uploads',
			'subdir'  => '',
			'basedir' => '/tmp/wp-content/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
			'error'   => false,
		);
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql', $gmt = 0 ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}


if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params = array();
		private $headers = array();

		public function __construct( $method = 'GET', $route = '', $args = array() ) {
			$this->params = $args;
		}

		public function has_param( $key ) {
			return isset( $this->params[ $key ] );
		}

		public function get_param( $key ) {
			return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
		}

		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}

		public function get_header( $key ) {
			return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : null;
		}

		public function set_header( $key, $value ) {
			$this->headers[ $key ] = $value;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data = array();
		private $headers = array();

		public function __construct( $data = null, $status = 200, $headers = array() ) {
			$this->data = $data;
			$this->headers = $headers;
		}

		public function get_data() {
			return $this->data;
		}

		public function set_data( $data ) {
			$this->data = $data;
		}

		public function header( $key, $value ) {
			$this->headers[ $key ] = $value;
		}

		public function get_headers() {
			return $this->headers;
		}
	}
}

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	/**
	 * Base test case class for WordPress unit tests
	 */
	class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {
		/**
		 * Set up test environment before each test
		 */
		protected function setUp(): void {
			parent::setUp();
			
			// Reset global storage
			global $wp_cache_storage, $wp_posts_storage, $wp_postmeta_storage;
			$wp_cache_storage = array();
			$wp_posts_storage = array();
			$wp_postmeta_storage = array();
			
			// Reset test overrides
			global $test_current_user_can_override, $test_wp_verify_nonce_override;
			$test_current_user_can_override = null;
			$test_wp_verify_nonce_override = null;
		}
		
		/**
		 * Tear down test environment after each test
		 */
		protected function tearDown(): void {
			parent::tearDown();
		}
	}
}
