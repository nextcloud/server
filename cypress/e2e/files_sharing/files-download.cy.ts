/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/e2e-test-server/cypress'

import {
	getActionButtonForFile,
	getActionEntryForFile,
	getRowForFile,
} from '../files/FilesUtils.ts'
import { createShare } from './FilesSharingUtils.ts'

describe('files_sharing: Download forbidden', { testIsolation: true }, () => {
	let user: User
	let sharee: User

	beforeEach(() => {
		cy.runOccCommand('config:app:set --value yes core shareapi_allow_view_without_download')
		cy.createRandomUser().then(($user) => {
			user = $user
		})
		cy.createRandomUser().then(($user) => {
			sharee = $user
		})
	})

	after(() => {
		cy.runOccCommand('config:app:delete core shareapi_allow_view_without_download')
	})

	it('cannot download a folder if disabled', () => {
		// share the folder
		cy.mkdir(user, '/folder')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('folder', sharee.userId, { read: true, download: false })
		cy.logout()

		// Now for the sharee
		cy.login(sharee)

		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getActionButtonForFile('folder')
			.should('be.visible')
			// open the action menu
			.click({ force: true })
		// see no download action
		getActionEntryForFile('folder', 'download')
			.should('not.exist')

		// Disable view without download option
		cy.runOccCommand('config:app:set --value no core shareapi_allow_view_without_download')

		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		getActionButtonForFile('folder')
			.should('be.visible')
			// open the action menu
			.click({ force: true })
		getActionEntryForFile('folder', 'download').should('not.exist')
	})

	it('cannot download a file if disabled', () => {
		// share the folder
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
		createShare('file.txt', sharee.userId, { read: true, download: false })
		cy.logout()

		// Now for the sharee
		cy.login(sharee)

		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getActionButtonForFile('file.txt')
			.should('be.visible')
			// open the action menu
			.click({ force: true })
		// see no download action
		getActionEntryForFile('file.txt', 'download')
			.should('not.exist')

		// Disable view without download option
		cy.runOccCommand('config:app:set --value no core shareapi_allow_view_without_download')

		// visit shared files view
		cy.visit('/apps/files')
		// see the shared folder
		getRowForFile('file.txt').should('be.visible')
		getActionButtonForFile('file.txt')
			.should('be.visible')
			// open the action menu
			.click({ force: true })
		getActionEntryForFile('file.txt', 'download').should('not.exist')
	})
})
