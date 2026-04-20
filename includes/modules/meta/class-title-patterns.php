<?php
/**
 * Title Patterns Class
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

use MeowSEO\Options;

/**
 * Title_Patterns class
 *
 * Responsible for parsing, validating, and resolving title patterns
 * with variable substitution.
 */
class Title_Patterns {
	/**
	 * Supported variables
	 *
	 * @var array
	 */
	private const VARIABLES = array(
		'title',
		'sep',
		'site_name',
		'tagline',
		'page',
		'term_name',
		'term_description',
		'author_name',
		'current_year',
		'current_month',
		'category',
		'tag',
		'term',
		'date',
		'name',
		'searchphrase',
		'posttype',
	);

	/**
	 * Options instance
	 *
	 * @var Options
	 */
	private Options $options;

	/**
	 * Constructor
	 *
	 * @param Options $options Options instance.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Resolve pattern with context
	 *
	 * @param string $pattern Pattern string.
	 * @param array  $context Context array with variable values.
	 * @return string Resolved pattern.
	 */
	public function resolve( string $pattern, array $context ): string {
		return $this->replace_variables( $pattern, $context );
	}

	/**
	 * Parse pattern into structured representation
	 *
	 * @param string $pattern Pattern string.
	 * @return array|object Parsed structure or error object.
	 */
	public function parse( string $pattern ) {
		$result = array();
		$length = strlen( $pattern );
		$i      = 0;
		
		while ( $i < $length ) {
			// Check for variable start.
			if ( $pattern[ $i ] === '{' ) {
				// Find closing brace.
				$close_pos = strpos( $pattern, '}', $i );
				
				if ( $close_pos === false ) {
					return (object) array(
						'error'   => true,
						'message' => 'Unbalanced curly braces at position ' . $i,
					);
				}
				
				// Extract variable name.
				$var_name = substr( $pattern, $i + 1, $close_pos - $i - 1 );
				
				// Check for nested braces (invalid).
				if ( strpos( $var_name, '{' ) !== false ) {
					return (object) array(
						'error'   => true,
						'message' => 'Unbalanced curly braces at position ' . $i,
					);
				}
				
				// Validate variable name.
				if ( ! in_array( $var_name, self::VARIABLES, true ) ) {
					return (object) array(
						'error'   => true,
						'message' => 'Unsupported variable: ' . $var_name,
					);
				}
				
				// Add variable token.
				$result[] = array(
					'type' => 'variable',
					'name' => $var_name,
				);
				
				$i = $close_pos + 1;
			} else {
				// Collect literal text until next variable or end.
				$literal_start = $i;
				while ( $i < $length && $pattern[ $i ] !== '{' ) {
					// Check for unmatched closing brace.
					if ( $pattern[ $i ] === '}' ) {
						return (object) array(
							'error'   => true,
							'message' => 'Unbalanced curly braces at position ' . $i,
						);
					}
					$i++;
				}
				
				$literal = substr( $pattern, $literal_start, $i - $literal_start );
				
				// Add literal token.
				$result[] = array(
					'type'  => 'literal',
					'value' => $literal,
				);
			}
		}
		
		return $result;
	}

	/**
	 * Print structured pattern back to string
	 *
	 * @param array $structured Structured pattern.
	 * @return string Pattern string.
	 */
	public function print( array $structured ): string {
		$result = '';
		
		foreach ( $structured as $token ) {
			if ( ! isset( $token['type'] ) ) {
				continue;
			}
			
			if ( $token['type'] === 'variable' && isset( $token['name'] ) ) {
				$result .= '{' . $token['name'] . '}';
			} elseif ( $token['type'] === 'literal' && isset( $token['value'] ) ) {
				$result .= $token['value'];
			}
		}
		
		return $result;
	}

