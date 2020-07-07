const merge = require('webpack-merge')
const common = require('./webpack.common.js')
const TerserPlugin = require('terser-webpack-plugin');

module.exports = common.map(
	config => merge(config, {
		mode: 'production',
		devtool: '#source-map',
		// This is required to keep IE11 compatibility (see #21316)
		optimization: {
			minimize: true,
			minimizer: [
				new TerserPlugin({
					terserOptions: {
						output: {
							keep_quoted_props: true,
						},
					},
				}),
			],
		},
	})
)
