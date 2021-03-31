/**
 * @copyright Copyright (c) 2020 Florent Fayolle <florent@zeteo.me>
 *
 * @author Florent Fayolle <florent@zeteo.me>
 *
 * @license GNU AGPL version 3 or any later version
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

import { randHash } from '../utils'
import * as path from 'path'

const randUser = randHash()
const fileName = 'image1.jpg'

describe(`Download ${fileName} from viewer in link share`, function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser, 'password')
		cy.login(randUser, 'password')

		// Upload test files
		cy.createFolder('Photos')
		cy.uploadFile('image1.jpg', 'image/jpeg', '/Photos')
		cy.uploadFile('image2.jpg', 'image/jpeg', '/Photos')
		cy.visit('/apps/files')

		// wait a bit for things to be settled
		cy.wait(1000)
	})
	after(function() {
		// already logged out after visiting share link
		// cy.logout()
	})

	it('See the default files list', function() {
		cy.get('#fileList tr').should('contain', 'welcome.txt')
		cy.get('#fileList tr').should('contain', 'Photos')
	})

	it('Does not have any visual regression 1', function() {
		// cy.matchImageSnapshot()
	})

	it('See shared files in the list', function() {
		cy.openFile('Photos')
		cy.get('#fileList tr[data-file="image1.jpg"]', { timeout: 10000 })
			.should('contain', 'image1.jpg')
		cy.get('#fileList tr[data-file="image2.jpg"]', { timeout: 10000 })
			.should('contain', 'image2.jpg')
	})

	it('Does not have any visual regression 2', function() {
		// cy.matchImageSnapshot()
	})

	it('Share the Photos folder with a share link and access the share link', function() {
		cy.createLinkShare('/Photos').then(token => {
			cy.logout()
			cy.visit(`/s/${token}`)
		})
	})

	it('Does not have any visual regression 3', function() {
		// cy.matchImageSnapshot()
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

	it('See the download icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-title').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header a.action-item.icon-download').should('be.visible')
		cy.get('body > .viewer .modal-header button.icon-close').should('be.visible')
	})

	it('Download the image', function() {
		// download the file
		cy.get('body > .viewer .modal-header a.action-item.icon-download').click()
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
