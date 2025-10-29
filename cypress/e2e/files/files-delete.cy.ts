/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getRowForFile, navigateToFolder, selectAllFiles, triggerActionForFile } from './FilesUtils.ts'

describe('files: Delete files using file actions', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
		})
	})

	it('can delete file', () => {
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')

		// The file must exist and the preview loaded as it locks the file
		getRowForFile('file.txt')
			.should('be.visible')
			.find('.files-list__row-icon-preview--loaded')
			.should('exist')

		cy.intercept('DELETE', '**/remote.php/dav/files/**').as('deleteFile')

		triggerActionForFile('file.txt', 'delete')
		cy.wait('@deleteFile').its('response.statusCode').should('eq', 204)
	})

	it('can delete multiple files', () => {
		cy.mkdir(user, '/root')
		for (let i = 0; i < 5; i++) {
			cy.uploadContent(user, new Blob([]), 'text/plain', `/root/file${i}.txt`)
		}
		cy.login(user)
		cy.visit('/apps/files')
		navigateToFolder('/root')

		// The file must exist and the preview loaded as it locks the file
		cy.get('.files-list__row-icon-preview--loaded')
			.should('have.length', 5)

		cy.intercept('DELETE', '**/remote.php/dav/files/**').as('deleteFile')

		// select all
		selectAllFiles()
		cy.get('[data-cy-files-list-selection-actions]')
			.findByRole('button', { name: 'Actions' })
			.click()
		cy.get('[data-cy-files-list-selection-action="delete"]')
			.findByRole('menuitem', { name: /^Delete files/ })
			.click()

		// see dialog for confirmation
		cy.findByRole('dialog', { name: 'Confirm deletion' })
			.findByRole('button', { name: 'Delete files' })
			.click()

		cy.wait('@deleteFile')
		cy.get('@deleteFile.all')
			.should('have.length', 5)

			.should((all: any) => {
				for (const call of all) {
					expect(call.response.statusCode).to.equal(204)
				}
			})
	})
})
