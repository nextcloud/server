/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getViewer } from '../utils.ts'

describe('Open the new saved as image', function() {
	before(function() {
		cy.createRandomUser().then((user) => {
			cy.uploadFile(user, 'viewer/image1.jpg', 'image/jpeg', '/image1.jpg')
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('Open the viewer on file click', function() {
		getRowForFile('image1.jpg')
			.should('exist')
		triggerActionForFile('image1.jpg', 'view')
		getViewer().should('be.visible')
	})
	it('open the image editor', function() {
		getViewer()
			.findByRole('button', { name: 'Edit' })
			.should('be.visible')
			.click()
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
		getRowForFile('imageSave.jpg')
			.should('exist')
	})
})
