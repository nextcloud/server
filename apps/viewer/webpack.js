const { merge } = require('webpack-merge')
const webpack = require('webpack')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const isTesting = !!process.env.TESTING

// Remove default js rule
webpackConfig.prod.module.rules.pop()

const config = {
	plugins: [
		new webpack.DefinePlugin({ isTesting }),
	],
	module: {
		rules: [
			{
				// vue-plyr uses .mjs file
				test: /\.m?js$/,
				loader: 'babel-loader',
				exclude: /node_modules(?!(\/|\\)(camelcase|fast-xml-parser|hot-patcher|vue-plyr|webdav)(\/|\\))/,
			},
			{
				test: /\.(png|jpg|gif|svg)$/,
				loader: 'url-loader',
			},
		],
	},
}

if (process.env.NODE_ENV === 'production') {
	module.exports = merge(webpackConfig.prod, config)
}
module.exports = merge(webpackConfig.dev, config)
