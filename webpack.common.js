/* eslint-disable camelcase */
const { VueLoaderPlugin } = require('vue-loader')
const path = require('path')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')
const ESLintPlugin = require('eslint-webpack-plugin')
const webpack = require('webpack')
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
	const MODULE = process.env.MODULE
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
			const rootDir = process.cwd()
			const rel = path.relative(rootDir, info.absoluteResourcePath)
			return `webpack:///nextcloud/${rel}`
		},
		clean: true,
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
				test: /\.js$/,
				loader: 'babel-loader',
				// automatically detect necessary packages to
				// transpile in the node_modules folder
				exclude: BabelLoaderExcludeNodeModulesExcept([
					'@nextcloud/dialogs',
					'@nextcloud/event-bus',
					'@nextcloud/vue-dashboard',
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

		],
	},

	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
			cacheGroups: {
				vendors: {
					// split every dependency into one bundle
					test: /[\\/]node_modules[\\/]/,
					enforce: true,
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
		new ESLintPlugin(),
		new webpack.ProvidePlugin({
			// Provide jQuery to jquery plugins as some are loaded before $ is exposed globally.
			jQuery: 'jquery',
		}),
	],
	resolve: {
		alias: {
			// make sure to use the handlebar runtime when importing
			handlebars: 'handlebars/runtime',
		},
		extensions: ['*', '.js', '.vue'],
		symlinks: false,
		fallback: {
			stream: require.resolve('stream-browserify'),
			buffer: require.resolve('buffer'),
		},
	},
}
