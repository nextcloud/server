/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder } from './FilesUtils'
import { UnifiedSearchPage } from '../../pages/UnifiedSearch.ts'

describe('files: Search and filter in files list', { testIsolation: true }, () => {
	const unifiedSearch = new UnifiedSearchPage()
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/a folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/b file')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/a folder/c file')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('files app supports local search', () => {
		unifiedSearch.openLocalSearch()
		unifiedSearch.localSearchInput()
			.should('not.have.css', 'display', 'none')
			.and('not.be.disabled')
	})

	it('filters current view', () => {
		// All are visible by default
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// Set up a search query
		unifiedSearch.openLocalSearch()
		unifiedSearch.typeLocalSearch('a folder')

		// See that only the folder is visible
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('not.exist')
	})

	it('resets filter when changeing the directory', () => {
		// All are visible by default
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// Set up a search query
		unifiedSearch.openLocalSearch()
		unifiedSearch.typeLocalSearch('a folder')

		// See that only the folder is visible
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('not.exist')

		// go to that folder
		navigateToFolder('a folder')

		// see that the folder is not filtered
		getRowForFile('c file').should('be.visible')
	})

	it('resets filter when changeing the view', () => {
		// All are visible by default
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// Set up a search query
		unifiedSearch.openLocalSearch()
		unifiedSearch.typeLocalSearch('a folder')

		// See that only the folder is visible
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('not.exist')

		// go to other view
		cy.get('[data-cy-files-navigation-item="personal"] a').click({ force: true })
		// wait for view changed
		cy.url().should('match', /apps\/files\/personal/)

		// see that the folder is not filtered
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// see the filter bar is gone
		unifiedSearch.localSearchInput().should('not.exist')
	})
})
