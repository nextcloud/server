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

import { randHash } from '../../utils'
const randUser = randHash()

describe('Open mp4 videos in viewer', function() {
	before(function() {
		// Init user
		cy.nextcloudCreateUser(randUser)

		// Upload test file
		cy.uploadFile(randUser, 'video1.mp4', 'video/mp4')
		cy.uploadFile(randUser, 'video2.mp4', 'video/mp4')
	})
	after(function() {
		cy.logout()
	})

	it('See videos in the list', function() {
		cy.login(randUser)
		cy.visit('/apps/files')

		cy.get('.files-fileList tr[data-file="video1.mp4"]', { timeout: 10000 })
			.should('contain', 'video1.mp4')
		cy.get('.files-fileList tr[data-file="video2.mp4"]', { timeout: 10000 })
			.should('contain', 'video2.mp4')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('video1.mp4')
		cy.get('body > .viewer').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-title').should('contain', 'video1.mp4')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container video').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The video source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser}/video1.mp4`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show video 2 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container video').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The video source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser}/video2.mp4`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})
})
