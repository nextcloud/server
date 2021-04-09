/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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

describe('Open custom list of images in viewer with pagination', function() {
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

	it('Open the viewer with a specific list', async function() {
		// make sure we only loadMore once
		let loaded = false

		// get the files fileids
		cy.getFileId('image1.jpg').then(fileID1 => {
			cy.getFileId('image2.jpg').then(fileID2 => {
				cy.getFileId('image3.jpg').then(fileID3 => {
					cy.getFileId('image4.jpg').then(fileID4 => {

						// open the viewer with custom list of fileinfo
						cy.window().then((win) => {
							win.OCA.Viewer.open({
								path: '/image1.jpg',
								list: [
									{
										basename: 'image1.jpg',
										filename: '/image1.jpg',
										hasPreview: true,
										fileid: parseInt(fileID1),
										mime: 'image/jpeg',
										permissions: 'RWD',
										etag: 'etag123',
									},
									{
										basename: 'image2.jpg',
										filename: '/image2.jpg',
										hasPreview: true,
										fileid: parseInt(fileID2),
										mime: 'image/jpeg',
										permissions: 'RWD',
										etag: 'etag456',
									},
								],
								// This will be triggered when we get to the end of the list
								loadMore() {
									// make sure we only loadMore once
									if (loaded) {
										return []
									}

									loaded = true
									return [
										{
											basename: 'image3.jpg',
											filename: '/image3.jpg',
											hasPreview: true,
											fileid: parseInt(fileID3),
											mime: 'image/jpeg',
											permissions: 'RWD',
											etag: 'etag123',
										},
										{
											basename: 'image4.jpg',
											filename: '/image4.jpg',
											hasPreview: true,
											fileid: parseInt(fileID4),
											mime: 'image/jpeg',
											permissions: 'RWD',
											etag: 'etag456',
										},
									]
								}
							})
						})
					})
				})
			})
		})
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

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer a.next').should('be.visible')
		cy.get('body > .viewer a.next').should('be.visible')
	})

	it('Does not have any visual regression 1', function() {
		// cy.matchImageSnapshot()
	})

	it('Show image2 on next', function() {
		cy.get('body > .viewer a.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer a.prev').should('be.visible')
		cy.get('body > .viewer a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Does not have any visual regression 2', function() {
		// cy.matchImageSnapshot()
	})

	it('Show image3 on next', function() {
		cy.get('body > .viewer a.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 3)
		cy.get('body > .viewer a.prev').should('be.visible')
		cy.get('body > .viewer a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Does not have any visual regression 3', function() {
		// cy.matchImageSnapshot()
	})

	it('Show image4 on next', function() {
		cy.get('body > .viewer a.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer a.prev').should('be.visible')
		cy.get('body > .viewer a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Does not have any visual regression 4', function() {
		// cy.matchImageSnapshot()
	})

	it('Show image1 again on next', function() {
		cy.get('body > .viewer a.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer a.prev').should('be.visible')
		cy.get('body > .viewer a.next').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('Does not have any visual regression 5', function() {
		// cy.matchImageSnapshot()
	})
})