	/**
	 * Get pattern for post type
	 *
	 * @param string $post_type Post type.
	 * @return string Pattern string.
	 */
	public function get_pattern_for_post_type( string $post_type ): string {
		$patterns = $this->options->get( 'title_patterns', $this->get_default_patterns() );
		
		// Check for specific post type pattern.
		if ( isset( $patterns[ $post_type ] ) ) {
			return $patterns[ $post_type ];
		}
		
		// Fall back to 'post' pattern for custom post types.
		if ( isset( $patterns['post'] ) ) {
			return $patterns['post'];
		}
		
		// Final fallback.
		return '{title} {sep} {site_name}';
	}

	/**
	 * Get pattern for page type
	 *
	 * @param string $page_type Page type.
	 * @return string Pattern string.
	 */
	public function get_pattern_for_page_type( string $page_type ): string {
		$patterns = $this->options->get( 'title_patterns', $this->get_default_patterns() );
		
		// Check for specific page type pattern.
		if ( isset( $patterns[ $page_type ] ) ) {
			return $patterns[ $page_type ];
		}
		
		// Final fallback.
		return '{title} {sep} {site_name}';
	}

	/**
	 * Get pattern for archive type
	 *
	 * @param string $archive_type Archive type.
	 * @return string Pattern string.
	 */
	public function get_pattern_for_archive_type( string $archive_type ): string {
		$patterns = $this->options->get( 'title_patterns', $this->get_default_patterns() );
		
		// Check for specific archive type pattern.
		if ( isset( $patterns[ $archive_type ] ) ) {
			return $patterns[ $archive_type ];
		}
		
		// Final fallback.
		return '{title} {sep} {site_name}';
	}

