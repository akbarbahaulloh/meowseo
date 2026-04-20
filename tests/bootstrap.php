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

/**
 * IMPORTANT: Brain\Monkey Mocking Strategy
 * 
 * The following WordPress functions are NOT defined in this bootstrap file
 * to allow Brain\Monkey to mock them in individual tests:
 * 
 * - wp_upload_dir()
 * - trailingslashit()
 * - wp_mkdir_p()
 * - get_bloginfo()
 * - get_site_url()
 * 
 * These functions should ONLY be mocked via Brain\Monkey in tests using:
 * 
 *   Monkey\Functions\when('wp_upload_dir')->justReturn([...]);
 *   Monkey\Functions\when('get_bloginfo')->justReturn('Test Site');
 *   etc.
 * 
 * If these functions are defined in bootstrap.php, Brain\Monkey cannot
 * override them, causing "Cannot redeclare function" errors in tests.
 * 
 * For property-based tests that need to mock these functions with different
 * values across iterations, use reset_wpdb_storage() in setUp() to ensure
 * clean state between test iterations.
 */

// Define WordPress constants for testing
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// Define AUTH_KEY for encryption testing
if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', 'test-auth-key-for-encryption-testing-12345678901234567890' );
}

// Define SECURE_AUTH_KEY for encryption testing
if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-encryption-testing-1234567890' );
}

// Define plugin directory constant
if ( ! defined( 'MEOWSEO_PLUGIN_DIR' ) ) {
	define( 'MEOWSEO_PLUGIN_DIR', __DIR__ . '/../' );
}

// Define MEOWSEO_PATH constant
if ( ! defined( 'MEOWSEO_PATH' ) ) {
	define( 'MEOWSEO_PATH', __DIR__ . '/../' );
}

// Define MEOWSEO_URL constant
if ( ! defined( 'MEOWSEO_URL' ) ) {
	define( 'MEOWSEO_URL', 'http://example.com/wp-content/plugins/meowseo/' );
}

// Define WordPress time constants
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
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

