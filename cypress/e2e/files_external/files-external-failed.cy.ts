/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getRowForFile } from '../files/FilesUtils.ts'
import { AuthBackend, createStorageWithConfig, StorageBackend } from './StorageUtils.ts'

describe('Files user credentials', { testIsolation: true }, () => {
	let currentUser: User

	beforeEach(() => {
	})

	before(() => {
		cy.runOccCommand('app:enable files_external')
		cy.createRandomUser().then((user) => {
			currentUser = user
		})
	})

	afterEach(() => {
		// Cleanup global storages
		cy.runOccCommand('files_external:list --output=json').then(({ stdout }) => {
			const list = JSON.parse(stdout)
			list.forEach((storage) => cy.runOccCommand(`files_external:delete --yes ${storage.mount_id}`), { failOnNonZeroExit: false })
		})
	})

	after(() => {
		cy.runOccCommand('app:disable files_external')
	})

	it('Create a failed user storage with invalid url', () => {
		const url = 'http://cloud.domain.com/remote.php/dav/files/abcdef123456'
		createStorageWithConfig('Storage1', StorageBackend.DAV, AuthBackend.LoginCredentials, { host: url.replace('index.php/', ''), secure: 'false' }).then((id) => {
			cy.runOccCommand(`files_external:verify ${id}`)
		})

		cy.login(currentUser)
		cy.visit('/apps/files')

		// Ensure the row is visible and marked as unavailable
		getRowForFile('Storage1').as('row').should('be.visible')
		cy.get('@row').find('[data-cy-files-list-row-name-link]')
			.should('have.attr', 'title', 'This node is unavailable')

		// Ensure clicking on the location does not open the folder
		cy.location().then((loc) => {
			cy.get('@row').find('[data-cy-files-list-row-name-link]').click()
			cy.location('href').should('eq', loc.href)
		})
	})

	it('Create a failed user storage with invalid login credentials', () => {
		const url = 'http://cloud.domain.com/remote.php/dav/files/abcdef123456'
		createStorageWithConfig('Storage2', StorageBackend.DAV, AuthBackend.Password, {
			host: url.replace('index.php/', ''),
			user: 'invaliduser',
			password: 'invalidpassword',
			secure: 'false',
		}).then((id) => {
			cy.runOccCommand(`files_external:verify ${id}`)
		})

		cy.login(currentUser)
		cy.visit('/apps/files')

		// Ensure the row is visible and marked as unavailable
		getRowForFile('Storage2').as('row').should('be.visible')
		cy.get('@row').find('[data-cy-files-list-row-name-link]')
			.should('have.attr', 'title', 'This node is unavailable')

		// Ensure clicking on the location does not open the folder
		cy.location().then((loc) => {
			cy.get('@row').find('[data-cy-files-list-row-name-link]').click()
			cy.location('href').should('eq', loc.href)
		})
	})
})