	/**
	 * Resolve archive variables
	 *
	 * Builds context array with archive-specific variables based on current page.
	 *
	 * @return array Context array with resolved archive variables.
	 */
	public function resolve_archive_variables(): array {
		$context = array();
		
		// Get page number for pagination.
		$paged = get_query_var( 'paged' );
		if ( $paged > 1 ) {
			$context['page_number'] = $paged;
		}
		
		// Category archive.
		if ( is_category() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->name ) ) {
				$context['category'] = $term->name;
				$context['term'] = $term->name;
			}
		}
		
		// Tag archive.
		if ( is_tag() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->name ) ) {
				$context['tag'] = $term->name;
				$context['term'] = $term->name;
			}
		}
		
		// Custom taxonomy archive.
		if ( is_tax() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->name ) ) {
				$context['term'] = $term->name;
			}
		}
		
		// Author archive.
		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author && isset( $author->display_name ) ) {
				$context['name'] = $author->display_name;
			}
		}
		
		// Search results.
		if ( is_search() ) {
			$context['searchphrase'] = get_search_query();
		}
		
		// Date archive.
		if ( is_date() ) {
			$context['date'] = $this->format_archive_date();
		}
		
		// Post type archive.
		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			if ( $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );
				if ( $post_type_obj && isset( $post_type_obj->labels->name ) ) {
					$context['posttype'] = $post_type_obj->labels->name;
				}
			}
		}
		
		return $context;
	}

	/**
	 * Format archive date
	 *
	 * Formats the current archive date based on year/month/day query vars.
	 *
	 * @return string Formatted date string.
	 */
	private function format_archive_date(): string {
		$year  = get_query_var( 'year' );
		$month = get_query_var( 'monthnum' );
		$day   = get_query_var( 'day' );
		
		if ( $day ) {
			// Day archive: "January 15, 2024".
			$date = gmdate( 'F j, Y', mktime( 0, 0, 0, $month, $day, $year ) );
		} elseif ( $month ) {
			// Month archive: "January 2024".
			$date = gmdate( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) );
		} elseif ( $year ) {
			// Year archive: "2024".
			$date = $year;
		} else {
			// Fallback.
			$date = gmdate( 'F Y' );
		}
		
		return $date;
	}

	/**
	 * Get default patterns
	 *
	 * @return array Default patterns by page type.
	 */
	public function get_default_patterns(): array {
		return array(
			'post'                   => '{title} {sep} {site_name}',
			'page'                   => '{title} {sep} {site_name}',
			'homepage'               => '{site_name} {sep} {tagline}',
			'category'               => '{term_name} Archives {sep} {site_name}',
			'tag'                    => '{term_name} Tag {sep} {site_name}',
			'author'                 => '{author_name} {sep} {site_name}',
			'date'                   => '{current_month} {current_year} Archives {sep} {site_name}',
			'search'                 => 'Search Results {sep} {site_name}',
			'404'                    => 'Page Not Found {sep} {site_name}',
			'attachment'             => '{title} {sep} {site_name}',
			'category_archive'       => '{category} Archives {sep} {site_name}',
			'tag_archive'            => '{tag} Tag {sep} {site_name}',
			'custom_taxonomy_archive' => '{term} {sep} {site_name}',
			'author_page'            => '{name} {sep} {site_name}',
			'search_results'         => 'Search Results for {searchphrase} {sep} {site_name}',
			'date_archive'           => '{date} Archives {sep} {site_name}',
			'404_page'               => 'Page Not Found {sep} {site_name}',
		);
	}

	/**
	 * Validate pattern
	 *
	 * @param string $pattern Pattern string.
	 * @return bool|object True if valid, error object if invalid.
	 */
	public function validate( string $pattern ) {
		$parsed = $this->parse( $pattern );
		
		// If parse returned an error object, return it.
		if ( is_object( $parsed ) && isset( $parsed->error ) ) {
			return $parsed;
		}
		
		return true;
	}

	/**
	 * Replace variables in pattern
	 *
	 * @param string $pattern Pattern string.
	 * @param array  $context Context array with variable values.
	 * @return string Pattern with variables replaced.
	 */
	private function replace_variables( string $pattern, array $context ): string {
		// Replace each variable in the pattern.
		foreach ( self::VARIABLES as $var_name ) {
			$placeholder = '{' . $var_name . '}';
			if ( strpos( $pattern, $placeholder ) !== false ) {
				$value   = $this->get_variable_value( $var_name, $context );
				$pattern = str_replace( $placeholder, $value, $pattern );
			}
		}
		
		return $pattern;
	}

	/**
	 * Get variable value from context
	 *
	 * @param string $var_name Variable name.
	 * @param array  $context  Context array.
	 * @return string Variable value.
	 */
	private function get_variable_value( string $var_name, array $context ): string {
		// Handle special variables.
		switch ( $var_name ) {
			case 'sep':
				return $this->options->get_separator();
			
			case 'site_name':
				return \get_bloginfo( 'name' );
			
			case 'tagline':
				return \get_bloginfo( 'description' );
			
			case 'current_year':
				return gmdate( 'Y' );
			
			case 'current_month':
				return gmdate( 'F' );
			
			case 'page':
				// Conditional: "Page N" when paginated, empty otherwise.
				if ( isset( $context['page_number'] ) && $context['page_number'] > 1 ) {
					return 'Page ' . $context['page_number'];
				}
				return '';
			
			case 'category':
				// Category name for category archives.
				if ( isset( $context['category'] ) ) {
					return $context['category'];
				}
				return '';
			
			case 'tag':
				// Tag name for tag archives.
				if ( isset( $context['tag'] ) ) {
					return $context['tag'];
				}
				return '';
			
			case 'term':
				// Generic term name for any taxonomy.
				if ( isset( $context['term'] ) ) {
					return $context['term'];
				}
				return '';
			
			case 'date':
				// Formatted archive date.
				if ( isset( $context['date'] ) ) {
					return $context['date'];
				}
				return '';
			
			case 'name':
				// Author display name.
				if ( isset( $context['name'] ) ) {
					return $context['name'];
				}
				return '';
			
			case 'searchphrase':
				// Search query.
				if ( isset( $context['searchphrase'] ) ) {
					return $context['searchphrase'];
				}
				return '';
			
			case 'posttype':
				// Post type label.
				if ( isset( $context['posttype'] ) ) {
					return $context['posttype'];
				}
				return '';
			
			default:
				// For all other variables, check context array.
				// Missing variables return empty string (Requirement 8.5).
				return isset( $context[ $var_name ] ) ? (string) $context[ $var_name ] : '';
		}
	}
}
