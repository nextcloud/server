/* eslint-disable node/no-extraneous-import */
/* eslint-disable node/no-unpublished-import */
import fs from 'fs'
import path from 'path'
import webpack from 'webpack'

import webpackConfig from '@nextcloud/webpack-vue-config'
import webpackRules from '@nextcloud/webpack-vue-config/rules'

import BabelLoaderExcludeNodeModulesExcept from 'babel-loader-exclude-node-modules-except'

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

webpackRules.RULE_TS = {
	test: /\.ts$/,
	use: [{
		loader: 'ts-loader',
		options: {
			// skip typechecking for speed
			transpileOnly: true,
		},
	}],
}

// Replaces rules array
webpackConfig.module.rules = Object.values(webpackRules)

// Add custom plugins
webpackConfig.plugins.push(...[
	new webpack.DefinePlugin({
		isTesting,
		PLYR_ICONS: JSON.stringify(plyrIcons),
	}),
])

// Add proper typescript support
webpackConfig.resolve.extensions = ['.ts', '.tsx', '.js', '.jsx', '.cjs', '.vue']

export default webpackConfig
