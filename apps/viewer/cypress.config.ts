
import { configureNextcloud, startNextcloud, stopNextcloud, waitOnNextcloud } from './cypress/dockerNode'
import { defineConfig } from 'cypress'

import browserify from '@cypress/browserify-preprocessor'
import getCompareSnapshotsPlugin from 'cypress-visual-regression/dist/plugin'

export default defineConfig({
	projectId: 'xysa6x',

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

	// faster video processing
	videoCompression: false,

	// Visual regression testing
	env: {
		failSilently: false,
		type: 'actual',
	},
	screenshotsFolder: 'cypress/snapshots/actual',
	trashAssetsBeforeRuns: true,

	e2e: {
		// Enable session management and disable isolation
		experimentalSessionAndOrigin: true,
		testIsolation: 'off',

		// We've imported your old cypress plugins here.
		// You may want to clean this up later by importing these.
		async setupNodeEvents(on, config) {
			// Fix browserslist extend https://github.com/cypress-io/cypress/issues/2983#issuecomment-570616682
			on('file:preprocessor', browserify())
			getCompareSnapshotsPlugin(on, config)

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
				stopNextcloud()
			})

			// Before the browser launches
			// starting Nextcloud testing container
			return startNextcloud(process.env.BRANCH)
				.then((ip) => {
					// Setting container's IP as base Url
					config.baseUrl = `http://${ip}/index.php`
					return ip
				})
				.then(waitOnNextcloud)
				.then(configureNextcloud)
				.then(() => {
					return config
				})
		},
	},
})
