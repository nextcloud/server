/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getActionButtonForFile, getRowForFile, triggerActionForFile } from './FilesUtils'

describe('files: Favorites', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
			cy.mkdir(user, '/new folder')
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	it('Mark file as favorite', () => {
		// See file exists
		getRowForFile('file.txt')
			.should('exist')

		cy.intercept('POST', '**/apps/files/api/v1/files/file.txt').as('addToFavorites')
		// Click actions
		getActionButtonForFile('file.txt').click({ force: true })
		// See action is called 'Add to favorites'
		cy.get('[data-cy-files-list-row-action="favorite"] > button').last()
			.should('exist')
			.and('contain.text', 'Add to favorites')
			.click({ force: true })
		cy.wait('@addToFavorites')
		// See favorites star
		getRowForFile('file.txt')
			.findByRole('img', { name: 'Favorite' })
			.should('exist')
	})

	it('Un-mark file as favorite', () => {
		// See file exists
		getRowForFile('file.txt')
			.should('exist')

		cy.intercept('POST', '**/apps/files/api/v1/files/file.txt').as('addToFavorites')
		// toggle favorite
		triggerActionForFile('file.txt', 'favorite')
		cy.wait('@addToFavorites')

		// See favorites star
		getRowForFile('file.txt')
			.findByRole('img', { name: 'Favorite' })
			.should('be.visible')

		// Remove favorite
		// click action button
		getActionButtonForFile('file.txt').click({ force: true })
		// See action is called 'Remove from favorites'
		cy.get('[data-cy-files-list-row-action="favorite"] > button').last()
			.should('exist')
			.and('have.text', 'Remove from favorites')
			.click({ force: true })
		cy.wait('@addToFavorites')
		// See no favorites star anymore
		getRowForFile('file.txt')
			.findByRole('img', { name: 'Favorite' })
			.should('not.exist')
	})

	it('See favorite folders in navigation', () => {
		cy.intercept('POST', '**/apps/files/api/v1/files/new%20folder').as('addToFavorites')

		// see navigation has no entry
		cy.get('[data-cy-files-navigation-item="favorites"]')
			.should('be.visible')
			.contains('new folder')
			.should('not.exist')

		// toggle favorite
		triggerActionForFile('new folder', 'favorite')
		cy.wait('@addToFavorites')

		// See in navigation
		cy.get('[data-cy-files-navigation-item="favorites"]')
			.should('be.visible')
			.contains('new folder')
			.should('exist')

		// toggle favorite
		triggerActionForFile('new folder', 'favorite')
		cy.wait('@addToFavorites')

		// See no longer in navigation
		cy.get('[data-cy-files-navigation-item="favorites"]')
			.should('be.visible')
			.contains('new folder')
			.should('not.exist')
	})

	it('Mark file as favorite using the sidebar', () => {
		// See file exists
		getRowForFile('new folder')
			.should('exist')
		// see navigation has no entry
		cy.get('[data-cy-files-navigation-item="favorites"]')
			.should('be.visible')
			.contains('new folder')
			.should('not.exist')

		cy.intercept('PROPPATCH', '**/remote.php/dav/files/*/new%20folder').as('addToFavorites')
		// open sidebar
		triggerActionForFile('new folder', 'details')
		// open actions
		cy.get('[data-cy-sidebar]')
			.findByRole('button', { name: 'Actions' })
			.click()
		// trigger menu button
		cy.findAllByRole('menu')
			.findByRole('menuitem', { name: 'Add to favorites' })
			.should('be.visible')
			.click()
		cy.wait('@addToFavorites')

		// See favorites star
		getRowForFile('new folder')
			.findByRole('img', { name: 'Favorite' })
			.should('be.visible')

		// See folder in navigation
		cy.get('[data-cy-files-navigation-item="favorites"]')
			.should('be.visible')
			.contains('new folder')
			.should('exist')
	})
})
