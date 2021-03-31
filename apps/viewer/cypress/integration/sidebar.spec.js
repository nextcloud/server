/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 * @copyright Copyright (c) 2020 Gary Kim <gary@garykim.dev>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

import { randHash } from '../utils/'
const randUser = randHash()

describe('Open the sidebar from the viewer and open viewer with sidebar already opened', function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser, 'password')
		cy.login(randUser, 'password')

		// Upload test files
		cy.uploadFile('image1.jpg', 'image/jpeg')
		cy.uploadFile('image2.jpg', 'image/jpeg')
		cy.uploadFile('image3.jpg', 'image/jpeg')
		cy.uploadFile('image4.jpg', 'image/jpeg')
		cy.visit('/apps/files')

		// wait a bit for things to be settled
		cy.wait(1000)
	})
	after(function() {
		cy.logout()
	})

	it('See images in the list', function() {
		cy.get('#fileList tr[data-file="image1.jpg"]', { timeout: 10000 })
			.should('contain', 'image1.jpg')
		cy.get('#fileList tr[data-file="image2.jpg"]', { timeout: 10000 })
			.should('contain', 'image2.jpg')
		cy.get('#fileList tr[data-file="image3.jpg"]', { timeout: 10000 })
			.should('contain', 'image3.jpg')
		cy.get('#fileList tr[data-file="image4.jpg"]', { timeout: 10000 })
			.should('contain', 'image4.jpg')
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
		cy.get('body > .viewer .modal-title').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.icon-close').should('be.visible')
	})

	it('Does not have any visual regression 1', function() {
		// cy.matchImageSnapshot()
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
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__maintitle').should('contain', 'image1.jpg')
		// check we indeed have a preview
		cy.get('aside.app-sidebar .app-sidebar-header').should('have.class', 'app-sidebar-header--with-figure')
		cy.getFileId('image1.jpg').then(fileID1 => {
			cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__figure').should('have.attr', 'style').should('contain', fileID1)
		})

	})

	it('Does not have any visual regression 2', function() {
		// Comments have the user's username which is randomly generated for tests causing a difference in the snapshot.
		// Switch to sharing section to avoid the issue.
		cy.get('aside.app-sidebar a#sharing').click()

		// cy.matchImageSnapshot()
	})

	it('Change to next image with sidebar open', function() {
		cy.get('aside.app-sidebar').should('be.visible')

		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__maintitle').should('contain', 'image1.jpg')

		// open the next file (image2.png) using the arrow
		cy.get('body > .viewer .icon-next').click()
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__maintitle').should('contain', 'image2.jpg')
	})

	it('Does not have any visual regression 3', function() {
		// Comments have the user's username which is randomly generated for tests causing a difference in the snapshot.
		// Switch to sharing section to avoid the issue.
		cy.get('aside.app-sidebar a#sharing').click()

		// cy.matchImageSnapshot()
	})

	it('Change to previous image with sidebar open', function() {
		cy.get('aside.app-sidebar').should('be.visible')

		// check the sidebar is opened for the correct file
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__maintitle').should('contain', 'image2.jpg')

		// open the previous file (image1.png) using the arrow
		cy.get('body > .viewer .icon-previous').click()
		cy.get('aside.app-sidebar .app-sidebar-header .app-sidebar-header__maintitle').should('contain', 'image1.jpg')
	})

	it('Does not have any visual regression 4', function() {
		// Comments have the user's username which is randomly generated for tests causing a difference in the snapshot.
		// Switch to sharing section to avoid the issue.
		cy.get('aside.app-sidebar a#sharing').click()

		// cy.matchImageSnapshot()
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
		cy.get('body > .viewer .header-close.icon-close').click()
		cy.get('body > .viewer').should('not.exist')

		// open the sidebar without viewer open
		cy.get('#fileList tr[data-file="image1.jpg"] .date .modified').click()

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

	it('Does not have any visual regression 5', function() {
		// cy.matchImageSnapshot()
	})
})
