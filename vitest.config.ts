/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import { defineConfig } from 'vitest/config'

export default defineConfig({
	test: {
		projects: [
			'build/frontend*',
		],
	},
	server: {
		watch: {
			ignored(path: string) {
				return !/(\/|build\/frontend[^/]*\/)(apps|core)\/(src|tests)\//.test(path)
			},
		},
	},
})
