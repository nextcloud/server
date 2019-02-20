const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
	entry: {
		'additionalScripts': path.join(__dirname, 'src', 'additionalScripts.js'),
		'files_sharing': path.join(__dirname, 'src', 'files_sharing.js'),
},
	output: {
		path: path.resolve(__dirname, './js/dist/'),
		publicPath: '/js/',
		filename: '[name].js',
		chunkFilename: 'files_sharing.[id].js'

	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader']
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader']
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
				options: {
					plugins: ['@babel/plugin-syntax-dynamic-import']
				}
			},
			{
				test: /\.(png|jpg|gif|svg)$/,
				loader: 'file-loader',
				options: {
					name: '[name].[ext]?[hash]'
				}
			}
		]
	},
	plugins: [new VueLoaderPlugin()],
	resolve: {
		alias: {
			vue$: 'vue/dist/vue.runtime.esm.js',
		},
		extensions: ['*', '.js', '.vue', '.json']
	}
};
