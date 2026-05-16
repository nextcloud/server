/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Generate a video cypress test
 *
 * @param {string} fileName the video to upload and test against
 * @param {string} mimeType the video mime type
 */
export default function(fileName = 'image1.jpg', mimeType = 'image/jpeg') {
	let randUser

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			randUser = user

			// Upload test files
			cy.uploadFile(user, fileName, mimeType)

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it(`See ${fileName} in the list`, function() {
		cy.getFile(fileName, { timeout: 10000 })
			.should('contain', fileName.replace(/(.*)\./, '$1 .'))
	})

	it('Open the viewer on file click and wait for loading to end', function() {
		// Match audio request
		cy.intercept('GET', `/remote.php/dav/files/${randUser.userId}/${fileName}`).as('source')

		// Open the file and check Viewer existence
		cy.openFile(fileName)
		cy.get('body > .viewer').should('be.visible')

		// Make sure loading is finished
		cy.wait('@source').its('response.statusCode').should('eq', 206)
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', fileName)
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does not see navigation arrows', function() {
		cy.get('body > .viewer button.prev').should('not.be.visible')
		cy.get('body > .viewer button.next').should('not.be.visible')
	})

	it('The video source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/${fileName}`)
	})
}
