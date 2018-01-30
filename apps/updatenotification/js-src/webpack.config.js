var path = require('path');
var webpack = require('webpack');

module.exports = {
	entry: './js-src/init.js',
	output: {
		path: path.resolve(__dirname, '../js'),
		publicPath: '/',
		filename: 'merged.js'
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				options: {
					loaders: {
					},
					esModule: false
					// other vue-loader options go here
				}
			}
		]
	},
	resolve: {
		alias: {
			'vue': process.env.NODE_ENV === 'production' ? 'vue/dist/vue.min.js' : 'vue/dist/vue.js'
		}
	},
	performance: {
		hints: false
	},
	devtool: '#eval-source-map'
};

if (process.env.NODE_ENV === 'production') {
	module.exports.devtool = '#source-map';
	// http://vue-loader.vuejs.org/en/workflow/production.html
	module.exports.plugins = (module.exports.plugins || []).concat([
		new webpack.DefinePlugin({
			'process.env': {
				NODE_ENV: '"production"'
			}
		}),
		new webpack.optimize.UglifyJsPlugin({
			sourceMap: true,
			compress: {
				warnings: false
			}
		}),
		new webpack.LoaderOptionsPlugin({
			minimize: true
		})
	]);
}
