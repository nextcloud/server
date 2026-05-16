/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getViewer } from '../utils'

/**
 * Generate an image cypress test
 *
 * @param fileName the image to upload and test against
 * @param mimeType the image mime type
 * @param source the optional custom source to check against
 */
export default function(fileName = 'image1.jpg', mimeType = 'image/jpeg', source: string | null = null) {
	let fileId: string

	before(function() {
		// Init user
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.uploadFile(user, `viewer/${fileName}`, mimeType, `/${fileName}`)
				.then(({ headers }) => {
					fileId = Number.parseInt(headers['oc-fileid']).toString()
				})

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it(`See ${fileName} in the list`, function() {
		getRowForFile(fileName).should('exist')
	})

	it('Open the viewer on file click and wait for loading to end', function() {
		// Match image request
		const matchRoute = source
			? `/remote.php/dav/files/*/${fileName}`
			: `/index.php/core/preview*fileId=${fileId}*`
		cy.intercept('GET', matchRoute).as('image')

		// Open the file and check Viewer existence
		triggerActionForFile(fileName, 'view')
		getViewer().should('be.visible')

		// Make sure loading is finished
		cy.wait('@image').its('response.statusCode').should('eq', 200)
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

	it(`The image source is the ${source ? 'remote' : 'preview'} url`, function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', source ?? '/index.php/core/preview')
	})
}
