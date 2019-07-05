const path = require('path')

module.exports = {
	entry: {
		'settings-apps-users-management': path.join(__dirname, 'src', 'main-apps-users-management'),
		'settings-admin-security': path.join(__dirname, 'src', 'main-admin-security'),
		'settings-personal-security': path.join(__dirname, 'src', 'main-personal-security')
	},
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/',
		filename: 'vue-[name].js?v=[chunkhash]'
	},
	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
		}
	}
}
