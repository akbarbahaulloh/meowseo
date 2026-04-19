<?php
/**
 * Fix Explanation Provider
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Analysis;

/**
 * Fix_Explanation_Provider class
 *
 * Provides actionable fix explanations for failing analyzer checks.
 */
class Fix_Explanation_Provider {
	/**
	 * Get explanation for analyzer result
	 *
	 * @param string $analyzer_id Analyzer identifier.
	 * @param array  $context     Context data for explanation.
	 * @return string Fix explanation.
	 */
	public function get_explanation( string $analyzer_id, array $context = array() ): string {
		// Implementation will be added in task 23.1
		return '';
	}
}
