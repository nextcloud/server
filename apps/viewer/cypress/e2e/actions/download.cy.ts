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

const fileName = 'image.png'

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, fileName, 'image/png')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	after(function() {
		cy.logout()
	})

	it(`See "${fileName}" in the list`, function() {
		cy.getFile(fileName, { timeout: 10000 })
			.should('contain', fileName.replace(/(.*)\./, '$1 .'))
	})

	it('Open the viewer on file click', function() {
		cy.openFile(fileName)
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Download the image', function() {
		// open the menu
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
		// download the file
		cy.get('.action-link .download-icon').click()
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
