/* eslint-disable no-console */
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { configureNextcloud, docker, getContainer, getContainerName, runExec, runOcc, startNextcloud, stopNextcloud, waitOnNextcloud } from '@nextcloud/e2e-test-server'
import { defineConfig } from 'cypress'
import cypressSplit from 'cypress-split'
import vitePreprocessor from 'cypress-vite'
import { existsSync, rmdirSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { nodePolyfills } from 'vite-plugin-node-polyfills'

if (!globalThis.__dirname) {
	// Cypress has their own weird parser
	globalThis.__dirname = dirname(fileURLToPath(new URL(import.meta.url)))
}

export default defineConfig({
	projectId: '37xpdh',

	// 16/9 screen ratio
	viewportWidth: 1280,
	viewportHeight: 720,

	// Tries again 2 more times on failure
	retries: {
		runMode: 2,
		// do not retry in `cypress open`
		openMode: 0,
	},

	// Needed to trigger `after:run` events with cypress open
	experimentalInteractiveRunEvents: true,

	// disabled if running in CI but enabled in debug mode
	video: !process.env.CI || !!process.env.RUNNER_DEBUG,

	// faster video processing
	videoCompression: false,

	// Prevent elements to be scrolled under a top bar during actions (click, clear, type, etc). Default is 'top'.
	// https://github.com/cypress-io/cypress/issues/871
	scrollBehavior: 'center',

	// Visual regression testing
	env: {
		failSilently: false,
		type: 'actual',
	},

	screenshotsFolder: 'cypress/snapshots/actual',
	trashAssetsBeforeRuns: true,

	e2e: {
		// Disable session isolation
		testIsolation: false,

		requestTimeout: 30000,

		// We've imported your old cypress plugins here.
		// You may want to clean this up later by importing these.
		async setupNodeEvents(on, config) {
			on('file:preprocessor', vitePreprocessor({
				plugins: [nodePolyfills()],
			}))

			// This allows to store global data (e.g. the name of a snapshot)
			// because Cypress.env() and other options are local to the current spec file.
			const data: Record<string, unknown> = {}
			on('task', {
				setVariable({ key, value }) {
					data[key] = value
					return null
				},
				getVariable({ key }) {
					return data[key] ?? null
				},
				// allow to clear the downloads folder
				deleteFolder(path: string) {
					try {
						if (existsSync(path)) {
							rmdirSync(path, { maxRetries: 10, recursive: true })
						}
						return null
					} catch (error) {
						throw Error(`Error while deleting ${path}. Original error: ${error}`)
					}
				},
			})

			// Disable spell checking to prevent rendering differences
			on('before:browser:launch', (browser, launchOptions) => {
				if (browser.family === 'chromium' && browser.name !== 'electron') {
					launchOptions.preferences.default['browser.enable_spellchecking'] = false
					return launchOptions
				}

				if (browser.family === 'firefox') {
					launchOptions.preferences['layout.spellcheckDefault'] = 0
					return launchOptions
				}

				if (browser.name === 'electron') {
					launchOptions.preferences.spellcheck = false
					return launchOptions
				}
			})

			// Remove container after run
			on('after:run', () => {
				if (!process.env.CI) {
					stopNextcloud()
				}
			})

			// Check if we are running the setup checks
			if (process.env.SETUP_TESTING === 'true') {
				console.log('Adding setup tests to specPattern 🧮')
				config.specPattern = [join(__dirname, 'cypress/e2e/core/setup.ts')]
				console.log('└─ Done')
			} else {
				// If we are not running the setup tests, we need to remove the setup tests from the specPattern
				cypressSplit(on, config)
			}

			const mounts = {
				'3rdparty': resolve(__dirname, './3rdparty'),
				apps: resolve(__dirname, './apps'),
				core: resolve(__dirname, './core'),
				cypress: resolve(__dirname, './cypress'),
				dist: resolve(__dirname, './dist'),
				lib: resolve(__dirname, './lib'),
				ocs: resolve(__dirname, './ocs'),
				'ocs-provider': resolve(__dirname, './ocs-provider'),
				resources: resolve(__dirname, './resources'),
				tests: resolve(__dirname, './tests'),
				'console.php': resolve(__dirname, './console.php'),
				'cron.php': resolve(__dirname, './cron.php'),
				'index.php': resolve(__dirname, './index.php'),
				occ: resolve(__dirname, './occ'),
				'public.php': resolve(__dirname, './public.php'),
				'remote.php': resolve(__dirname, './remote.php'),
				'status.php': resolve(__dirname, './status.php'),
				'version.php': resolve(__dirname, './version.php'),
			} as Record<string, string>

			for (const [key, path] of Object.entries(mounts)) {
				if (!existsSync(path)) {
					delete mounts[key]
				}
			}

			// Before the browser launches
			// starting Nextcloud testing container
			const port = 8042
			const ip = await startNextcloud(process.env.BRANCH, false, {
				mounts,
				exposePort: port,
				forceRecreate: true,
			})
			// Setting container's IP as base Url
			config.baseUrl = `http://localhost:${port}/index.php`
			// if needed for the setup tests, connect to the actions network
			await connectToActionsNetwork()
			// make sure not to write into apps but use a local apps folder
			runExec(['mkdir', 'apps-cypress'])
			runExec(['cp', 'cypress/fixtures/app.config.php', 'config'])
			// now wait until Nextcloud is ready and configure it
			await waitOnNextcloud(ip)
			await configureNextcloud()
			// additionally we do not want to DoS the app store
			runOcc(['config:system:set', 'appstoreenabled', '--value', 'false', '--type', 'boolean'])

			// for later use in tests save the container name
			// @ts-expect-error we are adding a custom property
			config.dockerContainerName = getContainerName()

			// IMPORTANT: return the config otherwise cypress-split will not work
			return config
		},
	},
})

/**
 * Connect the running test container to the GitHub Actions network
 */
async function connectToActionsNetwork() {
	if (process.env.SETUP_TESTING !== 'true') {
		console.log('├─ Not running setup tests, skipping actions network connection 🌐')
		return
	}

	console.log('├─ Looking for github actions network... 🔍')
	const networks = await docker.listNetworks()
	const network = networks.find((network) => network.Name.startsWith('github_network'))
	if (!network) {
		console.log('│  └─ No actions network found ⚠️')
		return
	}

	console.log('│  |─ Found actions network: ' + network.Name)
	await docker.getNetwork(network.Id)
		.connect({ Container: getContainer().id })
	console.log('│  └─ Connected to actions network 🌐')
}
