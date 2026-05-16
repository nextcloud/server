/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('Open the new saved as image', function() {
	before(function() {
		cy.createRandomUser().then(user => {
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg')
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See images in the list', function() {
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
	})
	it('Open the viewer on file click', function() {
		cy.openFile('image1.jpg')
		cy.get('body > .viewer').should('be.visible')
	})
	it('open the image editor', function() {
		cy.get('button[aria-label="Edit"]').click()
	})
	it('Save the image', function() {
		cy.get('.FIE_topbar-save-button').click()
		cy.get('input[type="text"].SfxInput-Base').clear()
		cy.get('input[type="text"].SfxInput-Base').type('imageSave')
		cy.get('.SfxModal-Container button[color="primary"].SfxButton-root').contains('Save').click()
		cy.get('.FIE_topbar-close-button').click()
		cy.get('.modal-header__name').should('contain', 'imageSave.jpg')
		cy.get('.modal-header button[aria-label="Close"]').click()
	})
	it('See the new saved image in the list', function() {

		cy.getFile('imageSave.jpg', { timeout: 10000 })
			.should('contain', 'imageSave .jpg')
	})

})
