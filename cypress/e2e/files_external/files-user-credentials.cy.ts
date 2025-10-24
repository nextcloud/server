/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getInlineActionEntryForFile, getRowForFile, navigateToFolder, triggerInlineActionForFile } from '../files/FilesUtils.ts'
import { handlePasswordConfirmation } from '../settings/usersUtils.ts'
import { AuthBackend, createStorageWithConfig, StorageBackend } from './StorageUtils.ts'

const ACTION_CREDENTIALS_EXTERNAL_STORAGE = 'credentials-external-storage'

describe('Files user credentials', { testIsolation: true }, () => {
	let user1: User
	let user2: User
	let storageUser: User

	before(() => {
		cy.runOccCommand('app:enable files_external')

		// Create some users
		cy.createRandomUser().then((user) => {
			user1 = user
		})
		cy.createRandomUser().then((user) => {
			user2 = user
		})

		// This user will hold the webdav storage
		cy.createRandomUser().then((user) => {
			storageUser = user
			cy.uploadFile(user, 'image.jpg')
		})
	})

	after(() => {
		// Cleanup global storages
		cy.runOccCommand('files_external:list --output=json').then(({ stdout }) => {
			const list = JSON.parse(stdout)
			list.forEach((storage) => cy.runOccCommand(`files_external:delete --yes ${storage.mount_id}`), { failOnNonZeroExit: false })
		})

		cy.runOccCommand('app:disable files_external')
	})

	it('Create a user storage with user credentials', () => {
		// Its not the public server address but the address so the server itself can connect to it
		const base = 'http://localhost'
		const host = `${base}/remote.php/dav/files/${storageUser.userId}`
		createStorageWithConfig(storageUser.userId, StorageBackend.DAV, AuthBackend.UserProvided, { host, secure: 'false' })

		cy.login(user1)
		cy.visit('/apps/files/extstoragemounts')
		getRowForFile(storageUser.userId).should('be.visible')

		cy.intercept('PUT', '**/apps/files_external/userglobalstorages/*').as('setCredentials')

		triggerInlineActionForFile(storageUser.userId, ACTION_CREDENTIALS_EXTERNAL_STORAGE)

		// See credentials dialog
		cy.findByRole('dialog', { name: 'Storage credentials' }).as('storageDialog')
		cy.get('@storageDialog').should('be.visible')
		cy.get('@storageDialog').findByRole('textbox', { name: 'Login' }).type(storageUser.userId)
		cy.get('@storageDialog').get('input[type="password"]').type(storageUser.password)
		cy.get('@storageDialog').get('button').contains('Confirm').click()
		cy.get('@storageDialog').should('not.exist')

		// Storage dialog now closed, the user auth dialog should be visible
		cy.findByRole('dialog', { name: 'Authentication required' }).as('authDialog')
		cy.get('@authDialog').should('be.visible')
		handlePasswordConfirmation(user1.password)

		// Wait for the credentials to be set
		cy.wait('@setCredentials')

		// Auth dialog should be closed and the set credentials button should be gone
		cy.get('@authDialog').should('not.exist', { timeout: 2000 })

		getInlineActionEntryForFile(storageUser.userId, ACTION_CREDENTIALS_EXTERNAL_STORAGE)
			.should('not.exist')

		// Finally, the storage should be accessible
		cy.visit('/apps/files')
		navigateToFolder(storageUser.userId)
		getRowForFile('image.jpg').should('be.visible')
	})

	it('Create a user storage with GLOBAL user credentials', () => {
		// Its not the public server address but the address so the server itself can connect to it
		const base = 'http://localhost'
		const host = `${base}/remote.php/dav/files/${storageUser.userId}`
		createStorageWithConfig('storage1', StorageBackend.DAV, AuthBackend.UserGlobalAuth, { host, secure: 'false' })

		cy.login(user2)
		cy.visit('/apps/files/extstoragemounts')
		getRowForFile('storage1').should('be.visible')

		cy.intercept('PUT', '**/apps/files_external/userglobalstorages/*').as('setCredentials')

		triggerInlineActionForFile('storage1', ACTION_CREDENTIALS_EXTERNAL_STORAGE)

		// See credentials dialog
		cy.findByRole('dialog', { name: 'Storage credentials' }).as('storageDialog')
		cy.get('@storageDialog').should('be.visible')
		cy.get('@storageDialog').findByRole('textbox', { name: 'Login' }).type(storageUser.userId)
		cy.get('@storageDialog').get('input[type="password"]').type(storageUser.password)
		cy.get('@storageDialog').get('button').contains('Confirm').click()
		cy.get('@storageDialog').should('not.exist')

		// Storage dialog now closed, the user auth dialog should be visible
		cy.findByRole('dialog', { name: 'Authentication required' }).as('authDialog')
		cy.get('@authDialog').should('be.visible')
		handlePasswordConfirmation(user2.password)

		// Wait for the credentials to be set
		cy.wait('@setCredentials')

		// Auth dialog should be closed and the set credentials button should be gone
		cy.get('@authDialog').should('not.exist', { timeout: 2000 })
		getInlineActionEntryForFile('storage1', ACTION_CREDENTIALS_EXTERNAL_STORAGE).should('not.exist')

		// Finally, the storage should be accessible
		cy.visit('/apps/files')
		navigateToFolder('storage1')
		getRowForFile('image.jpg').should('be.visible')
	})

	it('Create another user storage while reusing GLOBAL user credentials', () => {
		// Its not the public server address but the address so the server itself can connect to it
		const base = 'http://localhost'
		const host = `${base}/remote.php/dav/files/${storageUser.userId}`
		createStorageWithConfig('storage2', StorageBackend.DAV, AuthBackend.UserGlobalAuth, { host, secure: 'false' })

		cy.login(user2)
		cy.visit('/apps/files/extstoragemounts')
		getRowForFile('storage2').should('be.visible')

		// Since we already have set the credentials, the action should not be present
		getInlineActionEntryForFile('storage1', ACTION_CREDENTIALS_EXTERNAL_STORAGE).should('not.exist')
		getInlineActionEntryForFile('storage2', ACTION_CREDENTIALS_EXTERNAL_STORAGE).should('not.exist')

		// Finally, the storage should be accessible
		cy.visit('/apps/files')
		navigateToFolder('storage2')
		getRowForFile('image.jpg').should('be.visible')
	})
})
