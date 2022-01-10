const { merge } = require('webpack-merge')
const config = require('./webpack.common.js')

module.exports = merge(config, {
	mode: 'production',
	devtool: 'source-map',
})
