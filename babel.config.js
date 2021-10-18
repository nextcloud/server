module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		'@babel/plugin-proposal-class-properties',
	],
	presets: [
		[
			'@babel/preset-env',
			{
				useBuiltIns: false,
			},
		],
	],
}
