/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
	testDir: './tests/playwright/e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? [['blob'], ['dot'], ['github']] : 'html',
	// // Higher timeouts are necessary to avoid
	// // failures on CI runners with very high load
	// timeout: process.env.CI ? 150_000 : 30_000,
	// expect: {
	//  timeout: process.env.CI ? 25_000 : 5_000,
	// },
	use: {
		baseURL: 'http://localhost:8042/index.php/',
		trace: 'on-first-retry',
	},
	projects: [
		{
			name: 'admin-settings',
			fullyParallel: false,
			workers: 1, // only one admin setting test can run at a time due to shared state
			testMatch: '**/admin-settings*.spec.ts',
			use: {
				...devices['Desktop Chrome'],
			},
		},

		{
			name: 'chrome',
			testMatch: /\/(?!admin-settings)[^/]*\.spec\.ts$/,
			use: {
				...devices['Desktop Chrome'],
			},
		},
	],
	webServer: {
		command: 'node tests/playwright/start-nextcloud-server.js',
		env: {
			NEXTCLOUD_PORT: '8042',
		},
		stderr: 'pipe',
		stdout: 'pipe',
		gracefulShutdown: {
			signal: 'SIGTERM',
			timeout: 10000,
		},
		reuseExistingServer: !process.env.CI,
		timeout: 5 * 60 * 1000,
		wait: {
			stdout: /Nextcloud container ready to run Playwright tests/,
		},
	},
})
