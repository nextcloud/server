/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { FilesNavigationPage } from '../../pages/FilesNavigation.ts'
import { getRowForFile, navigateToFolder } from './FilesUtils.ts'

describe('files: search', () => {
	let user: User

	const navigation = new FilesNavigationPage()

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.mkdir(user, '/some folder')
			cy.mkdir(user, '/some folder/nested folder')
			cy.mkdir(user, '/other folder')
			cy.mkdir(user, '/12345')
			cy.uploadContent(user, new Blob(['content']), 'text/plain', '/file.txt')
			cy.uploadContent(user, new Blob(['content']), 'text/plain', '/some folder/a file.txt')
			cy.uploadContent(user, new Blob(['content']), 'text/plain', '/some folder/a second file.txt')
			cy.uploadContent(user, new Blob(['content']), 'text/plain', '/some folder/nested folder/deep file.txt')
			cy.uploadContent(user, new Blob(['content']), 'text/plain', '/other folder/another file.txt')
			cy.login(user)
		})
	})

	beforeEach(() => {
		cy.visit('/apps/files')
	})

	it('updates the query on the URL', () => {
		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()

		navigation.searchInput().type('file')
		cy.url().should('match', /query=file($|&)/)
	})

	it('can search globally', () => {
		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()
		navigation.searchInput().type('file')

		getRowForFile('file.txt').should('be.visible')
		getRowForFile('a file.txt').should('be.visible')
		getRowForFile('a second file.txt').should('be.visible')
		getRowForFile('another file.txt').should('be.visible')
	})

	it('filter does also search locally', () => {
		navigateToFolder('some folder')
		getRowForFile('a file.txt').should('be.visible')

		navigation.searchInput().type('file')

		getRowForFile('a file.txt').should('be.visible')
		getRowForFile('a second file.txt').should('be.visible')
		getRowForFile('deep file.txt').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 3)
	})

	it('See "search everywhere" button', () => {
		// Not visible initially
		cy.get('[data-cy-files-filters]')
			.findByRole('button', { name: /Search everywhere/i })
			.should('not.to.exist')

		// add a filter
		navigation.searchInput().type('file')

		// see its visible
		cy.get('[data-cy-files-filters]')
			.findByRole('button', { name: /Search everywhere/i })
			.should('be.visible')

		// clear the filter
		navigation.searchClearButton().click()

		// see its not visible again
		cy.get('[data-cy-files-filters]')
			.findByRole('button', { name: /Search everywhere/i })
			.should('not.to.exist')
	})

	it('can make local search a global search', () => {
		navigateToFolder('some folder')
		getRowForFile('a file.txt').should('be.visible')

		navigation.searchInput().type('file')

		// see local results
		getRowForFile('a file.txt').should('be.visible')
		getRowForFile('a second file.txt').should('be.visible')
		getRowForFile('deep file.txt').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 3)

		// toggle global search
		cy.get('[data-cy-files-filters]')
			.findByRole('button', { name: /Search everywhere/i })
			.should('be.visible')
			.click()

		// see global results
		getRowForFile('file.txt').should('be.visible')
		getRowForFile('a file.txt').should('be.visible')
		getRowForFile('deep file.txt').should('be.visible')
		getRowForFile('a second file.txt').should('be.visible')
		getRowForFile('another file.txt').should('be.visible')
	})

	it('shows empty content when there are no results', () => {
		navigateToFolder('some folder')
		getRowForFile('a file.txt').should('be.visible')

		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()
		navigation.searchInput().type('xyz')

		// see the empty content message
		cy.contains('[role="note"]', /No search results for .xyz./)
			.should('be.visible')
			.within(() => {
				// see within there is a search box with the same value
				cy.findByRole('searchbox', { name: /search for files/i })
					.should('be.visible')
					.and('have.value', 'xyz')
			})
	})

	it('can alter search', () => {
		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()
		navigation.searchInput().type('other')

		getRowForFile('another file.txt').should('be.visible')
		getRowForFile('other folder').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 2)

		navigation.searchInput().type(' file')
		navigation.searchInput().should('have.value', 'other file')
		getRowForFile('another file.txt').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 1)
	})

	it('returns to file list if search is cleared', () => {
		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()
		navigation.searchInput().type('other')

		getRowForFile('another file.txt').should('be.visible')
		getRowForFile('other folder').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 2)

		navigation.searchClearButton().click()
		navigation.searchInput().should('have.value', '')
		getRowForFile('file.txt').should('be.visible')
		cy.get('[data-cy-files-list-row-fileid]').should('have.length', 5)
	})

	/**
	 * Problem:
	 * 1. Being on the search view
	 * 2. Press the refresh button (name of the current view)
	 * 3. See that the router link does not preserve the query
	 *
	 * We fix this with a navigation guard and need to verify that it works
	 */
	it('keeps the query in the URL', () => {
		navigation.searchScopeTrigger().click()
		navigation.searchScopeMenu()
			.should('be.visible')
			.findByRole('menuitem', { name: /search everywhere/i })
			.should('be.visible')
			.click()
		navigation.searchInput().type('file')

		// see that the search view is loaded
		getRowForFile('a file.txt').should('be.visible')
		// see the correct url
		cy.url().should('match', /query=file($|&)/)

		cy.intercept('SEARCH', '**/remote.php/dav/').as('search')
		// refresh the view
		cy.findByRole('button', { description: /reload current directory/i }).click()
		// wait for the request
		cy.wait('@search')
		// see that the search view is reloaded
		getRowForFile('a file.txt').should('be.visible')
		// see the correct url
		cy.url().should('match', /query=file($|&)/)
	})
})
