/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import * as path from 'path'

const fileName = 'image.png'

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, fileName, 'image/png')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	after(function() {
		cy.logout()
	})

	it(`See "${fileName}" in the list`, function() {
		cy.getFile(fileName, { timeout: 10000 })
			.should('contain', fileName.replace(/(.*)\./, '$1 .'))
	})

	it('Open the viewer on file click', function() {
		cy.openFile(fileName)
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Download the image', function() {
		// open the menu
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
		// download the
		cy.findByRole('menuitem', { name: 'Download' }).click()
	})

	it('Compare downloaded file with asset by size', function() {
		const downloadsFolder = Cypress.config('downloadsFolder')
		const fixturesFolder = Cypress.config('fixturesFolder')

		const downloadedFilePath = path.join(downloadsFolder, fileName)
		const fixtureFilePath = path.join(fixturesFolder, fileName)

		cy.readFile(fixtureFilePath, 'binary', { timeout: 5000 }).then(fixtureBuffer => {
			cy.readFile(downloadedFilePath, 'binary', { timeout: 5000 })
				.should(downloadedBuffer => {
					if (downloadedBuffer.length !== fixtureBuffer.length) {
						throw new Error(`File size ${downloadedBuffer.length} is not ${fixtureBuffer.length}`)
					}
				})
		})
	})
})
