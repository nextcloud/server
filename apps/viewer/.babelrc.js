module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		[
			'@babel/plugin-proposal-class-properties',
			{ loose: true }
		]
	],
	presets: [
		[
			'@babel/preset-env',
			{
				targets: {
					browsers: ['last 2 versions', 'ie >= 11']
				}
			}
		]
	]
}
