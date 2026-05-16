/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getViewer, getViewerActionsMenu, toggleViewerActions } from '../utils.ts'

describe('Delete image.png in viewer', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.uploadFile(user, 'viewer/image.png', 'image/png', '/image.png')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	after(function() {
		cy.logout()
	})

	it('See image.png in the list and open the viewer', function() {
		getRowForFile('image.png')
			.should('exist')
		triggerActionForFile('image.png', 'view')
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Delete the image and close viewer', function() {
		// open the menu
		toggleViewerActions()
		getViewerActionsMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: 'Delete' })
			.should('be.visible')
			.click()
	})

	it('Does not see the viewer anymore', function() {
		getViewer().should('not.exist')
	})

	it('Does not see image.png in the list anymore', function() {
		cy.visit('/apps/files')
		getRowForFile('image.png')
			.should('not.exist')
	})
})
