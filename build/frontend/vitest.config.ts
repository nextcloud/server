/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import vue from '@vitejs/plugin-vue'
import { exec } from 'node:child_process'
import { resolve } from 'node:path'
import { promisify } from 'node:util'
import { defaultExclude, defineConfig } from 'vitest/config'

const gitIgnore: string[] = []
// get all files ignored in the apps directory (e.g. if putting `view` app there).
try {
	const execAsync = promisify(exec)
	const { stdout } = await execAsync('git check-ignore apps/*', { cwd: __dirname })
	gitIgnore.push(...stdout.split('\n').filter(Boolean))
	// eslint-disable-next-line no-console
	console.log('Git ignored files excluded from tests: ', gitIgnore)
} catch (error) {
	// we can ignore error code 1 as this just means there are no ignored files
	if (!error || typeof error !== 'object' || !('code' in error) || error.code !== 1) {
		// but otherwise something bad is happening and we should re-throw
		throw error
	}
}

export default defineConfig({
	plugins: [vue()],
	root: resolve(import.meta.dirname),
	// define some dummy globals for the tests
	define: {
		appName: '"nextcloud"',
		appVersion: '"1.0.0"',
	},
	resolve: {
		preserveSymlinks: true,
	},
	test: {
		include: ['apps/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		env: {
			LANG: 'en_US',
			TZ: 'UTC',
		},
		environment: 'jsdom',
		environmentOptions: {
			jsdom: {
				url: 'http://nextcloud.local',
			},
		},
		coverage: {
			include: [
				'apps/*/src/**',
				/* 'core/src/**', */
			],
			exclude: ['**.spec.*', '**.test.*', '**.cy.*', 'core/src/tests/**'],
			provider: 'istanbul',
			reporter: ['lcov', 'text'],
			reportsDirectory: resolve(import.meta.dirname, '../../coverage'),
		},
		setupFiles: [
			resolve(import.meta.dirname, '__tests__/mock-window.js'),
			resolve(import.meta.dirname, '__tests__/setup-testing-library.js'),
		],
		exclude: [
			...defaultExclude,
			...gitIgnore,
		],
		globalSetup: resolve(import.meta.dirname, '__tests__/setup-global.js'),
		server: {
			deps: {
				inline: [/@nextcloud\//],
			},
		},
	},
})
