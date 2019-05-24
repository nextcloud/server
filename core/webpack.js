const path = require('path')
const webpack = require('webpack')

module.exports = [
	{
		entry: {
			login: path.join(__dirname, 'src/login.js'),
			main: path.join(__dirname, 'src/main.js'),
			maintenance: path.join(__dirname, 'src/maintenance.js'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist')
		},
		module: {
			rules: [
				{
					test: /davclient/,
					use: 'exports-loader?dav'
				}
			]
		},
		plugins: [
			new webpack.ProvidePlugin({
				'_': "underscore",
				$: "jquery",
				jQuery: "jquery"
			})
		]
	},
	{
		entry: {
			share_backend: path.resolve(__dirname, 'js/merged-share-backend.js'),
			systemtags: path.resolve(__dirname, 'js/systemtags/merged-systemtags.js')
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist')
		}
	}
]
