/**
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { randHash } from '../utils/'
const randUser = randHash()

describe('Open non-dav files in viewer', function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser, 'password')
		cy.login(randUser, 'password')

		cy.visit('/apps/files')

		// wait a bit for things to be settled
		cy.wait(1000)
	})
	after(function() {
		cy.logout()
	})

	it('Open login background', function() {
		const fileInfo = {
			filename: '/core/img/app-background.jpg',
			basename: 'app-background.jpg',
			mime: 'image/jpeg',
			source: '/core/img/app-background.jpg',
			etag: 'abc',
			hasPreview: false,
			fileid: 123,
		}

		cy.window().then((win) => {
			win.OCA.Viewer.open({
				fileInfo,
				list: [fileInfo],
			})
		})
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('See the title and close button on the viewer header', function() {
		cy.get('body > .viewer .modal-title').should('contain', 'app-background.jpg')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does not see navigation arrows', function() {
		cy.get('body > .viewer button.prev').should('not.be.visible')
		cy.get('body > .viewer button.next').should('not.be.visible')
	})

	it('Does not see the menu or sidebar button', function() {
		// Menu does not exist
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('not.exist')
		cy.get('.action-button__icon.icon-menu-sidebar').should('not.exist')
	})

})
