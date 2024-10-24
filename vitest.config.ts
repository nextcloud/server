/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue2'

export default defineConfig({
	plugins: [vue()],
	test: {
		include: ['{apps,core}/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		environment: 'jsdom',
		environmentOptions: {
			jsdom: {
				url: 'http://nextcloud.local',
			},
		},
		coverage: {
			include: ['apps/*/src/**', 'core/src/**'],
			exclude: ['**.spec.*', '**.test.*', '**.cy.*', 'core/src/tests/**'],
			provider: 'v8',
			reporter: ['lcov', 'text'],
		},
		setupFiles: ['__tests__/mock-window.js', '__tests__/setup-testing-library.js'],
		server: {
			deps: {
				inline: [/@nextcloud\//],
			},
		},
	},
})
