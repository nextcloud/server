/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		'@babel/plugin-proposal-class-properties',
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
