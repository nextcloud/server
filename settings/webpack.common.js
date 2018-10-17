const path = require('path')
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
  entry: {
	  'settings-vue': './src/main.js',
	  'settings-admin-security': './src/main-admin-security'
  },
  output: {
    path: path.resolve(__dirname, './js'),
    publicPath: '/',
    filename: '[name].js'
  },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          'vue-style-loader', 'css-loader'
        ],
      },
      {
        test: /\.scss$/,
        use: [
					'vue-style-loader', 'css-loader', 'sass-loader'
        ],
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      },
    ]
  },
  plugins: [
    new VueLoaderPlugin()
  ],
  resolve: {
    extensions: ['*', '.js', '.vue', '.json']
  }
}
