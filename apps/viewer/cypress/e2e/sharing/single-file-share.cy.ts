/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('See shared folder with link share', function() {
	let imageToken
	let videoToken

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg')
			cy.uploadFile(user, 'video1.mp4', 'video/mp4')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')

			// Create shares
			cy.createLinkShare('/image1.jpg').then(token => { imageToken = token })
			cy.createLinkShare('/video1.mp4').then(token => { videoToken = token })

			// Done
			cy.logout()
		})
	})

	it('Opens the shared image in the viewer', function() {
		cy.visit(`/s/${imageToken}`)

		cy.contains('image1.jpg').should('be.visible')

		cy.intercept('GET', '**/apps/files_sharing/publicpreview/**').as('getImage')
		cy.openFileInSingleShare()
		cy.wait('@getImage')
			.its('response.statusCode')
			.should('eq', 200)

		// Make sure loading is finished
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')

		// The image source is the preview url
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/apps/files_sharing/publicpreview/')

		// See the menu icon and close button
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Opens the shared video in the viewer', function() {
		cy.visit(`/s/${videoToken}`)

		cy.contains('video1.mp4').should('be.visible')

		cy.intercept('GET', '**/public.php/dav/files/**').as('loadVideo')
		cy.openFileInSingleShare()
		cy.wait('@loadVideo')

		// Make sure loading is finished
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')

		// The video source is the preview url
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/public.php/dav/files/${videoToken}`)

		// See the menu icon and close button
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})
})
