/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { deleteFileWithRequest, getRowForFile, triggerActionForFile, triggerFileListAction } from '../files/FilesUtils.ts'

const FILE_COUNT = 5
describe('files_trashbin: Empty trashbin action', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			// create 5 fake files
			for (let index = 0; index < FILE_COUNT; index++) {
				cy.uploadContent(user, new Blob(['<content>']), 'text/plain', `/file${index}.txt`)
			}

			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	it('Can delete files', () => {
		for (let i = 0; i < FILE_COUNT; i++) {
			getRowForFile(`file${i}.txt`).should('be.visible')
		}

		cy.intercept('DELETE', '**/remote.php/dav/files/**').as('deleteFile')

		// Delete all files one by one
		for (let i = 0; i < FILE_COUNT; i++) {
			triggerActionForFile(`file${i}.txt`, 'delete')
			cy.wait('@deleteFile').its('response.statusCode').should('eq', 204)
		}

		cy.get('@deleteFile.all').should('have.length', FILE_COUNT)

		for (let i = 0; i < FILE_COUNT; i++) {
			getRowForFile(`file${i}.txt`).should('not.exist')
		}
	})

	it('Can empty trashbin', () => {
		// Delete files from home
		for (let index = 0; index < FILE_COUNT; index++) {
			deleteFileWithRequest(user, `/file${index}.txt`)
		}

		// Home have no files (or the default welcome file)
		cy.visit('/apps/files')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 1)
		cy.get('[data-cy-files-list-action="empty-trash"]').should('not.exist')

		// Go to trashbin, and see our deleted files
		cy.visit('/apps/files/trashbin')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', FILE_COUNT)

		// Empty trashbin
		cy.intercept('DELETE', '**/remote.php/dav/trashbin/**').as('emptyTrash')
		triggerFileListAction('empty-trash')

		// Confirm dialog
		cy.get('[role=dialog]').should('be.visible')
			.findByRole('button', { name: 'Empty deleted files' }).click()

		// Wait for the request to finish
		cy.wait('@emptyTrash').its('response.statusCode').should('eq', 204)
		cy.get('@emptyTrash.all').should('have.length', 1)

		// Trashbin should be empty
		cy.get('[data-cy-files-list-row-fileid]').should('not.exist')
	})

	it('Cancelling empty trashbin action does not delete anything', () => {
		// Delete files from home
		new Array(FILE_COUNT).fill(0).forEach((_, index) => {
			deleteFileWithRequest(user, `/file${index}.txt`)
		})

		// Go to trashbin, and see our deleted files
		cy.visit('/apps/files/trashbin')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', FILE_COUNT)

		// Empty trashbin
		cy.intercept('DELETE', '**/remote.php/dav/trashbin/**').as('emptyTrash')
		triggerFileListAction('empty-trash')

		// Cancel dialog
		cy.get('[role=dialog]').should('be.visible')
			.findByRole('button', { name: 'Cancel' }).click()

		// request was never sent
		cy.get('@emptyTrash').should('not.exist')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', FILE_COUNT)
	})

})
