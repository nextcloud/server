/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, triggerActionForFile } from './FilesUtils'

describe('files: Recent view', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
	}))

	it('see the recently created file in the recent view', () => {
		cy.visit('/apps/files/recent')
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')
	})

	/**
	 * Regression test: There was a bug that the files were correctly loaded but with invalid source
	 * so the delete action failed.
	 */
	it('can delete a file in the recent view', () => {
		cy.intercept('DELETE', '**/remote.php/dav/files/**').as('deleteFile')

		cy.visit('/apps/files/recent')
		// See the row
		getRowForFile('file.txt').should('be.visible')
		// delete the file
		triggerActionForFile('file.txt', 'delete')
		cy.wait('@deleteFile')
		// See it is not visible anymore
		getRowForFile('file.txt').should('not.exist')
		// also not existing in default view after reload
		cy.visit('/apps/files')
		getRowForFile('file.txt').should('not.exist')
	})
})
