const { merge } = require('webpack-merge')
const common = require('./webpack.common.js')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = common.map(
	config => merge(config, {
		mode: 'production',
		devtool: 'source-map',
		optimization: {
			minimizer: [new TerserPlugin({ extractComments: false })],
		},
	})
)
