const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = common.map(
	config => merge(config, {
		mode: 'development',
		devtool: 'cheap-source-map',
	})
)
