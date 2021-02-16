const fs = require('fs')
const path = require('path')
const { merge } = require('webpack-merge')
const webpack = require('webpack')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

const isTesting = !!process.env.TESTING
const plyrIcons = fs.readFileSync(path.join('node_modules', 'plyr', 'dist', 'plyr.svg'), { encoding: 'utf8' })

if (isTesting) {
	console.debug('TESTING MODE ENABLED')
}

const config = {
	module: {
		rules: [
			{
				// vue-plyr uses .mjs file
				test: /\.m?js$/,
				loader: 'babel-loader',
				exclude: BabelLoaderExcludeNodeModulesExcept([
					'@nextcloud/dialogs',
					'@nextcloud/event-bus',
					'camelcase',
					'fast-xml-parser',
					'hot-patcher',
					'semver',
					'vue-plyr',
					'webdav',
					'toastify-js',
				]),
				options: {
					plugins: ['add-module-exports'],
					presets: [
						/**
						 * From "add-module-exports" documentation:
						 * "webpack doesn't perform commonjs transformation for
						 * codesplitting. Need to set commonjs conversion."
						 */
						['@babel/env', { modules: 'commonjs' }],
					],
				},
			},
		],
	},
	plugins: [
		new webpack.DefinePlugin({
			isTesting,
			PLYR_ICONS: JSON.stringify(plyrIcons),
		}),
	],
}

const mergedConfigs = merge(config, webpackConfig)

// Remove default js rule
const jsRuleIndex = mergedConfigs.module.rules.findIndex(rule => rule.test.toString() === '/\\.js$/')
mergedConfigs.module.rules.splice(jsRuleIndex, 1)

// Merge rules by replacing existing tests
module.exports = mergedConfigs
