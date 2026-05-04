const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		gutenberg: path.resolve( process.cwd(), 'src/gutenberg', 'index.tsx' ),
		'ai-sidebar': path.resolve( process.cwd(), 'src/ai', 'index.js' ),
		'admin-settings': path.resolve( process.cwd(), 'src', 'admin-settings.js' ),
		'admin-dashboard': path.resolve( process.cwd(), 'src', 'admin-dashboard.js' ),
		'admin-search-console': path.resolve( process.cwd(), 'src', 'admin-search-console.js' ),
		'admin-404-monitor': path.resolve( process.cwd(), 'src', 'admin-404-monitor.js' ),
		'admin-redirects': path.resolve( process.cwd(), 'src', 'admin-redirects.js' ),
		'schema-builder': path.resolve( process.cwd(), 'includes/modules/schema/src/builder', 'index.jsx' ),
		'schema-sidebar': path.resolve( process.cwd(), 'includes/modules/schema/src/sidebar', 'index.jsx' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'build' ),
		filename: '[name].js',
		clean: true,
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [ '.tsx', '.ts', '.js', '.jsx' ],
		alias: {
			'@': path.resolve( __dirname, 'src' ),
		},
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.tsx?$/,
				use: [
					{
						loader: require.resolve( 'babel-loader' ),
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
						},
					},
					{
						loader: require.resolve( 'ts-loader' ),
						options: {
							transpileOnly: true,
						},
					},
				],
				exclude: /node_modules/,
			},
		],
	},
};
