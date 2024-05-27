/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder } from './FilesUtils'
import { UnifiedSearchFilter, getUnifiedSearchFilter, getUnifiedSearchInput, getUnifiedSearchModal, openUnifiedSearch } from '../core-utils.ts'

describe('files: Search and filter in files list', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/a folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/b file')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/a folder/c file')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('filters current view', () => {
		// All are visible by default
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// Set up a search query
		openUnifiedSearch()
		getUnifiedSearchInput().type('a folder')
		getUnifiedSearchFilter(UnifiedSearchFilter.FilterCurrentView).click({ force: true })
		// Wait for modal to close
		getUnifiedSearchModal().should('not.be.visible')

		// See that only the folder is visible
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('not.exist')
	})

	it('resets filter when changeing the directory', () => {
		// All are visible by default
		getRowForFile('a folder').should('be.visible')
		getRowForFile('b file').should('be.visible')

		// Set up a search query
		openUnifiedSearch()
		getUnifiedSearchInput().type('a folder')
		getUnifiedSearchFilter(UnifiedSearchFilter.FilterCurrentView).click({ force: true })
		// Wait for modal to close
		getUnifiedSearchModal().should('not.be.visible')

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
		openUnifiedSearch()
		getUnifiedSearchInput().type('a folder')
		getUnifiedSearchFilter(UnifiedSearchFilter.FilterCurrentView).click({ force: true })
		// Wait for modal to close
		getUnifiedSearchModal().should('not.be.visible')

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
	})
})
