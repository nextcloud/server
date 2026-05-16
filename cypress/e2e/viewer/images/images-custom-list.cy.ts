/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile } from '../../files/FilesUtils.ts'
import { getViewer } from '../utils.ts'

describe('Open custom images list in viewer', function() {
	const fileIds: Record<string, string> = {}

	before(function() {
		// Init user
		cy.createRandomUser().then((user) => {
			// Upload test files
			cy.uploadFile(user, 'viewer/image1.jpg', 'image/jpeg', '/image1.jpg')
				.then(({ headers }) => {
					fileIds['image1.jpg'] = Number.parseInt(headers['oc-fileid']).toString()
				})
			cy.uploadFile(user, 'viewer/image2.jpg', 'image/jpeg', '/image2.jpg')
				.then(({ headers }) => {
					fileIds['image2.jpg'] = Number.parseInt(headers['oc-fileid']).toString()
				})
			cy.uploadFile(user, 'viewer/image3.jpg', 'image/jpeg', '/image3.jpg')
				.then(({ headers }) => {
					fileIds['image3.jpg'] = Number.parseInt(headers['oc-fileid']).toString()
				})
			cy.uploadFile(user, 'viewer/image4.jpg', 'image/jpeg', '/image4.jpg')
				.then(({ headers }) => {
					fileIds['image4.jpg'] = Number.parseInt(headers['oc-fileid']).toString()
				})

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})
	after(function() {
		cy.logout()
	})

	it('See images in the list', function() {
		getRowForFile('image1.jpg').should('exist')
		getRowForFile('image2.jpg').should('exist')
		getRowForFile('image3.jpg').should('exist')
		getRowForFile('image4.jpg').should('exist')
	})

	it('Open the viewer with a specific list', function() {
		// open the viewer with custom list of fileinfo
		cy.window().then((win) => {
			win.OCA.Viewer.open({
				path: '/image1.jpg',
				list: [
					{
						basename: 'image1.jpg',
						filename: '/image1.jpg',
						hasPreview: true,
						fileid: parseInt(fileIds['image1.jpg']),
						permissions: 'RWD',
						mime: 'image/jpeg',
						etag: '123456789',
					},
					{
						basename: 'image3.jpg',
						filename: '/image3.jpg',
						hasPreview: true,
						fileid: parseInt(fileIds['image3.jpg']),
						permissions: 'R',
						mime: 'image/jpeg',
						etag: '987654321',
					},
				],
			})
		})
		getViewer().should('be.visible')
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

	it('Does see next navigation arrows', function() {
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer .modal-container img').should('have.attr', 'src')
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('Show image3 on next', function() {
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
fileIds['image3.jpg']
	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})

	it('See the menu icon and title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image3.jpg')
		cy.get('body > .viewer .modal-header button.action-item__menutoggle').should('be.visible')
		cy.get('body > .viewer .modal-header button.header-close').should('be.visible')
	})

	it('Show image1 on next', function() {
		cy.get('body > .viewer button.next').click()
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
	})

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
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

	it('The image source is the preview url', function() {
		cy.get('body > .viewer .modal-container .viewer__file.viewer__file--active img')
			.should('have.attr', 'src')
			.and('contain', '/index.php/core/preview')
	})
})
