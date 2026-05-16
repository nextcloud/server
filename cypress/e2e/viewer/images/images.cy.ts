/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getViewer } from '../utils.ts'

describe('Open images in viewer', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.uploadFile(user, 'viewer/image1.jpg', 'image/jpeg', '/image1.jpg')
			cy.uploadFile(user, 'viewer/image2.jpg', 'image/jpeg', '/image2.jpg')
			cy.uploadFile(user, 'viewer/image3.jpg', 'image/jpeg', '/image3.jpg')
			cy.uploadFile(user, 'viewer/image4.jpg', 'image/jpeg', '/image4.jpg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See images in the list', function() {
		getRowForFile('image1.jpg').should('exist')
		getRowForFile('image2.jpg').should('exist')
		getRowForFile('image3.jpg').should('exist')
		getRowForFile('image4.jpg').should('exist')
	})

	it('Open the viewer on file click', function() {
		triggerActionForFile('image1.jpg', 'view')
		getViewer().should('be.visible')
	})

	it('Does not see a loading animation', function() {
		getViewer()
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		// only 2 because we don't know if we're at the end of the slideshow, current img and next one
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Show image2 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 3)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('Show image3 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 3)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('Show image4 on next', function() {
		cy.get('body > .viewer button.next').click()
		// only 2 because we don't know if we're at the end of the slideshow, current img and previous one
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('Show image1 again on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})
})
