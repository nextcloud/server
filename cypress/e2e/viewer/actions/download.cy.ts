/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import * as path from 'path'
import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getViewer, getViewerActionsMenu, toggleViewerActions } from '../utils.ts'

const fileName = 'image.png'

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.uploadFile(user, path.join('viewer', fileName), 'image/png', `/${fileName}`)

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	after(function() {
		cy.logout()
	})

	it('Open the viewer on file click', function() {
		getRowForFile(fileName)
			.should('exist')
		triggerActionForFile(fileName, 'view')
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		getViewer()
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Download the image', function() {
		// open the menu
		toggleViewerActions()
		getViewerActionsMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: 'Download' })
			.should('be.visible')
			.click()
	})

	it('Compare downloaded file with asset by size', function() {
		const downloadsFolder = Cypress.config('downloadsFolder')
		const fixturesFolder = Cypress.config('fixturesFolder')

		const downloadedFilePath = path.join(downloadsFolder, fileName)
		const fixtureFilePath = path.join(fixturesFolder as string, 'viewer', fileName)

		cy.readFile(fixtureFilePath, 'binary', { timeout: 5000 }).then((fixtureBuffer) => {
			cy.readFile(downloadedFilePath, 'binary', { timeout: 5000 })
				.should((downloadedBuffer) => {
					if (downloadedBuffer.length !== fixtureBuffer.length) {
						throw new Error(`File size ${downloadedBuffer.length} is not ${fixtureBuffer.length}`)
					}
				})
		})
	})
})
