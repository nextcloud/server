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

describe('See shared folder with link share', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.createFolder(user, '/Photos')
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg', '/Photos/image1.jpg')
			cy.uploadFile(user, 'image2.jpg', 'image/jpeg', '/Photos/image2.jpg')
			cy.uploadFile(user, 'image3.jpg', 'image/jpeg', '/Photos/image3.jpg')
			cy.uploadFile(user, 'image4.jpg', 'image/jpeg', '/Photos/image4.jpg')
			cy.uploadFile(user, 'video1.mp4', 'video/mp4', '/Photos/video1.mp4')

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
		cy.getFile('image3.jpg', { timeout: 10000 })
			.should('contain', 'image3 .jpg')
		cy.getFile('image4.jpg', { timeout: 10000 })
			.should('contain', 'image4 .jpg')
		cy.getFile('video1.mp4', { timeout: 10000 })
			.should('contain', 'video1 .mp4')
	})

	it('Share the Photos folder with a share link and access the share link', function() {
		cy.createLinkShare('/Photos').then(token => {
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

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image1.jpg')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Show image2 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 3)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show image3 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 3)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show image4 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show video1 on next', function() {
		cy.get('body > .viewer button.next').click()
		// only 2 because we don't know if we're at the end of the slideshow, current vid and prev img
		cy.get('body > .viewer .modal-container img').should('have.length', 1)
		cy.get('body > .viewer .modal-container video').should('have.length', 1)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
		cy.get('body > .viewer .modal-header__name').should('contain', 'video1.mp4')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show image1 again on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})
})
