/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Generate an image cypress test
 *
 * @param {string} fileName the image to upload and test against
 * @param {string} mimeType the image mime type
 * @param {string} source the optional custom source to check against
 */
export default function(fileName = 'image1.jpg', mimeType = 'image/jpeg', source = null) {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
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
		// Match image request
		cy.getFileId(fileName).then(fileId => {
			const matchRoute = source
				? `/remote.php/dav/files/*/${fileName}`
				: `/index.php/core/preview*fileId=${fileId}*`
			cy.intercept('GET', matchRoute).as('image')
		})

		// Open the file and check Viewer existence
		cy.openFile(fileName)
		cy.get('body > .viewer').should('be.visible')

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
