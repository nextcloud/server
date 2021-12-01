/* eslint-disable camelcase */
const { VueLoaderPlugin } = require('vue-loader')
const path = require('path')

const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')
const ESLintPlugin = require('eslint-webpack-plugin')

const modules = require('./webpack.modules.js')

const modulesToBuild = () => {
	const MODULE = process.env.MODULE
	if (MODULE) {
		if (!modules[MODULE]) {
			throw new Error(`No module "${MODULE}" found`)
		}
		return modules[MODULE]
	}
	// merge all configs into one object
	return Object.assign({}, ...Object.values(modules))
}

module.exports = {
	entry: modulesToBuild(),
	output: {
		// Step away from the src folder and extract to the js folder
		path: path.join(__dirname),
		publicPath: '/dist/',
		filename: (chunkData) => {
			// Get relative path of the src folder
			let srcPath = chunkData.chunk.entryModule.context
			if (srcPath === null) {
				srcPath = chunkData.chunk.entryModule.rootModule.context
			}
			const relativePath = path.relative(__dirname, srcPath)

			// If this is a core source, output in core dist folder
			if (relativePath.indexOf('core/src') > -1) {
				return path.join('core/js/dist/', '[name].js?v=[contenthash]')
			}
			// Get out of the shared dist folder and output inside apps js folder
			return path.join(relativePath, '..', 'js') + '/[name].js?v=[contenthash]'
		},
		chunkFilename: 'dist/[name]-[id].js?v=[contenthash]',
	},

	module: {
		rules: [
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
				query: {
					extensions: '.handlebars',
				},
			},

		],
	},

	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
			cacheGroups: {
				vendors: {
					test: /[\\/]node_modules[\\/]/,
					enforce: true,
					name: 'nextcloud',
					chunks: 'all',
				},
			},
		},
	},

	plugins: [new VueLoaderPlugin(), new ESLintPlugin()],
	resolve: {
		alias: {
			OC: path.resolve(__dirname, './core/src/OC'),
			OCA: path.resolve(__dirname, './core/src/OCA'),
			// make sure to use the handlebar runtime when importing
			handlebars: 'handlebars/runtime',
		},
		extensions: ['*', '.js', '.vue'],
		symlinks: false,
	},
}
