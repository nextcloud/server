const path = require('path')

module.exports = {
	entry: {
		accessibility: path.join(__dirname, 'src', 'main.js'),
		accessibilityoca: path.join(__dirname, 'src', 'accessibilityoca.js'),
	},
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: '[name].js',
		jsonpFunction: 'webpackJsonpAccessibility',
	},
}
