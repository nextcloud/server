/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, renameFile } from './FilesUtils.ts'

describe('Files: Can rename files', { testIsolation: false }, () => {
	before(() => {
		cy.createRandomUser().then((user) => {
			cy.uploadContent(user, new Blob(), 'text/plain', '/foo.txt')
			cy.login(user)
			cy.visit('/apps/files/')
		})
	})

	it('See that the file is named correctly', () => {
		getRowForFile('foo.txt')
			.should('be.visible')
			.should('contain.text', 'foo.txt')
	})

	it('Can rename the file', () => {
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('renameFile')

		renameFile('foo.txt', 'bar.txt')
		cy.wait('@renameFile')

		getRowForFile('bar.txt')
			.should('be.visible')
	})

	it('See that the name is correctly shown', () => {
		getRowForFile('bar.txt')
			.should('be.visible')
			.should('contain.text', 'bar.txt')
	})

	it('See that the name preserved on reload', () => {
		cy.reload()

		getRowForFile('bar.txt')
			.should('be.visible')
			.should('contain.text', 'bar.txt')
	})
})
