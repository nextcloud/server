/* eslint-disable n/no-extraneous-require */
/* eslint-disable camelcase */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const { VueLoaderPlugin } = require('vue-loader')
const { readFileSync } = require('fs')
const path = require('path')

const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')
const webpack = require('webpack')
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')
const WebpackSPDXPlugin = require('./build/WebpackSPDXPlugin.js')

const modules = require('./webpack.modules.js')
const { codecovWebpackPlugin } = require('@codecov/webpack-plugin')

const appVersion = readFileSync('./version.php').toString().match(/OC_Version.+\[([0-9]{2})/)?.[1] ?? 'unknown'
const isDev = process.env.NODE_ENV === 'development'
const isTesting = process.env.TESTING === "true"

const formatOutputFromModules = (modules) => {
	// merge all configs into one object, and use AppID to generate the fileNames
	// with the following format:
	// AppId-fileName: path/to/js-file.js
	const moduleEntries = Object.keys(modules).map(moduleKey => {
		const module = modules[moduleKey]

		const entries = Object.keys(module).map(entryKey => {
			const entry = module[entryKey]
			return { [`${moduleKey}-${entryKey}`]: entry }
		})

		return Object.assign({}, ...Object.values(entries))
	})
	return Object.assign({}, ...Object.values(moduleEntries))
}

const modulesToBuild = () => {
	const MODULE = process?.env?.MODULE
	if (MODULE) {
		if (!modules[MODULE]) {
			throw new Error(`No module "${MODULE}" found`)
		}
		return formatOutputFromModules({
			[MODULE]: modules[MODULE],
		})
	}

	return formatOutputFromModules(modules)
}

const config = {
	entry: modulesToBuild(),
	output: {
		// Step away from the src folder and extract to the js folder
		path: path.join(__dirname, 'dist'),
		// Let webpack determine automatically where it's located
		publicPath: 'auto',
		filename: '[name].js?v=[contenthash]',
		chunkFilename: '[name]-[id].js?v=[contenthash]',
		// Make sure sourcemaps have a proper path and do not
		// leak local paths https://github.com/webpack/webpack/issues/3603
		devtoolNamespace: 'nextcloud',
		devtoolModuleFilenameTemplate(info) {
			const rootDir = process?.cwd()
			const rel = path.relative(rootDir, info.absoluteResourcePath)
			return `webpack:///nextcloud/${rel}`
		},
		clean: {
			keep: /icons\.css/, // Keep static icons css
		},
	},

	module: {
		rules: [
			{
				test: /davclient/,
				loader: 'exports-loader',
				options: {
					type: 'commonjs',
					exports: 'dav',
				},
			},
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader'],
			},
			{
				test: /\.scss$/,
				use: ['style-loader', 'css-loader', 'sass-loader'],
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				exclude: BabelLoaderExcludeNodeModulesExcept([
					'vue-material-design-icons',
					'emoji-mart-vue-fast',
				]),
			},
			{
				test: /\.tsx?$/,
				use: [
					'babel-loader',
					{
						// Fix TypeScript syntax errors in Vue
						loader: 'ts-loader',
						options: {
							transpileOnly: true,
						},
					},
				],
				exclude: BabelLoaderExcludeNodeModulesExcept([]),
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				// automatically detect necessary packages to
				// transpile in the node_modules folder
				exclude: BabelLoaderExcludeNodeModulesExcept([
					'@nextcloud/dialogs',
					'@nextcloud/event-bus',
					'davclient.js',
					'nextcloud-vue-collections',
					'p-finally',
					'p-limit',
					'p-locate',
					'p-queue',
					'p-timeout',
					'p-try',
					'semver',
					'striptags',
					'toastify-js',
					'v-tooltip',
					'yocto-queue',
				]),
			},
			{
				test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf)$/,
				type: 'asset/inline',
			},
			{
				test: /\.handlebars/,
				loader: 'handlebars-loader',
			},
			{
				resourceQuery: /raw/,
				type: 'asset/source',
			},
		],
	},

	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
			minChunks: 3, // minimum number of chunks that must share the module
			cacheGroups: {
				vendors: {
					// split every dependency into one bundle
					test: /[\\/]node_modules[\\/]/,
					// necessary to keep this name to properly inject it
					// see OC_Template.php
					name: 'core-common',
					chunks: 'all',
				},
			},
		},
	},

	plugins: [
		new VueLoaderPlugin(),
		new NodePolyfillPlugin({
			additionalAliases: ['process'],
		}),
		new webpack.ProvidePlugin({
			// Provide jQuery to jquery plugins as some are loaded before $ is exposed globally.
			// We need to provide the path to node_moduels as otherwise npm link will fail due
			// to tribute.js checking for jQuery in @nextcloud/vue
			jQuery: path.resolve(path.join(__dirname, 'node_modules/jquery')),
		}),

		new WorkboxPlugin.GenerateSW({
			swDest: 'preview-service-worker.js',
			clientsClaim: true,
			skipWaiting: true,
			exclude: [/.*/], // don't do pre-caching
			inlineWorkboxRuntime: true,
			sourcemap: false,

			// Increase perfs with less logging
			disableDevLogs: true,

			// Define runtime caching rules.
			runtimeCaching: [{
				// Match any preview file request
				// /apps/files_trashbin/preview?fileId=156380&a=1
				// /core/preview?fileId=155842&a=1
				urlPattern: /^.*\/(apps|core)(\/[a-z-_]+)?\/preview.*/i,

				// Apply a strategy.
				handler: 'CacheFirst',

				options: {
					// Use a custom cache name.
					cacheName: 'previews',

					// Only cache 10000 images.
					expiration: {
						maxAgeSeconds: 3600 * 24 * 7, // one week
						maxEntries: 10000,
					},
				},
			}],
		}),

		// Make appName & appVersion available as a constants for '@nextcloud/vue' components
		new webpack.DefinePlugin({ appName: JSON.stringify('Nextcloud') }),
		new webpack.DefinePlugin({ appVersion: JSON.stringify(appVersion) }),

		// @nextcloud/moment since v1.3.0 uses `moment/min/moment-with-locales.js`
		// Which works only in Node.js and is not compatible with Webpack bundling
		// It has an unused function `localLocale` that requires locales by invalid relative path `./locale`
		// Though it is not used, Webpack tries to resolve it with `require.context` and fails
		new webpack.IgnorePlugin({
			resourceRegExp: /^\.\/locale$/,
			contextRegExp: /moment\/min$/,
		}),
		codecovWebpackPlugin({
			enableBundleAnalysis: !isDev && !isTesting,
			bundleName: 'nextcloud',
			telemetry: false,
		}),
	],
	externals: {
		OC: 'OC',
		OCA: 'OCA',
		OCP: 'OCP',
	},
	resolve: {
		alias: {
			// make sure to use the handlebar runtime when importing
			handlebars: 'handlebars/runtime',
			vue$: path.resolve('./node_modules/vue'),
		},
		extensions: ['*', '.ts', '.js', '.vue'],
		extensionAlias: {
			/**
			 * Resolve TypeScript files when using fully-specified esm import paths
			 * https://github.com/webpack/webpack/issues/13252
			 */
			'.js': ['.js', '.ts'],
		},
		symlinks: true,
		fallback: {
			fs: false,
		},
	},
}

// Generate reuse license files if not in development mode
if (!isDev) {
	config.plugins.push(new WebpackSPDXPlugin({
		override: {
			select2: 'MIT',
			'@nextcloud/axios': 'GPL-3.0-or-later',
			'@nextcloud/vue': 'AGPL-3.0-or-later',
			'nextcloud-vue-collections': 'AGPL-3.0-or-later',
		},
	}))

	config.optimization.minimizer = [{
		apply: (compiler) => {
			// Lazy load the Terser plugin
			const TerserPlugin = require('terser-webpack-plugin')
			new TerserPlugin({
				extractComments: false,
				terserOptions: {
					format: {
						comments: false,
					},
					compress: {
						passes: 2,
					},
				},
		  }).apply(compiler)
		},
	}]
}

module.exports = config
