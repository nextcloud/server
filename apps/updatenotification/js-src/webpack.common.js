const path = require('path')
const { VueLoaderPlugin } = require('vue-loader');

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
      }
    ]
  },
  plugins: [
    new VueLoaderPlugin()
  ],
  resolve: {
    alias: {
      'vue$': 'vue/dist/vue.esm.js'
    },
    extensions: ['*', '.js', '.vue', '.json']
  }
}
