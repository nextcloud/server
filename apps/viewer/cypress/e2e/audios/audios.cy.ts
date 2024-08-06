/**
 * @copyright Copyright (c) 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

describe('Open mp3 and ogg audio in viewer', function() {
	let randUser

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			randUser = user

			// Upload test files
			cy.uploadFile(user, 'audio.mp3', 'audio/mpeg')
			cy.uploadFile(user, 'audio.ogg', 'audio/ogg')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See audios in the list', function() {
		cy.getFile('audio.mp3', { timeout: 10000 })
			.should('contain', 'audio .mp3')
		cy.getFile('audio.ogg', { timeout: 10000 })
			.should('contain', 'audio .ogg')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('audio.mp3')
		cy.get('body > .viewer').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'audio.mp3')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container audio').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The audio source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active audio')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/audio.mp3`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Show audio.ogg on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container audio').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The audio source is the remote url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active audio')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/audio.ogg`)
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})
})
