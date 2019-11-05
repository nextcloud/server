module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		['@babel/plugin-proposal-class-properties', { loose: true }],
		'inline-json-import'
	],
	presets: [
		[
			'@babel/preset-env',
			{
				modules: false
			}
		]
	]
};
