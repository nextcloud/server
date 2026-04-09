/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/e2e-test-server/cypress'

import { deleteFileWithRequest, triggerFileListAction } from '../files/FilesUtils.ts'

const FILE_COUNT = 5
describe('files_trashbin: Empty trashbin action', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			// create 5 fake files and move them to trash
			for (let index = 0; index < FILE_COUNT; index++) {
				cy.uploadContent(user, new Blob(['<content>']), 'text/plain', `/file${index}.txt`)
				deleteFileWithRequest(user, `/file${index}.txt`)
			}
			// login
			cy.login(user)
		})
	})

	it('Can empty trashbin', () => {
		cy.visit('/apps/files')
		// Home have no files (or the default welcome file)
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
