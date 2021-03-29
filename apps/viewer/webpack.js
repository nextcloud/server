const fs = require('fs')
const path = require('path')
const webpack = require('webpack')

const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')

const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

const isTesting = !!process.env.TESTING
const plyrIcons = fs.readFileSync(path.join('node_modules', 'plyr', 'dist', 'plyr.svg'), { encoding: 'utf8' })

if (isTesting) {
	console.debug('TESTING MODE ENABLED')
}

// vue-plyr uses .mjs file
webpackRules.RULE_JS.test = /\.m?js$/
webpackRules.RULE_JS.exclude = BabelLoaderExcludeNodeModulesExcept([
	'@nextcloud/dialogs',
	'@nextcloud/event-bus',
	'camelcase',
	'fast-xml-parser',
	'hot-patcher',
	'semver',
	'vue-plyr',
	'webdav',
	'toastify-js',
])

// Replaces rules array
webpackConfig.module.rules = Object.values(webpackRules)

// Add custom plugins
webpackConfig.plugins.push(...[
	new webpack.DefinePlugin({
		isTesting,
		PLYR_ICONS: JSON.stringify(plyrIcons),
	}),
])

module.exports = webpackConfig
