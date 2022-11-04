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

import { randHash } from '../../utils'

const randUser = randHash()
const fileName = 'image1.jpg'

describe(`Download ${fileName} in viewer`, function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser)

		// Upload test files
		cy.createFolder(randUser, '/Photos')
		cy.uploadFile(randUser, 'image1.jpg', 'image/jpeg', '/Photos/image1.jpg')
		cy.uploadFile(randUser, 'image2.jpg', 'image/jpeg', '/Photos/image2.jpg')
	})
	after(function() {
		// already logged out after visiting share link
		// cy.logout()
	})

	it('See the default files list', function() {
		cy.login(randUser)
		cy.visit('/apps/files')

		cy.get('.files-fileList tr').should('contain', 'welcome.txt')
		cy.get('.files-fileList tr').should('contain', 'Photos')
	})

	it('See shared files in the list', function() {
		cy.openFile('Photos')
		cy.get('.files-fileList tr[data-file="image1.jpg"]', { timeout: 10000 })
			.should('contain', 'image1.jpg')
		cy.get('.files-fileList tr[data-file="image2.jpg"]', { timeout: 10000 })
			.should('contain', 'image2.jpg')
	})

	it('Share the Photos folder with a share link and access the share link', function() {
		cy.createLinkShare('/Photos').then(token => {
			// Open the sidebar
			cy.visit('/apps/files')
			cy.get('.files-fileList tr[data-file="Photos"] .fileactions .action-share', { timeout: 10000 }).click()
			cy.get('aside.app-sidebar').should('be.visible')

			// Open the share menu
			cy.get(`.sharing-link-list > .sharing-entry > .action-item[href*='/s/${token}'] + .sharing-entry__actions .action-item__menutoggle`).click()
			cy.get('label:contains(\'Hide download\')').as('hideDownloadBtn').click()
			cy.get('@hideDownloadBtn').prev('input[type=checkbox]').should('be.checked')

			// Log out and access link share
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

	it('See the title on the viewer header but not the Download button', function() {
		cy.get('body > .viewer .modal-title').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header a.action-item .download-icon').should('not.exist')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

})
