/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, navigateToFolder, triggerActionForFile } from './FilesUtils'

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

		cy.get('[data-cy-sidebar]').should('be.visible')
	})

	it('changes the current fileid', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[data-cy-sidebar]').should('be.visible')
		cy.url().should('contain', `apps/files/files/${fileId}`)
	})

	it('closes the sidebar on delete', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		// open the sidebar
		triggerActionForFile('file', 'details')
		// validate it is open
		cy.get('[data-cy-sidebar]').should('be.visible')

		triggerActionForFile('file', 'delete')
		cy.get('[data-cy-sidebar]').should('not.exist')
	})

	it('changes the fileid on delete', () => {
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
			cy.get('[data-cy-sidebar]').should('not.exist')
			// Ensure the URL is changed
			cy.url().should('not.contain', `apps/files/files/${otherFileId}`)
		})
	})
})
