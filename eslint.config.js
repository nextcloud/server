/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { includeIgnoreFile } from '@eslint/compat'
import { recommended } from '@nextcloud/eslint-config'
import CypressEslint from 'eslint-plugin-cypress'
import noOnlyTests from 'eslint-plugin-no-only-tests'
import { defineConfig } from 'eslint/config'
import * as globals from 'globals'
import { fileURLToPath } from 'node:url'

const gitignorePath = fileURLToPath(new URL('.gitignore', import.meta.url))

export default defineConfig([
	{
		linterOptions: {
			reportUnusedDisableDirectives: 'error',
			reportUnusedInlineConfigs: 'error',
		},
	},

	...recommended,

	// add globals configuration for Webpack injected variables
	{
		name: 'server/custom-webpack-globals',
		files: ['**/*.js', '**/*.ts', '**/*.vue'],
		languageOptions: {
			globals: {
				PRODUCTION: 'readonly',
			},
		},
	},

	// Ensure that cjs files are treated as Node scripts
	{
		name: 'server/scripts-are-cjs',
		files: [
			'*.js',
			'build/*.js',
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

	// Cypress setup
	{
		...CypressEslint.configs.recommended,
		files: ['cypress/**', '**/*.cy.*'],
	},
	{
		name: 'server/cypress',
		files: ['cypress/**', '**/*.cy.*'],
		rules: {
			'no-console': 'off',
			'jsdoc/require-jsdoc': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/require-param-description': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/no-unused-expressions': 'off',
		},
	},

	// Forbid commiting .only in test files (skipping tests is very unexpected)
	{
		name: 'server/no-only-in-tests',
		files: ['cypress/**', 'apps/**/*.spec.*', 'core/**/*.spec.*'],
		plugins: {
			'no-only-tests': noOnlyTests,
		},
		rules: {
			'no-only-tests/no-only-tests': 'error',
		},
	},

	// respect .gitignore
	includeIgnoreFile(gitignorePath, 'Imported .gitignore patterns'),

	// custom server ignore files
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
