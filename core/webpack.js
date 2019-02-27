const path = require('path');
const webpack = require('webpack');

module.exports = [
	{
		entry: {
			main: path.join(__dirname, 'src/main.js'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist')
		},
		module: {
			rules: [
				{
					test: /\.css$/,
					use: ['style-loader', 'css-loader']
				},
				{
					test: /\.scss$/,
					use: ['style-loader', 'css-loader', 'sass-loader']
				},
				{
					test: /davclient/,
					use: 'exports-loader?dav'
				},
				{
					test: /\.js$/,
					loader: 'babel-loader',
					exclude: /node_modules/
				},
				{
					test: /\.(png|jpg|gif)$/,
					loader: 'url-loader',
					options: {
						name: '[name].[ext]?[hash]',
						limit: 8192
					}
				},
				{
					test: /\.handlebars/,
					loader: "handlebars-loader",
					query: {
						extensions: '.handlebars'
					}
				}
			]
		},
		plugins: [
			new webpack.ProvidePlugin({
				'_': "underscore",
				$: "jquery",
				jQuery: "jquery"
			})
		],
		resolve: {
			alias: {
				handlebars: 'handlebars/runtime'
			},
			extensions: ['*', '.js'],
			symlinks: false
		}
	},
	{
		entry: {
			share_backend: path.resolve(__dirname, 'js/merged-share-backend.js'),
			systemtags: path.resolve(__dirname, 'js/systemtags/merged-systemtags.js')
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist')
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					loader: 'babel-loader',
					exclude: /node_modules/
				},
				{
					test: /\.css$/,
					use: ['style-loader', 'css-loader']
				},
				{
					test: /\.scss$/,
					use: ['style-loader', 'css-loader', 'sass-loader']
				},
			]
		}
	}
];
