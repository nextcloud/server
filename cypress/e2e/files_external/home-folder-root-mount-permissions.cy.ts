/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { AuthBackend, createStorageWithConfig, deleteAllExternalStorages, setStorageMountOptions, StorageBackend } from './StorageUtils.ts'

describe('Home folder root mount permissions', { testIsolation: true }, () => {
	let user1: User

	before(() => {
		cy.runOccCommand('app:enable files_external')
		cy.createRandomUser().then((user) => {
			user1 = user
		})
	})

	after(() => {
		deleteAllExternalStorages()
		cy.runOccCommand('app:disable files_external')
	})

	it('Does not show write actions on read-only storage mounted at the root of the user\'s home folder', () => {
		cy.login(user1)
		cy.visit('/apps/files/')
		cy.runOccCommand('config:app:get files overwrites_home_folders --default-value=[]')
			.then(({ stdout }) => assert.equal(stdout.trim(), '[]'))

		cy.get('[data-cy-upload-picker=""]').should('exist')

		createStorageWithConfig('/', StorageBackend.LOCAL, AuthBackend.Null, { datadir: '/tmp' })
			.then((id) => setStorageMountOptions(id, { readonly: true }))
		// HACK: somehow, we need to create an external folder targeting a subpath for the previous one to show.
		createStorageWithConfig('/a', StorageBackend.LOCAL, AuthBackend.Null, { datadir: '/tmp' })
		cy.visit('/apps/files/')
		cy.visit('/apps/files/')
		cy.runOccCommand('config:app:get files overwrites_home_folders')
			.then(({ stdout }) => assert.equal(stdout.trim(), '["files_external"]'))
		cy.get('[data-cy-upload-picker=""]').should('not.exist')

		deleteAllExternalStorages()
		cy.visit('/apps/files/')
		cy.runOccCommand('config:app:get files overwrites_home_folders')
			.then(({ stdout }) => assert.equal(stdout.trim(), '[]'))
		cy.get('[data-cy-upload-picker=""]').should('exist')
	})
})
