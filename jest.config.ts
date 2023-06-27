/**
 * @copyright Copyright (c) 2020 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { Config } from 'jest'

// TODO: find a way to consolidate this in one place, with webpack.common.js
const ignorePatterns = [
	'@juliushaertl',
	'@mdi/svg',
	'@nextcloud/vue',
	'ansi-regex',
	'char-regex',
	'splitpanes',
	'string-length',
	'strip-ansi',
	'tributejs',
	'vue-material-design-icons',
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
	},
	transformIgnorePatterns: [
		'node_modules/(?!(' + ignorePatterns.join('|') + ')/)',
	],

	// Allow mocking svg files
	moduleNameMapper: {
		'^.+\\.svg(\\?raw)?$': '<rootDir>/__mocks__/svg.js',
	},
	modulePathIgnorePatterns: [
		'<rootDir>/apps2/',
		'<rootDir>/apps-extra/',
	],
}

export default config
