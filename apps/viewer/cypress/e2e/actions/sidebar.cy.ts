/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 * @copyright Copyright (c) 2020 Gary Kim <gary@garykim.dev>
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

describe('Open the sidebar from the viewer and open viewer with sidebar already opened', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg')
			cy.uploadFile(user, 'image2.jpg', 'image/jpeg')
			cy.uploadFile(user, 'image3.jpg', 'image/jpeg')
			cy.uploadFile(user, 'image4.jpg', 'image/jpeg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See images in the list', function() {
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
		cy.getFile('image2.jpg', { timeout: 10000 })
			.should('contain', 'image2 .jpg')
		cy.getFile('image3.jpg', { timeout: 10000 })
			.should('contain', 'image3 .jpg')
		cy.getFile('image4.jpg', { timeout: 10000 })
			.should('contain', 'image4 .jpg')
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

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Open the sidebar', function() {
		// open the menu
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
		// open the sidebar
		cy.get('.action-button__icon.icon-menu-sidebar').click()
		cy.get('aside.app-sidebar').should('be.visible')
		// we hide the sidebar button if opened
		cy.get('.action-button__icon.icon-menu-sidebar').should('not.exist')
		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image1.jpg')
		// check we do not have a preview
		cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--with-figure')
		cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--compact')
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__figure').should('have.attr', 'style').should('contain', 'core/filetypes')
	})

	it('Sidebar is in compact mode', function() {
		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image1.jpg')
		// check we do not have a preview
		cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--with-figure')
		cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--compact')
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__figure').should('have.attr', 'style').should('contain', 'core/filetypes')
	})

	it('Change to next image with sidebar open', function() {
		cy.get('aside.app-sidebar').should('be.visible')

		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image1.jpg')

		// open the next file (image2.png) using the arrow
		cy.get('body > .viewer .button-vue.next').click()
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image2.jpg')
	})

	it('Change to previous image with sidebar open', function() {
		cy.get('aside.app-sidebar').should('be.visible')

		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image2.jpg')

		// open the previous file (image1.png) using the arrow
		cy.get('body > .viewer .button-vue.prev').click()
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__mainname').should('contain', 'image1.jpg')
	})

	it('Close the sidebar', function() {
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar__close').click()
		cy.get('aside.app-sidebar').should('not.exist')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').click()
		// The button to show the sidebar is shown again
		cy.get('.action-button__icon.icon-menu-sidebar').should('be.visible')
	})

	it('Open the viewer with the sidebar open', function() {
		cy.get('body > .viewer .modal-header button.header-close').click()
		cy.get('body > .viewer').should('not.exist')

		// open the sidebar without viewer open
		cy.getFile('image1.jpg').find('[data-cy-files-list-row-mtime]').click()

		cy.openFile('image1.jpg')
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
		cy.get('aside.app-sidebar').should('have.class', 'app-sidebar--full')

		// close the sidebar again
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar__close').click()
		cy.get('aside.app-sidebar').should('not.exist')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
	})
})
