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
		onUnhandledError(error) {
			// TODO: remove when this is fixed: https://github.com/nextcloud-libraries/nextcloud-vue/issues/8090
			if (error.message.includes('`fallbackFocus` was specified but was not a node, or did not return a node')) {
				return false
			}
		},
	},
	server: {
		watch: {
			ignored(path: string) {
				return !/(\/|build\/frontend[^/]*\/)(apps|core)\/(src|tests)\//.test(path)
			},
		},
	},
})
