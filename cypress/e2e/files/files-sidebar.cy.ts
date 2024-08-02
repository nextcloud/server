/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder, triggerActionForFile } from './FilesUtils'
import { assertNotExistOrNotVisible } from '../settings/usersUtils'

describe('Files: Sidebar', { testIsolation: true }, () => {
	let user: User
	let fileId: number = 0

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file').then((response) => {
			fileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
		})
		cy.login(user)
	}))

	it('opens the sidebar', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('heading', { name: 'file' })
			.should('be.visible')
	})

	it('changes the current fileid', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[data-cy-sidebar]').should('be.visible')
		cy.url().should('contain', `apps/files/files/${fileId}`)
	})

	it('changes the sidebar content on other file', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('heading', { name: 'file' })
			.should('be.visible')

		triggerActionForFile('folder', 'details')
		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('heading', { name: 'folder' })
			.should('be.visible')
	})

	it('closes the sidebar on navigation', () => {
		cy.visit('/apps/files')

		getRowForFile('file').should('be.visible')
		getRowForFile('folder').should('be.visible')

		// open the sidebar
		triggerActionForFile('file', 'details')
		// validate it is open
		cy.get('[data-cy-sidebar]')
			.should('be.visible')

		// if we navigate to the folder
		navigateToFolder('folder')
		// the sidebar should not be visible anymore
		cy.get('[data-cy-sidebar]')
			.should(assertNotExistOrNotVisible)
	})

	it('closes the sidebar on delete', () => {
		cy.intercept('DELETE', `**/remote.php/dav/files/${user.userId}/file`).as('deleteFile')
		// visit the files app
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')
		// open the sidebar
		triggerActionForFile('file', 'details')
		// validate it is open
		cy.get('[data-cy-sidebar]').should('be.visible')
		// delete the file
		triggerActionForFile('file', 'delete')
		cy.wait('@deleteFile', { timeout: 10000 })
		// see the sidebar is closed
		cy.get('[data-cy-sidebar]')
			.should(assertNotExistOrNotVisible)
	})

	it('changes the fileid on delete', () => {
		cy.intercept('DELETE', `**/remote.php/dav/files/${user.userId}/folder/other`).as('deleteFile')

		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/other').then((response) => {
			const otherFileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
			cy.login(user)
			cy.visit('/apps/files')

			getRowForFile('folder').should('be.visible')
			navigateToFolder('folder')
			getRowForFile('other').should('be.visible')

			// open the sidebar
			triggerActionForFile('other', 'details')
			// validate it is open
			cy.get('[data-cy-sidebar]').should('be.visible')
			cy.url().should('contain', `apps/files/files/${otherFileId}`)

			triggerActionForFile('other', 'delete')
			cy.wait('@deleteFile')

			cy.get('[data-cy-sidebar]').should('not.exist')
			// Ensure the URL is changed
			cy.url().should('not.contain', `apps/files/files/${otherFileId}`)
		})
	})
})
