const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(process.cwd(), 'src/gutenberg', 'index.tsx'),
	},
	output: {
		path: path.resolve(process.cwd(), 'build'),
		filename: '[name].js',
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: ['.tsx', '.ts', '.js', '.jsx'],
		alias: {
			'@': path.resolve(__dirname, 'src'),
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
						loader: require.resolve('babel-loader'),
						options: {
							presets: [
								'@wordpress/babel-preset-default',
							],
						},
					},
					{
						loader: require.resolve('ts-loader'),
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