if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'wp_list_pluck' ) ) {
	function wp_list_pluck( $list, $field, $index_key = null ) {
		$newlist = array();
		
		if ( ! $index_key ) {
			foreach ( $list as $value ) {
				if ( is_object( $value ) ) {
					$newlist[] = $value->$field ?? null;
				} else {
					$newlist[] = $value[ $field ] ?? null;
				}
			}
			return $newlist;
		}
		
		foreach ( $list as $value ) {
			if ( is_object( $value ) ) {
				$index = $value->$index_key ?? null;
				$newlist[ $index ] = $value->$field ?? null;
			} else {
				$index = $value[ $index_key ] ?? null;
				$newlist[ $index ] = $value[ $field ] ?? null;
			}
		}
		
		return $newlist;
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
		// Mock function - store actions for testing
		global $wp_filter;
		if ( ! isset( $wp_filter ) ) {
			$wp_filter = array();
		}
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			$wp_filter[ $hook ] = array();
		}
		$wp_filter[ $hook ][] = array(
			'callback' => $callback,
			'priority' => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
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

// In-memory options storage for testing
global $wp_options_storage;
if ( ! isset( $wp_options_storage ) ) {
	$wp_options_storage = array();
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $wp_options_storage;
		return $wp_options_storage[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		global $wp_options_storage;
		$wp_options_storage[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $wp_options_storage;
		unset( $wp_options_storage[ $option ] );
		return true;
	}
}

// In-memory cache storage for testing
global $wp_cache_storage;
if ( ! isset( $wp_cache_storage ) ) {
	$wp_cache_storage = array();
}

// In-memory filter storage for testing
global $wp_filter;
if ( ! isset( $wp_filter ) ) {
	$wp_filter = array();
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

if ( ! function_exists( 'wp_cache_flush' ) ) {
	function wp_cache_flush() {
		global $wp_cache_storage;
		$wp_cache_storage = array();
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
		global $wp_filter;
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return $value;
		}
		// Sort by priority
		usort( $wp_filter[ $tag ], function( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		} );
		// Apply filters
		foreach ( $wp_filter[ $tag ] as $filter ) {
			$callback = $filter['callback'];
			if ( is_callable( $callback ) ) {
				$value = call_user_func_array( $callback, array_merge( array( $value ), $args ) );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock function - store filters for testing
		global $wp_filter;
		if ( ! isset( $wp_filter ) ) {
			$wp_filter = array();
		}
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			$wp_filter[ $hook ] = array();
		}
		$wp_filter[ $hook ][] = array(
			'callback' => $callback,
			'priority' => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return false;
		}
		if ( $callback === false ) {
			return ! empty( $wp_filter[ $hook ] );
		}
		foreach ( $wp_filter[ $hook ] as $filter ) {
			if ( $filter['callback'] === $callback ) {
				return $filter['priority'];
			}
		}
		return false;
	}
}

if ( ! function_exists( 'remove_all_filters' ) ) {
	function remove_all_filters( $hook, $priority = false ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return true;
		}
		if ( $priority === false ) {
			unset( $wp_filter[ $hook ] );
		} else {
			$wp_filter[ $hook ] = array_filter(
				$wp_filter[ $hook ],
				function( $filter ) use ( $priority ) {
					return $filter['priority'] !== $priority;
				}
			);
		}
		return true;
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return 'http://example.com/wp-content/plugins/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		if ( $output === 'objects' ) {
			return array(
				'post' => (object) array( 'name' => 'post', 'label' => 'Posts' ),
				'page' => (object) array( 'name' => 'page', 'label' => 'Pages' ),
			);
		}
		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'get_taxonomies' ) ) {
	function get_taxonomies( $args = array(), $output = 'names' ) {
		if ( $output === 'objects' ) {
			return array(
				'category' => (object) array( 'name' => 'category', 'label' => 'Categories' ),
				'post_tag' => (object) array( 'name' => 'post_tag', 'label' => 'Tags' ),
			);
		}
		return array( 'category', 'post_tag' );
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

// In-memory database storage for testing
global $wpdb_storage;
if ( ! isset( $wpdb_storage ) ) {
	$wpdb_storage = array();
}

/**
 * Reset wpdb storage for test isolation in property-based tests
 * 
 * This function should be called in setUp() methods of property-based tests
 * to ensure clean database state between test iterations.
 * 
 * @since 1.0.0
 */
function reset_wpdb_storage() {
	global $wpdb_storage, $wpdb;
	$wpdb_storage = array();
	
	if ( isset( $wpdb ) ) {
		$wpdb->insert_id = 1;
		$wpdb->last_error = '';
	}
}

/**
 * Reset Logger singleton for test isolation in property-based tests
 * 
 * This function should be called in setUp() methods of property-based tests
 * to ensure clean Logger state between test iterations.
 * 
 * @since 1.0.0
 */
function reset_logger_singleton() {
	$reflection = new \ReflectionClass( \MeowSEO\Helpers\Logger::class );
	$instance_property = $reflection->getProperty( 'instance' );
	$instance_property->setAccessible( true );
	$instance_property->setValue( null, null );
}

/**
 * Setup Brain\Monkey mocking for WordPress functions not defined in bootstrap
 * 
 * This function should be called in setUp() methods of tests that need to mock
 * WordPress functions that are intentionally not defined in bootstrap.php to
 * allow Brain\Monkey to override them.
 * 
 * Functions mocked:
 * - wp_upload_dir()
 * - trailingslashit()
 * - wp_mkdir_p()
 * - get_bloginfo()
 * - get_site_url()
 * 
 * @since 1.0.0
 */
function setup_brain_monkey_mocks() {
	// Mock WordPress functions that are not defined in bootstrap.php
	// to allow Brain\Monkey to override them
	\Brain\Monkey\Functions\when( 'get_bloginfo' )->alias( function ( $show = '' ) {
		$values = array(
			'name'        => 'Test Site',
			'description' => 'Test Description',
			'language'    => 'en-US',
			'version'     => '6.4.2',
		);
		return $values[ $show ] ?? '';
	} );
	
	\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( function ( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	} );
	
	\Brain\Monkey\Functions\when( 'get_site_url' )->justReturn( 'https://example.com' );
	
	\Brain\Monkey\Functions\when( 'wp_upload_dir' )->justReturn( [
		'path'    => sys_get_temp_dir() . '/wp-content/uploads',
		'url'     => 'http://example.com/wp-content/uploads',
		'subdir'  => '',
		'basedir' => sys_get_temp_dir() . '/wp-content/uploads',
		'baseurl' => 'http://example.com/wp-content/uploads',
		'error'   => false,
	] );
	
	\Brain\Monkey\Functions\when( 'wp_mkdir_p' )->justReturn( true );
}

// Mock global $wpdb
global $wpdb;
if ( ! isset( $wpdb ) ) {
	$wpdb = new class {
		public $posts = 'wp_posts';
		public $postmeta = 'wp_postmeta';
		public $options = 'wp_options';
		public $prefix = 'wp_';
		public $insert_id = 1;
		public $last_error = '';

		public function prepare( $query, ...$args ) {
			// Flatten args if first arg is an array
			if ( count( $args ) === 1 && is_array( $args[0] ) ) {
				$args = $args[0];
			}
			
			// Replace placeholders with actual values
			$offset = 0;
			$prepared = '';
			$arg_index = 0;
			
			while ( $offset < strlen( $query ) ) {
				$pos_s = strpos( $query, '%s', $offset );
				$pos_d = strpos( $query, '%d', $offset );
				$pos_f = strpos( $query, '%f', $offset );
				
				// Find the next placeholder
				$positions = array_filter( [ $pos_s, $pos_d, $pos_f ], function( $p ) { return $p !== false; } );
				
				if ( empty( $positions ) ) {
					// No more placeholders
					$prepared .= substr( $query, $offset );
					break;
				}
				
				$next_pos = min( $positions );
				$prepared .= substr( $query, $offset, $next_pos - $offset );
				
				// Determine placeholder type
				$placeholder = substr( $query, $next_pos, 2 );
				
				if ( $arg_index >= count( $args ) ) {
					// No more args, keep placeholder
					$prepared .= $placeholder;
					$offset = $next_pos + 2;
					continue;
				}
				
				$value = $args[ $arg_index++ ];
				
				// Replace based on type
				if ( $placeholder === '%s' ) {
					$prepared .= "'" . addslashes( $value ) . "'";
				} elseif ( $placeholder === '%d' ) {
					$prepared .= (int) $value;
				} elseif ( $placeholder === '%f' ) {
					$prepared .= (float) $value;
				}
				
				$offset = $next_pos + 2;
			}
			
			return $prepared;
		}

		public function get_results( $query, $output = OBJECT ) {
			global $wpdb_storage;
			
			// Extract table name from query
			if ( preg_match( '/FROM\s+(\w+)/i', $query, $matches ) ) {
				$table = $matches[1];
				if ( isset( $wpdb_storage[ $table ] ) ) {
					$results = array();
					
					// Apply WHERE conditions if present
					if ( preg_match( '/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $query, $where_matches ) ) {
						foreach ( $wpdb_storage[ $table ] as $row ) {
							if ( $this->matches_where( $row, $where_matches[1] ) ) {
								$results[] = $row;
							}
						}
					} else {
						$results = array_values( $wpdb_storage[ $table ] );
					}
					
					// Apply LIMIT and OFFSET if present
					$offset = 0;
					$limit = null;
					
					if ( preg_match( '/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', $query, $limit_matches ) ) {
						$limit = (int) $limit_matches[1];
						if ( isset( $limit_matches[2] ) ) {
							$offset = (int) $limit_matches[2];
						}
						$results = array_slice( $results, $offset, $limit );
					}
					
					return array_map( function( $row ) use ( $output ) {
						return $output === ARRAY_A ? $row : (object) $row;
					}, $results );
				}
			}
			
			return array();
		}

		public function get_var( $query ) {
			global $wpdb_storage;
			
			// Handle COUNT queries
			if ( preg_match( '/SELECT\s+COUNT\(\*\)\s+FROM\s+(\w+)/i', $query, $matches ) ) {
				$table = $matches[1];
				// If table doesn't exist, return 0
				if ( ! isset( $wpdb_storage[ $table ] ) ) {
					return 0;
				}
				// If table exists but is empty, return 0
				if ( empty( $wpdb_storage[ $table ] ) ) {
					return 0;
				}
				// Apply WHERE conditions if present
				if ( preg_match( '/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $query, $where_matches ) ) {
					$count = 0;
					foreach ( $wpdb_storage[ $table ] as $row ) {
						if ( $this->matches_where( $row, $where_matches[1] ) ) {
							$count++;
						}
					}
					return $count;
				}
				// No WHERE clause - return total count
				return count( $wpdb_storage[ $table ] );
			}
			
			// Handle SELECT specific column queries
			if ( preg_match( '/SELECT\s+(\w+)\s+FROM\s+(\w+)/i', $query, $matches ) ) {
				$column = $matches[1];
				$table = $matches[2];
				
				// If table doesn't exist or is empty, return null
				if ( ! isset( $wpdb_storage[ $table ] ) || empty( $wpdb_storage[ $table ] ) ) {
					return null;
				}
				
				// Apply WHERE conditions if present
				if ( preg_match( '/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $query, $where_matches ) ) {
					foreach ( $wpdb_storage[ $table ] as $row ) {
						if ( $this->matches_where( $row, $where_matches[1] ) ) {
							return isset( $row[ $column ] ) ? $row[ $column ] : null;
						}
					}
					return null;
				}
				
				// No WHERE clause - return first row's column value
				$row = reset( $wpdb_storage[ $table ] );
				return isset( $row[ $column ] ) ? $row[ $column ] : null;
			}
			
			// Handle SELECT * queries
			$results = $this->get_results( $query, ARRAY_A );
			if ( ! empty( $results ) ) {
				$first_row = reset( $results );
				return reset( $first_row );
			}
			
			return null;
		}

		public function get_col( $query, $column_offset = 0 ) {
			global $wpdb_storage;
			
			// Extract table name from query
			if ( preg_match( '/FROM\s+(\w+)/i', $query, $matches ) ) {
				$table = $matches[1];
				if ( isset( $wpdb_storage[ $table ] ) ) {
					$results = array();
					
					// Apply WHERE conditions if present
					if ( preg_match( '/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $query, $where_matches ) ) {
						foreach ( $wpdb_storage[ $table ] as $row ) {
							if ( $this->matches_where( $row, $where_matches[1] ) ) {
								$results[] = $row;
							}
						}
					} else {
						$results = array_values( $wpdb_storage[ $table ] );
					}
					
					// Apply ORDER BY if present
					if ( preg_match( '/ORDER\s+BY\s+(\w+)\s+(ASC|DESC)?/i', $query, $order_matches ) ) {
						$order_column = $order_matches[1];
						$order_direction = isset( $order_matches[2] ) ? strtoupper( $order_matches[2] ) : 'ASC';
						usort( $results, function( $a, $b ) use ( $order_column, $order_direction ) {
							$val_a = isset( $a[ $order_column ] ) ? $a[ $order_column ] : null;
							$val_b = isset( $b[ $order_column ] ) ? $b[ $order_column ] : null;
							$cmp = $val_a <=> $val_b;
							return $order_direction === 'DESC' ? -$cmp : $cmp;
						} );
					}
					
					// Apply LIMIT and OFFSET if present
					if ( preg_match( '/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', $query, $limit_matches ) ) {
						$limit = (int) $limit_matches[1];
						$offset = isset( $limit_matches[2] ) ? (int) $limit_matches[2] : 0;
						$results = array_slice( $results, $offset, $limit );
					}
					
					// Extract the specified column from each row
					$column_values = array();
					foreach ( $results as $row ) {
						$row_values = array_values( $row );
						if ( isset( $row_values[ $column_offset ] ) ) {
							$column_values[] = $row_values[ $column_offset ];
						}
					}
					
					return $column_values;
				}
			}
			
			return array();
		}

		public function get_row( $query, $output = OBJECT ) {
			global $wpdb_storage;
			
			// Extract table name from query
			if ( preg_match( '/FROM\s+(\w+)/i', $query, $matches ) ) {
				$table = $matches[1];
				if ( isset( $wpdb_storage[ $table ] ) && ! empty( $wpdb_storage[ $table ] ) ) {
					// Apply WHERE conditions if present
					if ( preg_match( '/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $query, $where_matches ) ) {
						foreach ( $wpdb_storage[ $table ] as $row ) {
							if ( $this->matches_where( $row, $where_matches[1] ) ) {
								return $output === ARRAY_A ? $row : (object) $row;
							}
						}
						return null;
					}
					
					$row = reset( $wpdb_storage[ $table ] );
					return $output === ARRAY_A ? $row : (object) $row;
				}
			}
			
			return null;
		}

		public function query( $query ) {
			global $wpdb_storage;
			
			// Handle INSERT ... ON DUPLICATE KEY UPDATE queries
			if ( preg_match( '/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s+(.+?)\s+ON\s+DUPLICATE\s+KEY\s+UPDATE\s+(.+?)$/is', $query, $matches ) ) {
				$table = $matches[1];
				$columns_str = $matches[2];
				$values_section = $matches[3];
				$update_clause = $matches[4];
				
				// Clean up column names (remove backticks and whitespace)
				$columns = array_map( function( $col ) {
					return trim( str_replace( '`', '', $col ) );
				}, explode( ',', $columns_str ) );
				
				if ( ! isset( $wpdb_storage[ $table ] ) ) {
					$wpdb_storage[ $table ] = array();
				}
				
				// Simple approach: just extract the first complete value tuple
				// Match: (value1, value2, ..., valueN)
				if ( preg_match( '/^\s*\((.+?)\)(?:\s*,\s*\(|$)/s', $values_section, $tuple_match ) ) {
					$values_str = $tuple_match[1];
					
					// Parse values using the simple regex approach
					$values = array();
					if ( preg_match_all( "/'([^']*)'|(\d+)/", $values_str, $value_matches, PREG_SET_ORDER ) ) {
						foreach ( $value_matches as $match ) {
							$values[] = isset( $match[1] ) && $match[1] !== '' ? $match[1] : $match[2];
						}
					}
					
					if ( count( $columns ) === count( $values ) ) {
						$data = array_combine( $columns, $values );
						
						// Check for duplicate based on url_hash (unique key for 404 log)
						$duplicate_id = null;
						if ( isset( $data['url_hash'] ) ) {
							foreach ( $wpdb_storage[ $table ] as $id => $row ) {
								if ( isset( $row['url_hash'] ) && $row['url_hash'] === $data['url_hash'] ) {
									$duplicate_id = $id;
									break;
								}
							}
						}
						
						if ( $duplicate_id !== null ) {
							// Duplicate found - apply UPDATE clause
							// Parse UPDATE clause: hit_count = hit_count + VALUES(hit_count), last_seen = VALUES(last_seen)
							if ( preg_match_all( '/(\w+)\s*=\s*(\w+)\s*\+\s*VALUES\((\w+)\)|(\w+)\s*=\s*VALUES\((\w+)\)/i', $update_clause, $update_matches, PREG_SET_ORDER ) ) {
								foreach ( $update_matches as $match ) {
									if ( isset( $match[1] ) && $match[1] !== '' ) {
										// Increment: hit_count = hit_count + VALUES(hit_count)
										$field = $match[1];
										$value_field = $match[3];
										if ( isset( $data[ $value_field ] ) ) {
											$wpdb_storage[ $table ][ $duplicate_id ][ $field ] = 
												( $wpdb_storage[ $table ][ $duplicate_id ][ $field ] ?? 0 ) + $data[ $value_field ];
										}
									} elseif ( isset( $match[4] ) && $match[4] !== '' ) {
										// Direct assignment: last_seen = VALUES(last_seen)
										$field = $match[4];
										$value_field = $match[5];
										if ( isset( $data[ $value_field ] ) ) {
											$wpdb_storage[ $table ][ $duplicate_id ][ $field ] = $data[ $value_field ];
										}
									}
								}
							}
						} else {
							// No duplicate - insert new row
							if ( ! isset( $data['id'] ) ) {
								$data['id'] = $this->insert_id++;
							} else {
								$this->insert_id = max( $this->insert_id, $data['id'] + 1 );
							}
							$wpdb_storage[ $table ][ $data['id'] ] = $data;
						}
					}
				}
				
				return 1; // Return number of rows affected
			}
			
			// Handle DELETE queries
			if ( preg_match( '/DELETE\s+FROM\s+(\w+)/i', $query, $matches ) ) {
				$table = $matches[1];
				if ( isset( $wpdb_storage[ $table ] ) ) {
					// Apply WHERE conditions if present
					if ( preg_match( '/WHERE\s+(.+?)$/is', $query, $where_matches ) ) {
						$deleted = 0;
						foreach ( $wpdb_storage[ $table ] as $id => $row ) {
							if ( $this->matches_where( $row, $where_matches[1] ) ) {
								unset( $wpdb_storage[ $table ][ $id ] );
								$deleted++;
							}
						}
						return $deleted;
					}
					// Delete all if no WHERE clause
					$count = count( $wpdb_storage[ $table ] );
					$wpdb_storage[ $table ] = array();
					return $count;
				}
			}
			
			// Handle UPDATE queries
			if ( preg_match( '/UPDATE\s+(\w+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+?))?$/is', $query, $matches ) ) {
				$table = $matches[1];
				$set_clause = $matches[2];
				$where_clause = isset( $matches[3] ) ? $matches[3] : '';
				
				if ( isset( $wpdb_storage[ $table ] ) ) {
					$updated = 0;
					
					// Parse SET clause - handle both simple assignments and increment operations
					$set_operations = array();
					
					// Match increment operations like: hit_count = hit_count + 1
					if ( preg_match_all( "/(\w+)\s*=\s*(\w+)\s*\+\s*(\d+)/i", $set_clause, $increment_matches, PREG_SET_ORDER ) ) {
						foreach ( $increment_matches as $match ) {
							$field = $match[1];
							$increment_value = (int) $match[3];
							$set_operations[ $field ] = array( 'type' => 'increment', 'value' => $increment_value );
						}
					}
					
					// Match simple assignments like: field = 'value' or field = 123 or field = NOW()
					if ( preg_match_all( "/(\w+)\s*=\s*(?:'([^']*)'|(\d+)|NOW\(\))/i", $set_clause, $set_matches, PREG_SET_ORDER ) ) {
						foreach ( $set_matches as $match ) {
							$field = $match[1];
							// Skip if already handled as increment
							if ( isset( $set_operations[ $field ] ) ) {
								continue;
							}
							if ( isset( $match[2] ) && $match[2] !== '' ) {
								$set_operations[ $field ] = array( 'type' => 'assign', 'value' => $match[2] );
							} elseif ( isset( $match[3] ) ) {
								$set_operations[ $field ] = array( 'type' => 'assign', 'value' => $match[3] );
							} elseif ( stripos( $match[0], 'NOW()' ) !== false ) {
								$set_operations[ $field ] = array( 'type' => 'assign', 'value' => gmdate( 'Y-m-d H:i:s' ) );
							}
						}
					}
					
					// Apply updates
					foreach ( $wpdb_storage[ $table ] as $id => &$row ) {
						if ( empty( $where_clause ) || $this->matches_where( $row, $where_clause ) ) {
							foreach ( $set_operations as $field => $operation ) {
								if ( $operation['type'] === 'increment' ) {
									$row[ $field ] = ( $row[ $field ] ?? 0 ) + $operation['value'];
								} else {
									$row[ $field ] = $operation['value'];
								}
							}
							$updated++;
						}
					}
					
					return $updated;
				}
			}
			
			// Handle INSERT queries
			if ( preg_match( '/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/is', $query, $matches ) ) {
				$table = $matches[1];
				$columns = array_map( 'trim', explode( ',', $matches[2] ) );
				$values_str = $matches[3];
				
				// Parse values (handle quoted strings and numbers)
				$values = array();
				if ( preg_match_all( "/'([^']*)'|(\d+)/", $values_str, $value_matches, PREG_SET_ORDER ) ) {
					foreach ( $value_matches as $match ) {
						$values[] = isset( $match[1] ) && $match[1] !== '' ? $match[1] : $match[2];
					}
				}
				
				if ( count( $columns ) === count( $values ) ) {
					$data = array_combine( $columns, $values );
					return $this->insert( $table, $data );
				}
			}
			
			return 0;
		}

		public function insert( $table, $data, $format = null ) {
			global $wpdb_storage;
			
			if ( ! isset( $wpdb_storage[ $table ] ) ) {
				$wpdb_storage[ $table ] = array();
			}
			
			// Apply default values for meowseo_logs table
			if ( strpos( $table, 'meowseo_logs' ) !== false ) {
				if ( ! isset( $data['hit_count'] ) ) {
					$data['hit_count'] = 1;
				}
				if ( ! isset( $data['created_at'] ) ) {
					$data['created_at'] = gmdate( 'Y-m-d H:i:s' );
				}
			}
			
			// Auto-increment ID if not provided
			if ( ! isset( $data['id'] ) ) {
				$data['id'] = $this->insert_id++;
			} else {
				$this->insert_id = max( $this->insert_id, $data['id'] + 1 );
			}
			
			$wpdb_storage[ $table ][ $data['id'] ] = $data;
			
			return 1;
		}

		public function update( $table, $data, $where, $format = null, $where_format = null ) {
			global $wpdb_storage;
			
			if ( ! isset( $wpdb_storage[ $table ] ) ) {
				return false;
			}
			
			$updated = 0;
			foreach ( $wpdb_storage[ $table ] as $id => &$row ) {
				$matches = true;
				foreach ( $where as $key => $value ) {
					if ( ! isset( $row[ $key ] ) || $row[ $key ] != $value ) {
						$matches = false;
						break;
					}
				}
				
				if ( $matches ) {
					foreach ( $data as $key => $value ) {
						$row[ $key ] = $value;
					}
					$updated++;
				}
			}
			
			return $updated;
		}

		private function matches_where( $row, $where_clause ) {
			// Simple WHERE clause matching for testing
			// This is a simplified implementation for common cases
			
			// Handle multiple AND conditions
			$conditions = preg_split( '/\s+AND\s+/i', $where_clause );
			
			foreach ( $conditions as $condition ) {
				$condition = trim( $condition );
				
				// Handle payload = 'json_value' conditions (for JSON comparison)
				// Match both with and without escaped quotes
				if ( preg_match( "/payload\s*=\s*'(.+?)'/", $condition, $matches ) ) {
					$expected_json = $matches[1];
					// Remove any escaping that might have been added
					$expected_json = stripslashes( $expected_json );
					$row_payload = isset( $row['payload'] ) ? stripslashes( $row['payload'] ) : '';
					if ( $row_payload !== $expected_json ) {
						return false;
					}
					continue;
				}
				
				// Handle status = 'value' conditions
				if ( preg_match( "/(\w+)\s*=\s*'([^']+)'/", $condition, $matches ) ) {
					$field = $matches[1];
					$value = $matches[2];
					if ( ! isset( $row[ $field ] ) || $row[ $field ] !== $value ) {
						return false;
					}
					continue;
				}
				
				// Handle numeric comparisons (id = value)
				if ( preg_match( '/(\w+)\s*=\s*(\d+)/', $condition, $matches ) ) {
					$field = $matches[1];
					$value = $matches[2];
					if ( ! isset( $row[ $field ] ) || $row[ $field ] != $value ) {
						return false;
					}
					continue;
				}
				
				// Handle IS NULL conditions
				if ( preg_match( '/(\w+)\s+IS\s+NULL/i', $condition, $matches ) ) {
					$field = $matches[1];
					if ( isset( $row[ $field ] ) && $row[ $field ] !== null ) {
						return false;
					}
					continue;
				}
				
				// Handle >= comparisons (created_at >= '2024-01-01 00:00:00')
				if ( preg_match( "/(\w+)\s*>=\s*'([^']+)'/i", $condition, $matches ) ) {
					$field = $matches[1];
					$value = $matches[2];
					if ( ! isset( $row[ $field ] ) ) {
						return false;
					}
					// Compare as strings (works for timestamps in Y-m-d H:i:s format)
					if ( $row[ $field ] < $value ) {
						return false;
					}
					continue;
				}
				
				// Handle <= comparisons (retry_after <= NOW())
				if ( preg_match( '/(\w+)\s*<=\s*NOW\(\)/i', $condition, $matches ) ) {
					$field = $matches[1];
					// For testing, treat NULL as always ready
					if ( ! isset( $row[ $field ] ) || $row[ $field ] === null ) {
						continue;
					}
					// Compare timestamps
					$row_time = strtotime( $row[ $field ] );
					if ( $row_time > time() ) {
						return false;
					}
					continue;
				}
				
				// Handle OR conditions within parentheses
				if ( preg_match( '/\((.+?)\)/i', $condition, $matches ) ) {
					$or_conditions = preg_split( '/\s+OR\s+/i', $matches[1] );
					$or_match = false;
					foreach ( $or_conditions as $or_cond ) {
						if ( $this->matches_where( $row, $or_cond ) ) {
							$or_match = true;
							break;
						}
					}
					if ( ! $or_match ) {
						return false;
					}
					continue;
				}
			}
			
			return true;
		}

		public function get_charset_collate() {
			return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		}

		public function esc_like( $data ) {
			return addcslashes( $data, '\\_%' );
		}
	};

}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries = '', $execute = true ) {
		return array();
	}
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

if ( ! function_exists( 'add_post_meta' ) ) {
	function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
		global $wp_postmeta_storage;
		$post_id = (int) $post_id;

		if ( ! isset( $wp_postmeta_storage[ $post_id ] ) ) {
			$wp_postmeta_storage[ $post_id ] = array();
		}

		// If unique is true and key already exists, return false
		if ( $unique && isset( $wp_postmeta_storage[ $post_id ][ $meta_key ] ) ) {
			return false;
		}

		// If key doesn't exist, create it
		if ( ! isset( $wp_postmeta_storage[ $post_id ][ $meta_key ] ) ) {
			$wp_postmeta_storage[ $post_id ][ $meta_key ] = array();
		}

		// Add the value
		$wp_postmeta_storage[ $post_id ][ $meta_key ][] = $meta_value;
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

if ( ! function_exists( 'get_home_url' ) ) {
	function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
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
		global $wp_query;
		return isset( $wp_query->queried_object ) ? $wp_query->queried_object : null;
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
		global $wp_query;
		return isset( $wp_query->is_category ) && $wp_query->is_category;
	}
}

if ( ! function_exists( 'is_tag' ) ) {
	function is_tag( $tag = '' ) {
		global $wp_query;
		return isset( $wp_query->is_tag ) && $wp_query->is_tag;
	}
}

if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $taxonomy = '', $term = '' ) {
		global $wp_query;
		return isset( $wp_query->is_tax ) && $wp_query->is_tax;
	}
}

if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		global $wp_query;
		return isset( $wp_query->is_front_page ) && $wp_query->is_front_page;
	}
}

if ( ! function_exists( 'is_home' ) ) {
	function is_home() {
		global $wp_query;
		return isset( $wp_query->is_home ) && $wp_query->is_home;
	}
}

if ( ! function_exists( 'is_author' ) ) {
	function is_author( $author = '' ) {
		global $wp_query;
		return isset( $wp_query->is_author ) && $wp_query->is_author;
	}
}

if ( ! function_exists( 'is_post_type_archive' ) ) {
	function is_post_type_archive( $post_types = '' ) {
		global $wp_query;
		return isset( $wp_query->is_post_type_archive ) && $wp_query->is_post_type_archive;
	}
}

if ( ! function_exists( 'is_search' ) ) {
	function is_search() {
		global $wp_query;
		return isset( $wp_query->is_search ) && $wp_query->is_search;
	}
}

if ( ! function_exists( 'is_attachment' ) ) {
	function is_attachment() {
		global $wp_query;
		return isset( $wp_query->is_attachment ) && $wp_query->is_attachment;
	}
}

if ( ! function_exists( 'is_date' ) ) {
	function is_date() {
		global $wp_query;
		return isset( $wp_query->is_date ) && $wp_query->is_date;
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

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		if ( $output === 'objects' ) {
			return array(
				'post' => (object) array( 'name' => 'post', 'label' => 'Posts' ),
				'page' => (object) array( 'name' => 'page', 'label' => 'Pages' ),
			);
		}
		return array( 'post', 'page' );
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

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( $selected === $current ) ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( $checked === $current ) ? ' checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data; // Simplified for testing
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

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1; // Default test user ID
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

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		// Mock function for testing
		return array(
			'response' => array( 'code' => 200 ),
			'body' => wp_json_encode( array( 'success' => true ) ),
		);
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = array() ) {
		// Mock function for testing
		return array(
			'response' => array( 'code' => 200 ),
			'body' => wp_json_encode( array( 'success' => true ) ),
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_array( $response ) && isset( $response['response']['code'] ) ) {
			return $response['response']['code'];
		}
		return 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_array( $response ) && isset( $response['body'] ) ) {
			return $response['body'];
		}
		return '';
	}
}

if ( ! function_exists( 'wp_redirect' ) ) {
	function wp_redirect( $location, $status = 302 ) {
		// Mock function for testing
		return true;
	}
}

// Note: wp_upload_dir() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// Note: trailingslashit() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// Note: wp_mkdir_p() is intentionally not defined here to allow Brain\Monkey to mock it in tests
// These functions should ONLY be mocked via Brain\Monkey in individual tests to avoid conflicts

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql', $gmt = 0 ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

// In-memory theme support storage for testing
global $wp_theme_features;
if ( ! isset( $wp_theme_features ) ) {
	$wp_theme_features = array();
}

if ( ! function_exists( 'add_theme_support' ) ) {
	function add_theme_support( $feature, ...$args ) {
		global $wp_theme_features;
		$wp_theme_features[ $feature ] = $args;
		return true;
	}
}

if ( ! function_exists( 'remove_theme_support' ) ) {
	function remove_theme_support( $feature ) {
		global $wp_theme_features;
		if ( isset( $wp_theme_features[ $feature ] ) ) {
			unset( $wp_theme_features[ $feature ] );
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'current_theme_supports' ) ) {
	function current_theme_supports( $feature ) {
		global $wp_theme_features;
		return isset( $wp_theme_features[ $feature ] );
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
		private $status = 200;

		public function __construct( $data = null, $status = 200, $headers = array() ) {
			$this->data = $data;
			$this->status = $status;
			$this->headers = $headers;
		}

		public function get_data() {
			return $this->data;
		}

		public function set_data( $data ) {
			$this->data = $data;
		}

		public function get_status() {
			return $this->status;
		}

		public function set_status( $status ) {
			$this->status = $status;
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
			global $wp_cache_storage, $wp_posts_storage, $wp_postmeta_storage, $wp_filter, $wp_options_storage;
			$wp_cache_storage = array();
			$wp_posts_storage = array();
			$wp_postmeta_storage = array();
			$wp_filter = array();
			$wp_options_storage = array();
			
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

// Mock WordPress conditional functions for Breadcrumbs testing
if ( ! function_exists( 'is_404' ) ) {
	function is_404() {
		return false;
	}
}

if ( ! function_exists( 'is_search' ) ) {
	function is_search() {
		return false;
	}
}

if ( ! function_exists( 'is_archive' ) ) {
	function is_archive() {
		global $wp_query;
		return isset( $wp_query->is_archive ) && $wp_query->is_archive;
	}
}

if ( ! function_exists( 'is_page' ) ) {
	function is_page() {
		return false;
	}
}

if ( ! function_exists( 'is_single' ) ) {
	function is_single() {
		return false;
	}
}

if ( ! function_exists( 'is_category' ) ) {
	function is_category() {
		return false;
	}
}

if ( ! function_exists( 'is_tag' ) ) {
	function is_tag() {
		return false;
	}
}

if ( ! function_exists( 'is_tax' ) ) {
	function is_tax() {
		return false;
	}
}

if ( ! function_exists( 'is_date' ) ) {
	function is_date() {
		return false;
	}
}

if ( ! function_exists( 'is_year' ) ) {
	function is_year() {
		return false;
	}
}

if ( ! function_exists( 'is_month' ) ) {
	function is_month() {
		return false;
	}
}

if ( ! function_exists( 'is_day' ) ) {
	function is_day() {
		return false;
	}
}

if ( ! function_exists( 'is_author' ) ) {
	function is_author() {
		return false;
	}
}

if ( ! function_exists( 'is_post_type_archive' ) ) {
	function is_post_type_archive() {
		return false;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
		return null;
	}
}

if ( ! function_exists( 'get_the_category' ) ) {
	function get_the_category( $post_id = 0 ) {
		return array();
	}
}

if ( ! function_exists( 'get_category_link' ) ) {
	function get_category_link( $category_id ) {
		return 'https://example.com/category/';
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0, $leavename = false ) {
		return 'https://example.com/post/';
	}
}

if ( ! function_exists( 'get_post_ancestors' ) ) {
	function get_post_ancestors( $post_id ) {
		return array();
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		return 'Post Title';
	}
}

if ( ! function_exists( 'get_queried_object' ) ) {
	function get_queried_object() {
		return null;
	}
}

if ( ! function_exists( 'get_term_link' ) ) {
	function get_term_link( $term, $taxonomy = '' ) {
		return 'https://example.com/term/';
	}
}

if ( ! function_exists( 'get_the_time' ) ) {
	function get_the_time( $format = '', $post = null ) {
		return '2024-01-01';
	}
}

if ( ! function_exists( 'get_year_link' ) ) {
	function get_year_link( $year ) {
		return 'https://example.com/year/';
	}
}

if ( ! function_exists( 'get_month_link' ) ) {
	function get_month_link( $year, $month ) {
		return 'https://example.com/month/';
	}
}

if ( ! function_exists( 'get_day_link' ) ) {
	function get_day_link( $year, $month, $day ) {
		return 'https://example.com/day/';
	}
}

if ( ! function_exists( 'get_author_posts_url' ) ) {
	function get_author_posts_url( $author_id, $author_nicename = '' ) {
		return 'https://example.com/author/';
	}
}

if ( ! function_exists( 'get_post_type_archive_link' ) ) {
	function get_post_type_archive_link( $post_type ) {
		return 'https://example.com/archive/';
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( $post_type ) {
		return null;
	}
}

if ( ! function_exists( 'get_search_query' ) ) {
	function get_search_query( $escaped = true ) {
		return 'search query';
	}
}

if ( ! function_exists( 'get_search_link' ) ) {
	function get_search_link( $query = '' ) {
		return 'https://example.com/?s=search';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		return 'https://example.com/';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
		$atts = (array) $atts;
		$out = array();
		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) ) {
				$out[ $name ] = $atts[ $name ];
			} else {
				$out[ $name ] = $default;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {
		global $shortcode_tags;
		if ( ! isset( $shortcode_tags ) ) {
			$shortcode_tags = array();
		}
		$shortcode_tags[ $tag ] = $callback;
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class, $fallback = '' ) {
		// Strip out any %-encoded octets.
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

		// Limit to A-Z, a-z, 0-9, '_', '-'.
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		if ( '' === $sanitized && $fallback ) {
			return sanitize_html_class( $fallback );
		}

		return $sanitized;
	}
}

// Global test override for is_admin
global $test_is_admin;
$test_is_admin = false;

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		global $test_is_admin;
		
		// Allow tests to override the is_admin check
		if ( $test_is_admin !== null ) {
			return $test_is_admin;
		}
		
		return false;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
		return true;
	}
}

if ( ! function_exists( 'get_admin_page_title' ) ) {
	function get_admin_page_title() {
		return 'Test Page Title';
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new \Exception( $message );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
		$nonce_field = '<input type="hidden" name="' . $name . '" value="test_nonce" />';
		if ( $echo ) {
			echo $nonce_field;
		}
		return $nonce_field;
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = array() ) {
		$button = '<input type="submit" name="' . esc_attr( $name ) . '" value="' . esc_attr( $text ) . '" class="button button-' . esc_attr( $type ) . '" />';
		if ( $wrap ) {
			echo '<p class="submit">' . $button . '</p>';
		} else {
			echo $button;
		}
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		global $wp_settings_errors;
		if ( ! isset( $wp_settings_errors ) ) {
			$wp_settings_errors = array();
		}
		$wp_settings_errors[] = array(
			'setting' => $setting,
			'code'    => $code,
			'message' => $message,
			'type'    => $type,
		);
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) {
		return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, $status_code = null ) {
		$response = array( 'success' => false );
		if ( isset( $data ) ) {
			$response['data'] = $data;
		}
		echo json_encode( $response );
		exit;
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, $status_code = null ) {
		$response = array( 'success' => true );
		if ( isset( $data ) ) {
			$response['data'] = $data;
		}
		echo json_encode( $response );
		exit;
	}
}

if ( ! function_exists( 'paginate_links' ) ) {
	function paginate_links( $args = '' ) {
		return '<div class="pagination">1 2 3</div>';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = false ) {
		if ( is_array( $args ) ) {
			return http_build_query( $args );
		}
		return $args;
	}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {
		// Mock function
	}
}

if ( ! function_exists( 'wp_redirect' ) ) {
	function wp_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
		// Mock function - in tests we don't actually redirect
		return true;
	}
}

if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() {
		return false;
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules( $hard = true ) {
		// Mock function
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		// Mock function - return a mock response
		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => '{}',
		);
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		// Mock function - return a mock response
		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => '{}',
		);
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = array() ) {
		// Mock function - return a mock response
		return array(
			'response' => array(
				'code' => 200,
			),
			'body' => '{}',
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return 0;
		}
		return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		// Mock function
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		// Mock function
		return true;
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( $handle, $object_name, $l10n ) {
		// Mock function
		return true;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		// Mock function - return a test nonce
		return 'test_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '', $scheme = 'rest' ) {
		return 'http://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_admin_page_title' ) ) {
	function get_admin_page_title() {
		return 'Admin Page Title';
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) {
		// Mock function
		return $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
		// Mock function
		return $menu_slug;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		// Mock function - just echo the message for testing
		echo $message;
		// Don't actually exit in tests
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text = '', $num_words = 55, $more = null ) {
		if ( null === $more ) {
			$more = __( '&hellip;' );
		}

		$original_text = $text;
		$text = wp_strip_all_tags( $text );

		/* translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter the number of characters in each word. Otherwise, enter 1. Do not use 0. */
		$word_count = 'characters_per_word' === _x( 'words_per_minute', 'translation-speed-measure' ) ? mb_strlen( $text ) : str_word_count( $text );

		if ( $word_count <= $num_words ) {
			return $original_text;
		}

		$words = preg_split( '/\s+/', $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
		array_pop( $words );

		$text = implode( ' ', $words ) . $more;

		return $text;
	}
}

if ( ! function_exists( 'wp_tempnam' ) ) {
	function wp_tempnam( $filename = '', $dir = '' ) {
		if ( empty( $dir ) ) {
			$dir = sys_get_temp_dir();
		}

		$filename = basename( $filename );
		if ( empty( $filename ) ) {
			$filename = 'tmp';
		}

		$temp_file = tempnam( $dir, $filename );
		return $temp_file;
	}
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
	function has_post_thumbnail( $post = null ) {
		return false;
	}
}

if ( ! function_exists( 'set_post_thumbnail' ) ) {
	function set_post_thumbnail( $post, $thumbnail_id = null ) {
		return true;
	}
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
	function wp_get_attachment_url( $attachment_id = 0 ) {
		return 'http://example.com/wp-content/uploads/test-image.png';
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		// Mock function for testing
		return array(
			'body'     => file_get_contents( $url ),
			'response' => array( 'code' => 200 ),
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'media_handle_sideload' ) ) {
	function media_handle_sideload( $file_array, $post_id, $desc = null, $post_data = array() ) {
		// Mock function - create a fake attachment
		static $attachment_id = 0;
		$attachment_id++;
		return $attachment_id;
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

if ( ! function_exists( 'wp_get_post_categories' ) ) {
	function wp_get_post_categories( $post_id = 0, $args = array() ) {
		return array();
	}
}

if ( ! function_exists( 'wp_get_post_tags' ) ) {
	function wp_get_post_tags( $post_id = 0, $args = array() ) {
		return array();
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context = 'default', $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'str_word_count' ) ) {
	function str_word_count( $string, $format = 0, $charlist = null ) {
		return \str_word_count( $string, $format, $charlist );
	}
}

if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $string, $encoding = null ) {
		return \mb_strlen( $string, $encoding );
	}
}

if ( ! function_exists( 'has_action' ) ) {
	function has_action( $hook, $callback = false ) {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return false;
		}
		if ( $callback === false ) {
			return ! empty( $wp_filter[ $hook ] );
		}
		foreach ( $wp_filter[ $hook ] as $filter ) {
			if ( $filter['callback'] === $callback ) {
				return $filter['priority'];
			}
		}
		return false;
	}
}

/**
 * Mock WP_Query class for testing.
 */
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		private array $query_vars = array();
		private bool $is_admin;
		private bool $is_main;
		public array $posts = array();
		public int $found_posts = 0;
		public int $post_count = 0;
		public int $current_post = -1;
		public bool $in_the_loop = false;

		/**
		 * Constructor - accepts either array of query args (WordPress standard) or individual params (for backward compatibility)
		 */
		public function __construct( $args = array(), string $order = '', bool $is_admin = true, bool $is_main = true ) {
			// Handle array of query args (WordPress standard)
			if ( is_array( $args ) ) {
				$this->query_vars = $args;
				$this->is_admin = $is_admin;
				$this->is_main = $is_main;
				
				// Simulate query execution for testing
				$this->execute_query();
			}
			// Handle individual params (backward compatibility for existing tests)
			elseif ( is_string( $args ) ) {
				$this->query_vars['orderby'] = $args;
				$this->query_vars['order'] = $order;
				$this->is_admin = $is_admin;
				$this->is_main = $is_main;
			}
		}

		/**
		 * Simulate query execution for testing
		 */
		private function execute_query(): void {
			global $wp_posts_storage;
			
			// If fields=ids, return post IDs
			if ( isset( $this->query_vars['fields'] ) && $this->query_vars['fields'] === 'ids' ) {
				// Get posts from storage
				if ( isset( $wp_posts_storage ) && ! empty( $wp_posts_storage ) ) {
					$this->posts = array_keys( $wp_posts_storage );
					$this->found_posts = count( $this->posts );
					$this->post_count = min( $this->found_posts, $this->query_vars['posts_per_page'] ?? 10 );
					
					// Apply pagination
					if ( isset( $this->query_vars['paged'] ) && $this->query_vars['paged'] > 1 ) {
						$offset = ( $this->query_vars['paged'] - 1 ) * ( $this->query_vars['posts_per_page'] ?? 10 );
						$this->posts = array_slice( $this->posts, $offset, $this->query_vars['posts_per_page'] ?? 10 );
						$this->post_count = count( $this->posts );
					} else {
						$this->posts = array_slice( $this->posts, 0, $this->query_vars['posts_per_page'] ?? 10 );
						$this->post_count = count( $this->posts );
					}
				}
			}
		}

		public function get( string $key, $default = '' ) {
			return $this->query_vars[ $key ] ?? $default;
		}

		public function set( string $key, $value ): void {
			$this->query_vars[ $key ] = $value;
		}

		public function is_main_query(): bool {
			return $this->is_main;
		}

		public function have_posts(): bool {
			return $this->current_post + 1 < $this->post_count;
		}

		public function the_post(): void {
			$this->current_post++;
			$this->in_the_loop = true;
		}

		public function rewind_posts(): void {
			$this->current_post = -1;
			$this->in_the_loop = false;
		}
	}
}


// In-memory termmeta storage for testing
global $wp_termmeta_storage;
if ( ! isset( $wp_termmeta_storage ) ) {
	$wp_termmeta_storage = array();
}

if ( ! function_exists( 'get_term_meta' ) ) {
	function get_term_meta( $term_id, $key = '', $single = false ) {
		global $wp_termmeta_storage;
		$term_id = (int) $term_id;

		if ( ! isset( $wp_termmeta_storage[ $term_id ] ) ) {
			return $single ? '' : array();
		}

		if ( empty( $key ) ) {
			return $wp_termmeta_storage[ $term_id ];
		}

		if ( $single ) {
			return isset( $wp_termmeta_storage[ $term_id ][ $key ] ) ? $wp_termmeta_storage[ $term_id ][ $key ][0] : '';
		}

		return isset( $wp_termmeta_storage[ $term_id ][ $key ] ) ? $wp_termmeta_storage[ $term_id ][ $key ] : array();
	}
}

if ( ! function_exists( 'update_term_meta' ) ) {
	function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
		global $wp_termmeta_storage;
		$term_id = (int) $term_id;

		if ( ! isset( $wp_termmeta_storage[ $term_id ] ) ) {
			$wp_termmeta_storage[ $term_id ] = array();
		}

		$wp_termmeta_storage[ $term_id ][ $meta_key ] = array( $meta_value );
		return true;
	}
}

if ( ! function_exists( 'delete_term_meta' ) ) {
	function delete_term_meta( $term_id, $meta_key = '', $meta_value = '' ) {
		global $wp_termmeta_storage;
		$term_id = (int) $term_id;

		if ( ! isset( $wp_termmeta_storage[ $term_id ] ) ) {
			return false;
		}

		if ( empty( $meta_key ) ) {
			unset( $wp_termmeta_storage[ $term_id ] );
			return true;
		}

		if ( isset( $wp_termmeta_storage[ $term_id ][ $meta_key ] ) ) {
			unset( $wp_termmeta_storage[ $term_id ][ $meta_key ] );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'is_archive' ) ) {
	function is_archive() {
		global $wp_query;
		return isset( $wp_query->is_archive ) && $wp_query->is_archive;
	}
}

if ( ! function_exists( 'is_404' ) ) {
	function is_404() {
		return false;
	}
}


if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
		// Convert to lowercase
		$title = strtolower( $title );
		// Replace spaces and underscores with hyphens
		$title = preg_replace( '/[\s_]+/', '-', $title );
		// Remove any characters that are not alphanumeric, hyphens, or underscores
		$title = preg_replace( '/[^a-z0-9\-_]/', '', $title );
		// Remove consecutive hyphens
		$title = preg_replace( '/-+/', '-', $title );
		// Remove leading/trailing hyphens
		$title = trim( $title, '-' );
		
		if ( empty( $title ) ) {
			return $fallback_title;
		}
		
		return $title;
	}
}

// In-memory site options storage for testing (multisite)
global $wp_site_options_storage;
if ( ! isset( $wp_site_options_storage ) ) {
	$wp_site_options_storage = array();
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {
		return false;
	}
}

if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $option, $default = false ) {
		global $wp_site_options_storage;
		return $wp_site_options_storage[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_site_option' ) ) {
	function update_site_option( $option, $value ) {
		global $wp_site_options_storage;
		$wp_site_options_storage[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_site_option' ) ) {
	function delete_site_option( $option ) {
		global $wp_site_options_storage;
		unset( $wp_site_options_storage[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'get_current_blog_id' ) ) {
	function get_current_blog_id() {
		return 1;
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return 'meowseo/meowseo.php';
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
		$nonce = wp_create_nonce( $action );
		$field = '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $nonce ) . '" />';
		if ( $echo ) {
			echo $field;
		}
		return $field;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'test_nonce_' . $action;
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $nonce === 'test_nonce_' . $action ? 1 : false;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new \Exception( $message );
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		// Mock function
	}
}

if ( ! function_exists( 'settings_errors' ) ) {
	function settings_errors( $setting = '', $sanitize = false, $hide_on_update = false ) {
		// Mock function
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		return '<button type="submit" name="' . esc_attr( $name ) . '" class="button button-' . esc_attr( $type ) . '">' . esc_html( $text ?? 'Save Changes' ) . '</button>';
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = '';
		if ( $checked === $current ) {
			$result = ' checked="checked"';
		}
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
		// Mock function
		return 'toplevel_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		// Mock function
		return $parent_slug . '_' . $menu_slug;
	}
}

if ( ! function_exists( 'get_role' ) ) {
	function get_role( $role ) {
		// Mock function - return a simple role object
		return (object) array(
			'name' => $role,
			'capabilities' => array(),
		);
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r =& $args;
		} else {
			return $defaults;
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $r );
		}
		return $r;
	}
}

if ( ! function_exists( 'has_blocks' ) ) {
	function has_blocks( $post = null ) {
		if ( is_string( $post ) ) {
			return strpos( $post, '<!-- wp:' ) !== false;
		}
		
		if ( is_object( $post ) && isset( $post->post_content ) ) {
			return strpos( $post->post_content, '<!-- wp:' ) !== false;
		}
		
		return false;
	}
}

// Global test override for get_current_screen
global $test_current_screen;
$test_current_screen = null;

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		global $test_current_screen;
		
		// Allow tests to override the current screen
		if ( $test_current_screen !== null ) {
			return $test_current_screen;
		}
		
		// Return a default screen object
		return (object) array(
			'id' => 'dashboard',
			'base' => 'dashboard',
			'post_type' => '',
			'taxonomy' => '',
		);
	}
}
