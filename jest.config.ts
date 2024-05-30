/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Config } from 'jest'

// TODO: find a way to consolidate this in one place, with webpack.common.js
const ignorePatterns = [
	'@buttercup/fetch',
	'@juliushaertl',
	'@mdi/svg',
	'@nextcloud/upload',
	'@nextcloud/vue',
	'ansi-regex',
	'camelcase',
	'char-regex',
	'hot-patcher',
	'is-svg',
	'mime',
	'p-cancelable',
	'p-limit',
	'p-queue',
	'p-timeout',
	'splitpanes',
	'string-length',
	'strip-ansi',
	'tributejs',
	'unist-.+',
	'vue-material-design-icons',
	'webdav',
	'yocto-queue',
]

const config: Config = {
	testMatch: ['<rootDir>/**/*.(spec|test).(ts|js)'],

	clearMocks: true,
	setupFilesAfterEnv: ['<rootDir>/__tests__/jest-setup.ts'],

	testEnvironment: 'jest-environment-jsdom',
	preset: 'ts-jest/presets/js-with-ts',

	roots: [
		'<rootDir>/__mocks__',
		'<rootDir>/__tests__',
		'<rootDir>/apps',
		'<rootDir>/core',
	],

	transform: {
		// process `*.js` files with `babel-jest`
		'^.+\\.js$': 'babel-jest',
		'^.+\\.vue$': '@vue/vue2-jest',
		'^.+\\.ts$': ['ts-jest', {
			// @see https://github.com/kulshekhar/ts-jest/issues/4081
			tsconfig: './__tests__/tsconfig.json',
		}],
	},
	transformIgnorePatterns: [
		'node_modules/(?!(' + ignorePatterns.join('|') + ')/)',
	],

	// Allow mocking svg files
	moduleNameMapper: {
		'^.+\\.svg(\\?raw)?$': '<rootDir>/__mocks__/svg.js',
		'\\.s?css$': '<rootDir>/__mocks__/css.js',
	},
	modulePathIgnorePatterns: [
		'<rootDir>/apps2/',
		'<rootDir>/apps-extra/',
	],
}

export default config
