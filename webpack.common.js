/* eslint-disable camelcase */
const { VueLoaderPlugin } = require('vue-loader')
const path = require('path')
const CircularDependencyPlugin = require('circular-dependency-plugin')
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
		publicPath: '/dist/',
		filename: '[name].js?v=[contenthash]',
		chunkFilename: '[name]-[id].js?v=[contenthash]',
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
				loader: 'url-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
			{
				test: /\.handlebars/,
				loader: 'handlebars-loader',
			},

		],
	},

	optimization: {
		splitChunks: false,
		// {
		// 	automaticNameDelimiter: '-',
		// 	cacheGroups: {
		// 		vendors: {
		// 			test: /[\\/]node_modules[\\/]/,
		// 			enforce: true,
		// 			name: 'nextcloud',
		// 			chunks: 'all',
		// 		},
		// 	},
		// },
	},

	plugins: [
		new VueLoaderPlugin(),
		new ESLintPlugin(),
		new CircularDependencyPlugin({
		}),
		new webpack.ProvidePlugin({
			_: 'underscore',
			$: 'jquery',
			jQuery: 'jquery',
		}),
	],
	resolve: {
		alias: {
			OC: path.resolve(__dirname, './core/src/OC'),
			OCA: path.resolve(__dirname, './core/src/OCA'),
			// make sure to use the handlebar runtime when importing
			handlebars: 'handlebars/runtime',
		},
		extensions: ['*', '.js', '.vue'],
		symlinks: false,
		fallback: {
			stream: require.resolve('stream-browserify'),
		},
	},
}
