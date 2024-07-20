/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, moveFile, copyFile, navigateToFolder } from './FilesUtils.ts'

describe('Files: Move or copy files', { testIsolation: true }, () => {
	let currentUser
	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			currentUser = user
			cy.login(user)
		})
	})
	afterEach(() => {
		// nice to have cleanup
		cy.deleteUser(currentUser)
	})


	it('Can copy a file to new folder', () => {
		// Prepare initial state
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
			.mkdir(currentUser, '/new-folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		copyFile('original.txt', 'new-folder')

		navigateToFolder('new-folder')

		cy.url().should('contain', 'dir=/new-folder')
		getRowForFile('original.txt').should('be.visible')
		getRowForFile('new-folder').should('not.exist')
	})

	it('Can move a file to new folder', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
			.mkdir(currentUser, '/new-folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		moveFile('original.txt', 'new-folder')

		// wait until visible again
		getRowForFile('new-folder').should('be.visible')

		// original should be moved -> not exist anymore
		getRowForFile('original.txt').should('not.exist')
		navigateToFolder('new-folder')

		cy.url().should('contain', 'dir=/new-folder')
		getRowForFile('original.txt').should('be.visible')
		getRowForFile('new-folder').should('not.exist')
	})

	/**
	 * Test for https://github.com/nextcloud/server/issues/41768
	 */
	it('Can move a file to folder with similar name', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original')
			.mkdir(currentUser, '/original folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		moveFile('original', 'original folder')

		// wait until visible again
		getRowForFile('original folder').should('be.visible')

		// original should be moved -> not exist anymore
		getRowForFile('original').should('not.exist')
		navigateToFolder('original folder')

		cy.url().should('contain', 'dir=/original%20folder')
		getRowForFile('original').should('be.visible')
		getRowForFile('original folder').should('not.exist')
	})

	it('Can move a file to its parent folder', () => {
		cy.mkdir(currentUser, '/new-folder')
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/new-folder/original.txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		navigateToFolder('new-folder')
		cy.url().should('contain', 'dir=/new-folder')

		moveFile('original.txt', '/')

		// wait until visible again
		cy.get('main').contains('No files in here').should('be.visible')

		// original should be moved -> not exist anymore
		getRowForFile('original.txt').should('not.exist')

		cy.visit('/apps/files')
		getRowForFile('new-folder').should('be.visible')
		getRowForFile('original.txt').should('be.visible')
	})

	it('Can copy a file to same folder', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		copyFile('original.txt', '.')

		getRowForFile('original.txt').should('be.visible')
		getRowForFile('original (copy).txt').should('be.visible')
	})

	it('Can copy a file multiple times to same folder', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original (copy).txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		copyFile('original.txt', '.')

		getRowForFile('original.txt').should('be.visible')
		getRowForFile('original (copy 2).txt').should('be.visible')
	})

	/**
	 * Test that a copied folder with a dot will be renamed correctly ('foo.bar' -> 'foo.bar (copy)')
	 * Test for: https://github.com/nextcloud/server/issues/43843
	 */
	it('Can copy a folder to same folder', () => {
		cy.mkdir(currentUser, '/foo.bar')
		cy.login(currentUser)
		cy.visit('/apps/files')

		copyFile('foo.bar', '.')

		getRowForFile('foo.bar').should('be.visible')
		getRowForFile('foo.bar (copy)').should('be.visible')
	})

	/** Test for https://github.com/nextcloud/server/issues/43329 */
	context('escaping file and folder names', () => {
		it('Can handle files with special characters', () => {
			cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
				.mkdir(currentUser, '/can\'t say')
			cy.login(currentUser)
			cy.visit('/apps/files')

			copyFile('original.txt', 'can\'t say')

			navigateToFolder('can\'t say')

			cy.url().should('contain', 'dir=/can%27t%20say')
			getRowForFile('original.txt').should('be.visible')
			getRowForFile('can\'t say').should('not.exist')
		})

		/**
		 * If escape is set to false (required for test above) then "<a>foo" would result in "<a>foo</a>" if sanitizing is not disabled
		 * We should disable it as vue already escapes the text when using v-text
		 */
		it('does not incorrectly sanitize file names', () => {
			cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
				.mkdir(currentUser, '/<a href="#">foo')
			cy.login(currentUser)
			cy.visit('/apps/files')

			copyFile('original.txt', '<a href="#">foo')

			navigateToFolder('<a href="#">foo')

			cy.url().should('contain', 'dir=/%3Ca%20href%3D%22%23%22%3Efoo')
			getRowForFile('original.txt').should('be.visible')
			getRowForFile('<a href="#">foo').should('not.exist')
		})
	})
})
