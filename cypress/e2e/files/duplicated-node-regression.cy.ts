/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createFolder, getRowForFile, triggerActionForFile } from './FilesUtils.ts'

before(() => {
	cy.createRandomUser()
		.then((user) => {
			cy.mkdir(user, '/only once')
			cy.login(user)
			cy.visit('/apps/files')
		})
})

/**
 * Regression test for https://github.com/nextcloud/server/issues/47904
 */
it('Ensure nodes are not duplicated in the file list', () => {
	// See the folder
	getRowForFile('only once').should('be.visible')
	// Delete the folder
	cy.intercept('DELETE', '**/remote.php/dav/**').as('deleteFolder')
	triggerActionForFile('only once', 'delete')
	cy.wait('@deleteFolder')
	getRowForFile('only once').should('not.exist')
	// Create the folder again
	createFolder('only once')
	// See folder exists only once
	getRowForFile('only once')
		.should('have.length', 1)
})
