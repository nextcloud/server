const path = require('path');

module.exports = {
	entry: {
		'additionalScripts': path.join(__dirname, 'src', 'additionalScripts.js'),
		'files_sharing': path.join(__dirname, 'src', 'files_sharing.js'),
		'collaboration': path.join(__dirname, 'src', 'collaborationresourceshandler.js'),
	},
	output: {
		path: path.resolve(__dirname, './js/dist/'),
		publicPath: '/js/',
		filename: '[name].js',
		chunkFilename: 'files_sharing.[id].js'
	}
}
