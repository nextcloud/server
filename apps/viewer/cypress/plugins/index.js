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
const { addMatchImageSnapshotPlugin } = require('cypress-image-snapshot/plugin')

module.exports = (on, config) => {
	// Fix browserslist extend https://github.com/cypress-io/cypress/issues/2983#issuecomment-570616682
	on('file:preprocessor', browserify())

	addMatchImageSnapshotPlugin(on, config)
}
