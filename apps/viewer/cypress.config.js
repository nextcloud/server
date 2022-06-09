const { defineConfig } = require('cypress')
const browserify = require('@cypress/browserify-preprocessor')
const getCompareSnapshotsPlugin = require('cypress-visual-regression/dist/plugin')

module.exports = defineConfig({
	projectId: 'xysa6x',

	viewportWidth: 1280,
	viewportHeight: 720,
	defaultCommandTimeout: 6000,
	retries: 2,

	env: {
		failSilently: false,
		type: 'actual',
	},
	screenshotsFolder: 'cypress/snapshots/actual',
	trashAssetsBeforeRuns: true,

	e2e: {
		baseUrl: 'http://localhost:8082/index.php',
		// We've imported your old cypress plugins here.
		// You may want to clean this up later by importing these.
		setupNodeEvents(on, config) {
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

			return config
		},
	},
})
