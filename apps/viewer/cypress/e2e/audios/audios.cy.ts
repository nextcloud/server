/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('Open mp3 and ogg audio in viewer', function() {
	let randUser

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			randUser = user

			// Upload test files
			cy.uploadFile(user, 'audio.mp3', 'audio/mpeg')
			cy.uploadFile(user, 'audio.ogg', 'audio/ogg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See audios in the list', function() {
		cy.getFile('audio.mp3', { timeout: 10000 })
			.should('contain', 'audio .mp3')
		cy.getFile('audio.ogg', { timeout: 10000 })
			.should('contain', 'audio .ogg')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('audio.mp3')
		cy.get('body > .viewer').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'audio.mp3')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container audio').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The audio source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active audio')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/audio.mp3`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show audio.ogg on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container audio').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The audio source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active audio')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/audio.ogg`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})
})
