// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

const browserify = require('@cypress/browserify-preprocessor')
const getCompareSnapshotsPlugin = require('cypress-visual-regression/dist/plugin');

module.exports = (on, config) => {
	// Fix browserslist extend https://github.com/cypress-io/cypress/issues/2983#issuecomment-570616682
	on('file:preprocessor', browserify())
	getCompareSnapshotsPlugin(on, config);

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
}
