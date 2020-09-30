const path = require('path');

module.exports = {
	entry: {
		renewPassword: path.join(__dirname, 'src', 'renewPassword'),
		user_ldap: path.join(__dirname, 'src', 'main.js'),
	},
	output: {
		path: path.resolve(__dirname, './js/dist/'),
		publicPath: '/js/',
		filename: '[name].js',
		chunkFilename: 'user_ldap.[id].js?v=[chunkhash]',
		jsonpFunction: 'webpackJsonpUserLDAP'
	}
}
