/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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

describe('Open mp4 videos in viewer', function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser, 'password')
		cy.login(randUser, 'password')

		// Upload test file
		cy.uploadFile('video1.mp4', 'image/jpeg')
		cy.uploadFile('video2.mp4', 'image/jpeg')
		cy.visit('/apps/files')

		// wait a bit for things to be settled
		cy.wait(1000)
	})
	after(function() {
		cy.logout()
	})

	it('See videos in the list', function() {
		cy.get('#fileList tr[data-file="video1.mp4"]', { timeout: 10000 })
			.should('contain', 'video1.mp4')
		cy.get('#fileList tr[data-file="video2.mp4"]', { timeout: 10000 })
			.should('contain', 'video2.mp4')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('video1.mp4')
		cy.get('#viewer-content').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('#viewer-content .modal-title').should('contain', 'video1.mp4')
		cy.get('#viewer-content .modal-header button.icon-menu-sidebar-white-forced').should('be.visible')
		cy.get('#viewer-content .modal-header button.icon-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('#viewer-content .modal-container video').should('have.length', 2)
		cy.get('#viewer-content .modal-container video.active')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser}/video1.mp4`)
		cy.get('#viewer-content a.next').should('be.visible')
		cy.get('#viewer-content a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('#viewer-content', { timeout: 4000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take screenshot 1', function() {
		// video are impossible to match with existing screenshot
		// just taking a screenshot to manually compare if needed
		cy.screenshot()
	})

	it('Show video 2 on next', function() {
		cy.get('#viewer-content a.next').click()
		cy.get('#viewer-content .modal-container video').should('have.length', 2)
		cy.get('#viewer-content .modal-container video.active')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser}/video2.mp4`)
		cy.get('#viewer-content a.prev').should('be.visible')
		cy.get('#viewer-content a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('#viewer-content', { timeout: 4000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take screenshot 2', function() {
		// video are impossible to match with existing screenshot
		// just taking a screenshot to manually compare if needed
		cy.screenshot()
	})
})
