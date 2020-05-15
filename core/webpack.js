const path = require('path')
const webpack = require('webpack')

module.exports = [
	{
		entry: {
			login: path.join(__dirname, 'src/login.js'),
			main: path.join(__dirname, 'src/main.js'),
			maintenance: path.join(__dirname, 'src/maintenance.js'),
			recommendedapps: path.join(__dirname, 'src/recommendedapps.js'),
			install: path.join(__dirname, 'src/install.js'),
			files_client: path.join(__dirname, 'src/files/client.js'),
			files_fileinfo: path.join(__dirname, 'src/files/fileinfo.js'),
			files_iedavclient: path.join(__dirname, 'src/files/iedavclient.js')
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist'),
			jsonpFunction: 'webpackJsonpCore'
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
			systemtags: path.resolve(__dirname, 'js/systemtags/merged-systemtags.js')
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist')
		}
	}
]
