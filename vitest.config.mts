/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */
import { defaultExclude, defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue2'
import { exec } from 'node:child_process'
import { promisify } from 'node:util'

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
	if (error.code !== 1) {
		// but otherwise something bad is happening and we should re-throw
		throw error
	}
}

export default defineConfig({
	plugins: [vue()],
	test: {
		include: ['{apps,core}/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		env: {
			LANG: 'en-US',
			TZ: 'UTC',
		},
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
		setupFiles: [
			'__tests__/mock-window.js',
			'__tests__/setup-testing-library.js',
		],
		exclude: [
			...defaultExclude,
			...gitIgnore,
		],
		globalSetup: '__tests__/setup-global.js',
		server: {
			deps: {
				inline: [/@nextcloud\//],
			},
		},
	},
})
