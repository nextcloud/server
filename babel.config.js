module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		'@babel/plugin-proposal-class-properties',
		'@babel/plugin-transform-private-methods',
		// We need the bundler entry not the web one
		// Jest will otherwise resolve the wrong one
		[
			"module-resolver",
			{
				"alias": {
					"webdav$": "webdav/dist/node/index.js",
				},
			},
		]
	],
	presets: [
		// https://babeljs.io/docs/en/babel-preset-typescript
		'@babel/preset-typescript',
		[
			'@babel/preset-env',
			{
				useBuiltIns: false,
				modules: 'auto',
			},
		],
	],
}
