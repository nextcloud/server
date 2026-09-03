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
			// A worker that still has console output in flight when it shuts down
			// fails the run even though every test passed. The specs that log
			// heavily (the focus trap above prints per interaction) hit this
			// depending on how the files are spread over the workers.
			if (error.name === 'EnvironmentTeardownError' && error.message.includes('Closing rpc while')) {
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
