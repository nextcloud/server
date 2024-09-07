/**
 * @copyright Copyright (c) 2020 Florent Fayolle <florent@zeteo.me>
 *
 * @author Florent Fayolle <florent@zeteo.me>
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

import * as path from 'path'

const fileName = 'image1.jpg'

describe(`Download ${fileName} from viewer in link share`, function() {
	let token = null

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.createFolder(user, '/Photos')
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg', '/Photos/image1.jpg')
			cy.uploadFile(user, 'image2.jpg', 'image/jpeg', '/Photos/image2.jpg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		// already logged out after visiting share link
		// cy.logout()
	})

	it('See the default files list', function() {
		cy.getFile('welcome.txt').should('contain', 'welcome .txt')
		cy.getFile('Photos').should('contain', 'Photos')
	})

	it('See shared files in the list', function() {
		cy.openFile('Photos')
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
		cy.getFile('image2.jpg', { timeout: 10000 })
			.should('contain', 'image2 .jpg')
	})

	it('Share the Photos folder with a share link and access the share link', function() {
		cy.createLinkShare('/Photos').then(newToken => {
			token = newToken
			cy.logout()
			cy.visit(`/s/${token}`)
		})
	})

	it('Open the viewer on file click', function() {
		cy.openFile('image1.jpg')
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('See the title and the close icon on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('See the menu on the viewer header and open it', function() {
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible').click()
	})

	it('See the full screen and download icons in the menu', function() {
		cy.get('body > .v-popper__popper ul span.fullscreen-icon').should('be.visible')
		cy.get(`body > .v-popper__popper ul a.action-link[href*='/public.php/dav/files/${token}/image1.jpg']`).should('be.visible')
	})

	it('Download the image', function() {
		// https://github.com/cypress-io/cypress/issues/14857
		cy.window().then((win) => { setTimeout(() => { win.location.reload() }, 5000) })
		// download the file
		cy.get(`body > .v-popper__popper ul a.action-link[href*='/public.php/dav/files/${token}/image1.jpg']`).click()
	})

	it('Compare downloaded file with asset by size', function() {
		const downloadsFolder = Cypress.config('downloadsFolder')
		const fixturesFolder = Cypress.config('fixturesFolder')

		const downloadedFilePath = path.join(downloadsFolder, fileName)
		const fixtureFilePath = path.join(fixturesFolder, fileName)

		cy.readFile(fixtureFilePath, 'binary', { timeout: 5000 }).then(fixtureBuffer => {
			cy.readFile(downloadedFilePath, 'binary', { timeout: 5000 })
				.should(downloadedBuffer => {
					if (downloadedBuffer.length !== fixtureBuffer.length) {
						throw new Error(`File size ${downloadedBuffer.length} is not ${fixtureBuffer.length}`)
					}
				})
		})
	})
})
