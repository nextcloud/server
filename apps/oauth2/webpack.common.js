const path = require('path')
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
  entry: path.join(__dirname, 'src', 'main.js'),
  output: {
    path: path.resolve(__dirname, './js'),
    publicPath: '/js',
    filename: 'oauth2.js'
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.css$/,
        use: ['vue-style-loader', 'css-loader']
      },
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
