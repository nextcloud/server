const path = require('path');

module.exports = {
	entry: path.join(__dirname, 'src', 'workflowengine.js'),
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: 'workflowengine.js',
		jsonpFunction: 'webpackJsonpWorkflowengine'
	},
	module: {
		rules: [
			{
				test: /\.handlebars/,
				loader: "handlebars-loader",
				query: {
					extensions: '.handlebars',
					helperDirs: path.join(__dirname, 'src/hbs_helpers'),
				}
			}
		]
	}
}
