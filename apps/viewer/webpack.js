const { merge } = require('webpack-merge')
const webpack = require('webpack')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const babelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

const isTesting = !!process.env.TESTING

const config = {
	module: {
		rules: [
			{
				// vue-plyr uses .mjs file
				test: /\.m?js$/,
				loader: 'babel-loader',
				exclude: babelLoaderExcludeNodeModulesExcept([
					'camelcase',
					'fast-xml-parser',
					'hot-patcher',
					'vue-plyr',
					'webdav',
				]),
			},
			{
				test: /\.(png|jpg|gif|svg)$/,
				loader: 'url-loader',
			},
		],
	},
	plugins: [
		new webpack.DefinePlugin({ isTesting }),
	],
}

const mergedConfigs = merge(config, webpackConfig)

// Remove default js rule
const jsRuleIndex = mergedConfigs.module.rules.findIndex(rule => rule.test.toString() === '/\\.js$/')
mergedConfigs.module.rules.splice(jsRuleIndex, 1)

// Merge rules by replacing existing tests
module.exports = mergedConfigs
