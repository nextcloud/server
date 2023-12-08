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

const getRowForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${filename}"]`)
const getActionsForFile = (filename: string) => getRowForFile(filename).find('[data-cy-files-list-row-actions]')
const getActionButtonForFile = (filename: string) => getActionsForFile(filename).find('button[aria-label="Actions"]')
const triggerActionForFile = (filename: string, actionId: string) => {
	getActionButtonForFile(filename).click()
	cy.get(`[data-cy-files-list-row-action="${actionId}"] > button`).should('exist').click()
}

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

		// intercept the copy so we can wait for it
		cy.intercept('COPY', /\/remote.php\/dav\/files\//).as('copyFile')

		// Open actions and trigger copy-move action
		getRowForFile('original.txt').should('be.visible')
		triggerActionForFile('original.txt', 'move-copy')

		// select new folder
		cy.get('.file-picker [data-filename="new-folder"]').should('be.visible').click()
		// click copy
		cy.get('.file-picker').contains('button', 'Copy to new-folder').should('be.visible').click()

		// wait for copy to finish
		cy.wait('@copyFile')

		getRowForFile('new-folder').find('[data-cy-files-list-row-name-link]').click()

		cy.url().should('contain', 'dir=/new-folder')
		getRowForFile('original.txt').should('be.visible')
		getRowForFile('new-folder').should('not.exist')
	})

	it('Can move a file to new folder', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt')
			.mkdir(currentUser, '/new-folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

		getRowForFile('original.txt').should('be.visible')
		triggerActionForFile('original.txt', 'move-copy')

		// select new folder
		cy.get('.file-picker [data-filename="new-folder"]').should('be.visible').click()
		// click copy
		cy.get('.file-picker').contains('button', 'Move to new-folder').should('be.visible').click()

		cy.wait('@moveFile')
		// wait until visible again
		getRowForFile('new-folder').should('be.visible')

		// original should be moved -> not exist anymore
		getRowForFile('original.txt').should('not.exist')
		getRowForFile('new-folder').should('be.visible').find('[data-cy-files-list-row-name-link]').click()

		cy.url().should('contain', 'dir=/new-folder')
		getRowForFile('original.txt').should('be.visible')
		getRowForFile('new-folder').should('not.exist')
	})

	it('Can move a file to its parent folder', () => {
		cy.mkdir(currentUser, '/new-folder')
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/new-folder/original.txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

		getRowForFile('new-folder').should('be.visible').find('[data-cy-files-list-row-name-link]').click()
		cy.url().should('contain', 'dir=/new-folder')

		getRowForFile('original.txt').should('be.visible')
		triggerActionForFile('original.txt', 'move-copy')

		// select new folder
		cy.get('.file-picker a[title="Home"]').should('be.visible').click()
		// click move
		cy.get('.file-picker').contains('button', 'Move').should('be.visible').click()

		cy.wait('@moveFile')
		// wait until visible again
		cy.get('main').contains('No files in here').should('be.visible')

		// original should be moved -> not exist anymore
		getRowForFile('original.txt').should('not.exist')

		cy.visit('/apps/files')
		getRowForFile('new-folder').should('be.visible')
		getRowForFile('original.txt').should('be.visible')
	})
})
