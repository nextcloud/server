/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('Open mp4 videos in viewer', function() {
	let randUser

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			randUser = user

			// Upload test files
			cy.uploadFile(user, 'video1.mp4', 'video/mp4')
			cy.uploadFile(user, 'video2.mp4', 'video/mp4')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See videos in the list', function() {
		cy.getFile('video1.mp4', { timeout: 10000 })
			.should('contain', 'video1 .mp4')
		cy.getFile('video2.mp4', { timeout: 10000 })
			.should('contain', 'video2 .mp4')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('video1.mp4')
		cy.get('body > .viewer').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'video1.mp4')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container video').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The video source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/video1.mp4`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show video 2 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container video').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The video source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/video2.mp4`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})
})
