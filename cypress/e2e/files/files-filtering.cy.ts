/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder } from './FilesUtils'
import { FilesNavigationPage } from '../../pages/FilesNavigation'
import { FilesFilterPage } from '../../pages/FilesFilters'

describe('files: Filter in files list', { testIsolation: true }, () => {
	const appNavigation = new FilesNavigationPage()
	const filesFilters = new FilesFilterPage()
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.uploadContent(user, new Blob([]), 'text/csv', '/spreadsheet.csv')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/text.txt')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('filters current view by name', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// Set up a search query
		appNavigation.searchInput()
			.type('folder')

		// See that only the folder is visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')
		getRowForFile('spreadsheet.csv').should('not.exist')
	})

	it('can reset name filter', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// Set up a search query
		appNavigation.searchInput()
			.type('folder')

		// See that only the folder is visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')

		// reset the filter
		appNavigation.searchInput().should('have.value', 'folder')
		appNavigation.searchClearButton().should('exist').click()
		appNavigation.searchInput().should('have.value', '')

		// All are visible again
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')
	})

	it('filters current view by type', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')
		getRowForFile('spreadsheet.csv').should('be.visible')

		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Spreadsheets' })
			.should('be.visible')
			.click()
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()

		// See that only the spreadsheet is visible
		getRowForFile('spreadsheet.csv').should('be.visible')
		getRowForFile('file.txt').should('not.exist')
		getRowForFile('folder').should('not.exist')
	})

	it('can reset filter by type', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')

		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Spreadsheets' })
			.should('be.visible')
			.click()
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()

		// See folder is not visible
		getRowForFile('folder').should('not.exist')

		// clear filter
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitem', { name: /clear filter/i })
			.should('be.visible')
			.click()
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()

		// See folder is visible again
		getRowForFile('folder').should('be.visible')
	})

	it('can reset filter by clicking chip button', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')

		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Spreadsheets' })
			.should('be.visible')
			.click()
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()

		// See folder is not visible
		getRowForFile('folder').should('not.exist')

		// clear filter
		filesFilters.removeFilter('Spreadsheets')

		// See folder is visible again
		getRowForFile('folder').should('be.visible')
	})

	it('keeps name filter when changing the directory', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// Set up a search query
		appNavigation.searchInput()
			.type('folder')

		// See that only the folder is visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')

		// go to that folder
		navigateToFolder('folder')

		// see that the folder is also filtered
		getRowForFile('text.txt').should('not.exist')
	})

	it('keeps type filter when changing the directory', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Folders' })
			.should('be.visible')
			.click()
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.click()

		// See that only the folder is visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')

		// see filter is active
		filesFilters.activeFilters().contains(/Folder/).should('be.visible')

		// go to that folder
		navigateToFolder('folder')

		// see filter is still active
		filesFilters.activeFilters().contains(/Folder/).should('be.visible')

		// see that the folder is filtered
		getRowForFile('text.txt').should('not.exist')
	})

	/** Regression test of https://github.com/nextcloud/server/issues/47251 */
	it('keeps filter state when changing the directory', () => {
		// files are visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// enable type filter for folders
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Folders' })
			.should('be.visible')
			.click()
		// assert the button is checked
		cy.findByRole('menuitemcheckbox', { name: 'Folders' })
			.should('have.attr', 'aria-checked', 'true')
		// close the menu
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.click()

		// See the chips are active
		filesFilters.activeFilters()
			.should('have.length', 1)
			.contains(/Folder/).should('be.visible')

		// See that folder is visible but file not
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')

		// Change the directory
		navigateToFolder('folder')
		getRowForFile('folder').should('not.exist')

		// See that the chip is still active
		filesFilters.activeFilters()
			.should('have.length', 1)
			.contains(/Folder/).should('be.visible')
		// And also the button should be active
		filesFilters.filterContainter()
			.findByRole('button', { name: 'Type' })
			.should('be.visible')
			.click()
		cy.findByRole('menuitemcheckbox', { name: 'Folders' })
			.should('be.visible')
			.and('have.attr', 'aria-checked', 'true')
	})

	it('resets filter when changing the view', () => {
		// All are visible by default
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// Set up a search query
		appNavigation.searchInput()
			.type('folder')

		// See that only the folder is visible
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('not.exist')

		// go to other view
		appNavigation.views()
			.findByRole('link', { name: /personal files/i })
			.click()
		// wait for view changed
		cy.url().should('match', /apps\/files\/personal/)

		// see that the folder is not filtered
		getRowForFile('folder').should('be.visible')
		getRowForFile('file.txt').should('be.visible')

		// see the filter bar is gone
		appNavigation.searchInput().should('have.value', '')
	})
})
