const path = require('path');

module.exports = {
	entry: path.join(__dirname, 'src', 'comments.js'),
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: 'comments.js'
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
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/
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
	externals: {
		jquery: 'jQuery'
	},
	resolve: {
		extensions: ['*', '.js']
	}
};
