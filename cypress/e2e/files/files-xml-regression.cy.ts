/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from './FilesUtils.ts'

/**
 * This is a regression test for https://github.com/nextcloud/server/issues/43331
 * Where files with XML entities in their names were wrongly displayed and could no longer be renamed / deleted etc.
 */
describe('Files: Can handle XML entities in file names', { testIsolation: false }, () => {
	before(() => {
		cy.createRandomUser().then((user) => {
			cy.uploadContent(user, new Blob(), 'text/plain', '/and.txt')
			cy.login(user)
			cy.visit('/apps/files/')
		})
	})

	it('Can reanme to a file name containing XML entities', () => {
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('renameFile')
		triggerActionForFile('and.txt', 'rename')
		getRowForFile('and.txt')
			.find('form[aria-label="Rename file"] input')
			.type('{selectAll}&amp;.txt{enter}')

		cy.wait('@renameFile')
		getRowForFile('&amp;.txt').should('be.visible')
	})

	it('After a reload the filename is preserved', () => {
		cy.reload()
		getRowForFile('&amp;.txt').should('be.visible')
		getRowForFile('&.txt').should('not.exist')
	})

	it('Can delete the file', () => {
		cy.intercept('DELETE', /\/remote.php\/dav\/files\//).as('deleteFile')
		triggerActionForFile('&amp;.txt', 'delete')
		cy.wait('@deleteFile')

		cy.contains('.toast-success', /Delete .* successfull/)
			.should('be.visible')
		getRowForFile('&amp;.txt').should('not.exist')

		cy.reload()
		getRowForFile('&amp;.txt').should('not.exist')
		getRowForFile('&.txt').should('not.exist')
	})
})
