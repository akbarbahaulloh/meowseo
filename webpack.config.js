/**
 * WordPress webpack configuration
 *
 * Uses @wordpress/scripts default configuration.
 *
 * @package MeowSEO
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.js',
	},
	output: {
		...defaultConfig.output,
		path: __dirname + '/build',
	},
};
