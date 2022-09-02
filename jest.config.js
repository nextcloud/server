/*
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

// TODO: find a way to consolidate this in one place, with webpack.common.js
const ignorePatterns = [
	'vue-material-design-icons',
	'@juliushaertl',
	'tributejs',
	'@nextcloud/vue',
	'splitpanes',
	'string-length',
	'strip-ansi',
	'ansi-regex',
	'char-regex',
]

module.exports = {
	preset: '@vue/cli-plugin-unit-jest/presets/no-babel',
	testMatch: ['<rootDir>/apps/*/src/**/*.(spec|test).(ts|js)'],
	modulePathIgnorePatterns: ["<rootDir>/apps-extra/"],
	transformIgnorePatterns: [
		'node_modules/(?!(' + ignorePatterns.join('|') + ')/)',
	],
    setupFilesAfterEnv: ['<rootDir>/tests/jestSetup.js'],
	resetMocks: false,
	collectCoverageFrom: [
		'<rootDir>/apps/*/src/**/*.{js,vue}',
	],
	transform: {
		// process `*.js` files with `babel-jest`
		'.*\\.(js)$': 'babel-jest',
	},
}
