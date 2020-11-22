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
import * as path from 'path';
const randUser = randHash()
const fileName = "image.png"
const fileSize = 4531680; // du -b image.png

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser, 'password')
		cy.login(randUser, 'password')

		// Upload test files
		cy.uploadFile(fileName, 'image/png')
		cy.visit('/apps/files')

		// wait a bit for things to be settled
		cy.wait(2000)
	})

	after(function() {
		cy.logout()
	})

	it(`See "${fileName}" in the list`, function() {
		cy.get(`#fileList tr[data-file="${fileName}"]`, { timeout: 10000 })
			.should('contain', fileName)
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
		cy.get('.action-link__icon.icon-download').click()
	})

	it('Compare downloaded file with asset by size', function() {
		const downloadsFolder = Cypress.config('downloadsFolder')
		const downloadedFileName = path.join(downloadsFolder, fileName)
		cy.readFile(downloadedFileName, 'binary', { timeout: 15000 })
			.should((buffer) => {
				if (buffer.length !== fileSize) {
					throw new Error(`File size ${buffer.length} is not ${fileSize}`)
				}
			})
	})
})
