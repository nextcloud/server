/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { recommendedVue2 } from '@nextcloud/eslint-config'
import { defineConfig } from 'eslint/config'
import * as globals from 'globals'

export default defineConfig([
	{
		linterOptions: {
			reportUnusedDisableDirectives: 'error',
			reportUnusedInlineConfigs: 'error',
		},
	},

	...recommendedVue2,

	{
		name: 'server/custom-webpack-globals',
		files: ['**/*.js', '**/*.ts', '**/*.vue'],
		languageOptions: {
			globals: {
				PRODUCTION: 'readonly',
			},
		},
	},

	{
		name: 'server/scripts-are-cjs',
		files: [
			'*.js',
			'build/**/*.js',
			'**/core/src/icons.cjs',
		],

		languageOptions: {
			globals: {
				...globals.es2023,
				...globals.node,
			},
		},

		rules: {
			'no-console': 'off',
			'jsdoc/require-jsdoc': 'off',
		},
	},

	// customer server ignore files
	{
		name: 'server/ignored-files',
		ignores: [
			'.devcontainer/',
			'composer.json',
			'**/*.php',
			'3rdparty/',
			'tests/', // PHP tests
			'**/js/',
			'**/l10n/', // all translations (config only ignored in root)
			'**/vendor/', // different vendors
		],
	},
])
