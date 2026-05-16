/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('Delete image.png in viewer', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'image.png', 'image/png')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See image.png in the list', function() {
		cy.getFile('image.png', { timeout: 10000 })
			.should('contain', 'image .png')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('image.png')
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
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
		// delete the file
		cy.get('.action-button:contains(\'Delete\')').click()
	})

	it('Does not see the viewer anymore', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('not.exist')
	})

	it('Does not see image.png in the list anymore', function() {
		cy.visit('/apps/files')
		cy.getFile('image.png', { timeout: 10000 })
			.should('not.exist')
	})
})
