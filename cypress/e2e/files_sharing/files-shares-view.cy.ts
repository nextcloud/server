/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { createShare } from './FilesSharingUtils.ts'
import { getRowForFile } from '../files/FilesUtils.ts'

describe('files_sharing: Files view', { testIsolation: true }, () => {
	let user: User
	let sharee: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user
		})
		cy.createRandomUser().then(($user) => {
			sharee = $user
		})
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/46108
	 */
	it('opens a shared folder when clicking on it', () => {
		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/file')
		cy.login(user)
		cy.visit('/apps/files')

		// share the folder
		createShare('folder', sharee.userId, { read: true, download: true })
		// visit the own shares
		cy.visit('/apps/files/sharingout')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		// click on the folder should open it in files
		getRowForFile('folder').findByRole('button', { name: /open in files/i }).click()
		// See the URL has changed
		cy.url().should('match', /apps\/files\/files\/.+dir=\/folder/)
		// Content of the shared folder
		getRowForFile('file').should('be.visible')

		cy.logout()
		// Now for the sharee
		cy.login(sharee)

		// visit shared files view
		cy.visit('/apps/files/sharingin')
		// see the shared folder
		getRowForFile('folder').should('be.visible')
		// click on the folder should open it in files
		getRowForFile('folder').findByRole('button', { name: /open in files/i }).click()
		// See the URL has changed
		cy.url().should('match', /apps\/files\/files\/.+dir=\/folder/)
		// Content of the shared folder
		getRowForFile('file').should('be.visible')
	})
})
