const path = require('path')
const webpack = require('webpack')

module.exports = [
	{
		entry: {
			files_client: path.join(__dirname, 'src/files/client.js'),
			files_fileinfo: path.join(__dirname, 'src/files/fileinfo.js'),
			files_iedavclient: path.join(__dirname, 'src/files/iedavclient.js'),
			install: path.join(__dirname, 'src/install.js'),
			login: path.join(__dirname, 'src/login.js'),
			main: path.join(__dirname, 'src/main.js'),
			maintenance: path.join(__dirname, 'src/maintenance.js'),
			recommendedapps: path.join(__dirname, 'src/recommendedapps.js'),
			'unified-search': path.join(__dirname, 'src/unified-search.js'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist'),
			jsonpFunction: 'webpackJsonpCore',
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
			],
		},
		plugins: [
			new webpack.ProvidePlugin({
				_: 'underscore',
				$: 'jquery',
				jQuery: 'jquery',
			}),
		],
	},
	{
		entry: {
			systemtags: path.resolve(__dirname, 'src/systemtags/merged-systemtags.js'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'js/dist'),
		},
	},
]
