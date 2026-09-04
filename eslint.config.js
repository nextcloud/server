/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { includeIgnoreFile } from '@eslint/compat'
import { recommended } from '@nextcloud/eslint-config'
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

	// Playwright tests setup
	{
		name: 'server/playwright',
		files: ['tests/playwright/**'],
		rules: {
			'no-empty-pattern': 'off', // PW needs the destructuring syntax {} for fixtures!
		},
	},

	// Forbid commiting .only in test files (skipping tests is very unexpected)
	{
		name: 'server/no-only-in-tests',
		files: ['tests/playwright/**', 'apps/**/*.spec.*', 'core/**/*.spec.*'],
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
			'tests/!(playwright)/', // PHP tests, but not Playwright tests
			'**/js/',
			'**/l10n/', // all translations (config only ignored in root)
			'**/vendor/', // different vendors
			// files_sharing is symlinked into the Vue 3 frontend only for its bridge
			// entry point; the app itself is still Vue 2 and is linted through the
			// legacy frontend, whose config matches the code it actually is.
			'build/frontend/apps/files_sharing/',
		],
	},
])
