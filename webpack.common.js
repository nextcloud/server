/* eslint-disable camelcase */
const { VueLoaderPlugin } = require('vue-loader')
const path = require('path')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')
const webpack = require('webpack')
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')

const modules = require('./webpack.modules.js')

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

module.exports = {
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
		new NodePolyfillPlugin(),
		new webpack.ProvidePlugin({
			// Provide jQuery to jquery plugins as some are loaded before $ is exposed globally.
			// We need to provide the path to node_moduels as otherwise npm link will fail due
			// to tribute.js checking for jQuery in @nextcloud/vue
			jQuery: path.resolve(path.join(__dirname, 'node_modules/jquery')),

			// Shim ICAL to prevent using the global object (window.ICAL).
			// The library ical.js heavily depends on instanceof checks which will
			// break if two separate versions of the library are used (e.g. bundled one
			// and global one).
			ICAL: 'ical.js',
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
