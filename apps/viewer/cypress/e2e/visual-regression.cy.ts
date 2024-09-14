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

describe('Visual regression tests', function() {
	let randUser

	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			randUser = user

			// Upload test files
			cy.uploadFile(user, 'test-card.mp4', 'video/mp4')
			cy.uploadFile(user, 'test-card.png', 'image/png')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})

		cy.window().then((win) => {
			// Load roboto font for visual regression consistency
			win.loadRoboto = true
			win.document.body.style.setProperty('--font-face', 'Roboto')
			win.document.body.style.setProperty('font-family', 'Roboto')
		})
	})

	it('See files in the list', function() {
		cy.getFile('test-card.mp4', { timeout: 10000 })
			.should('contain', 'test-card .mp4')
		cy.getFile('test-card.png', { timeout: 10000 })
			.should('contain', 'test-card .png')
	})

	it('Open the viewer on file click', function() {
		cy.intercept('GET', '**/viewer/css/fonts/roboto-*').as('roboto-font')
		cy.intercept('GET', '**/core/preview*').as('image1')
		cy.intercept('GET', '/remote.php/dav/files/*/test-card.mp4').as('video')
		cy.openFile('test-card.mp4')
		cy.wait('@roboto-font')
		cy.wait('@video')
		// We preload images, so we can check its loading here and not when clicking next
		cy.wait('@image1')
		cy.get('body > .viewer').should('be.visible')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'test-card.mp4')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container video').should('have.length', 1)
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video')
			.should('have.attr', 'src')
			.and('contain', `/remote.php/dav/files/${randUser.userId}/test-card.mp4`)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take test-card.mp4 screenshot', { retries: 0 }, function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active video').then(video => {
			video.get(0).pause()
			video.get(0).currentTime = 1
		})
		// wait a bit for things to be settled
		// eslint-disable-next-line
		cy.wait(250)
		cy.compareSnapshot('video', 0.02)
	})

	it('Show second file on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 1)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take test-card.png screenshot', { retries: 0 }, function() {
		cy.compareSnapshot('image')
	})

	it('Close and open image again', function() {
		cy.get('body > .viewer button.header-close').click()
		cy.get('body > .viewer').should('not.exist')

		// No need to intercept the request again, it's cached
		cy.openFile('test-card.png')

		cy.get('body > .viewer').should('be.visible')
		cy.get('body > .viewer .modal-header__name').should('contain', 'test-card.png')
		cy.get('body > .viewer .modal-container img').should('have.length', 1)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take test-card.png screenshot 2', { retries: 0 }, function() {
		cy.compareSnapshot('image2')
	})

	it('Open non-dav image', function() {
		const fileInfo = {
			filename: '/core/img/favicon.png',
			basename: 'favicon.png',
			mime: 'image/png',
			source: '/core/img/favicon.png',
			etag: 'abc',
			hasPreview: false,
			fileid: 123,
		}
		cy.intercept('GET', '/core/img/favicon.png').as('favicon')
		cy.window().then((win) => {
			win.OCA.Viewer.open({
				fileInfo,
				list: [fileInfo],
			})
		})
		cy.wait('@favicon')

		cy.get('body > .viewer .modal-container img').should('have.length', 1)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('not.be.visible')
		cy.get('body > .viewer button.next').should('not.be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Take non-dav logo.png screenshot', { retries: 0 }, function() {
		cy.compareSnapshot('non-dav')
	})
})
