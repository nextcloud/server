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

import { basename as pathBasename } from '@nextcloud/paths'

const source = '/apps/theming/img/background/anatoly-mikhaltsov-butterfly-wing-scale.jpg'
const basename = pathBasename(source)

describe('Open non-dav files in viewer', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'test-card.mp4', 'video/mp4')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('Open background', function() {
		const fileInfo = {
			filename: source,
			basename,
			mime: 'image/jpeg',
			source,
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
		cy.get('body > .viewer .modal-header__name').should('contain', basename)
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does not see navigation arrows', function() {
		cy.get('body > .viewer button.prev').should('not.be.visible')
		cy.get('body > .viewer button.next').should('not.be.visible')
	})

	it('See the menu but does not see the sidebar button', function() {
		// Menu exists
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('.action-button__icon.icon-menu-sidebar').should('not.exist')
	})

	it('The image source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', source)
	})
})
