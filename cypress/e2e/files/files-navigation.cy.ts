/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder } from './FilesUtils.ts'

describe('files: Navigate through folders and observe behavior', () => {
	let user: User

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.mkdir(user, '/foo')
			cy.mkdir(user, '/foo/bar')
			cy.mkdir(user, '/foo/bar/baz')
		})
	})

	it('Shows root folder and we can navigate to the last folder', () => {
		cy.login(user)
		cy.visit('/apps/files/')

		getRowForFile('foo').should('be.visible')
		navigateToFolder('/foo/bar/baz')

		// Last folder is empty
		cy.get('[data-cy-files-list-row-fileid]').should('not.exist')
	})

	it('Highlight the previous folder when navigating back', () => {
		cy.go('back')
		getRowForFile('baz').should('be.visible')
			.invoke('attr', 'class').should('contain', 'active')

		cy.go('back')
		getRowForFile('bar').should('be.visible')
			.invoke('attr', 'class').should('contain', 'active')

		cy.go('back')
		getRowForFile('foo').should('be.visible')
			.invoke('attr', 'class').should('contain', 'active')
	})

	it('Can navigate forward again', () => {
		cy.go('forward')
		getRowForFile('bar').should('be.visible')
			.invoke('attr', 'class').should('contain', 'active')

		cy.go('forward')
		getRowForFile('baz').should('be.visible')
			.invoke('attr', 'class').should('contain', 'active')
	})
})
