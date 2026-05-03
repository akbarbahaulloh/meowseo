const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'schema-builder': path.resolve(__dirname, 'src/builder/index.jsx'),
		'schema-sidebar': path.resolve(__dirname, 'src/sidebar/index.jsx'),
	},
	output: {
		path: path.resolve(__dirname, 'assets/js'),
		filename: '[name].js',
	},
};
