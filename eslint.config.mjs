/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { includeIgnoreFile } from "@eslint/compat";
import { recommendedVue2 } from '@nextcloud/eslint-config'
import { defineConfig } from 'eslint/config'
import { fileURLToPath } from "node:url";
import * as globals from 'globals'

const gitignorePath = fileURLToPath(new URL(".gitignore", import.meta.url));

export default defineConfig([
	...recommendedVue2,
	includeIgnoreFile(gitignorePath, "Imported .gitignore patterns"),
	{
		ignores: [
			'3rdparty/', // PHP tests
			'tests/', // PHP tests
			'**/l10n/', // all translations (config only ignored in root)
		],
	},
	{
		files: ['cypress/**'],
		rules: {
			'no-console': 'off',
		},
	},
	// scripts are cjs
	{
		files: ['*.js', 'build/**/*.js'],
		languageOptions: {
			globals: {
				...globals.es2023,
				...globals.node,
				...globals.nodeBuiltin,
			},
		}
	},
])
