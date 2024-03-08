/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

	// This was a bug previously
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
})
