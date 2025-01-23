/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { createShare, openSharingPanel } from './FilesSharingUtils.ts'
import { navigateToFolder } from '../files/FilesUtils.ts'

describe('files_sharing: Note to recipient', { testIsolation: true }, () => {
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

	it('displays the note to the sharee', () => {
		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/file')
		cy.login(user)
		cy.visit('/apps/files')

		// share the folder
		createShare('folder', sharee.userId, { read: true, download: true, note: 'Hello, this is the note.' })

		cy.logout()
		// Now for the sharee
		cy.login(sharee)

		// visit shared files view
		cy.visit('/apps/files')
		navigateToFolder('folder')
		cy.get('.note-to-recipient')
			.should('be.visible')
			.and('contain.text', 'Hello, this is the note.')
	})

	/**
	 * Regression test for https://github.com/nextcloud/server/issues/46188
	 */
	it('shows an existing note when editing a share', () => {
		cy.mkdir(user, '/folder')
		cy.login(user)
		cy.visit('/apps/files')

		// share the folder
		createShare('folder', sharee.userId, { read: true, download: true, note: 'Hello, this is the note.' })

		// reload just to be sure
		cy.reload()

		// open the sharing tab
		openSharingPanel('folder')

		cy.get('[data-cy-sidebar]').within(() => {
			// Open the share
			cy.get('[data-cy-files-sharing-share-actions]').first().click()
			// Open the custom settings
			cy.get('[data-cy-files-sharing-share-permissions-bundle="custom"]').click()

			cy.findByRole('checkbox', { name: /note to recipient/i })
				.and('be.checked')
			cy.findByRole('textbox', { name: /note to recipient/i })
				.should('be.visible')
				.and('have.value', 'Hello, this is the note.')
		})
	})
})
