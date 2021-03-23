module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		['@babel/plugin-proposal-class-properties', { loose: true }],
	],
	presets: [
		[
			'@babel/preset-env',
			{
				modules: false,
				useBuiltIns: false,
			},
		],
	],

	// For mocha testing
	env: {
		test: {
			presets: ['@babel/preset-env'],
			plugins: [
				'@babel/plugin-transform-modules-commonjs',
				['@babel/plugin-proposal-class-properties', { loose: true }],
			],
		},
	},
}
