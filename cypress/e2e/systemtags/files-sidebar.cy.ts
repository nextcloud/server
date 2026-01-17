/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomBytes } from 'crypto'
import { getRowForFile, triggerActionForFile } from '../files/FilesUtils.ts'
import { createNewTagInDialog } from './utils.ts'

describe('Systemtags: Files sidebar integration', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
	}))

	it('Can assign tags using the sidebar', () => {
		const tag = randomBytes(8).toString('base64')
		cy.visit('/apps/files')

		getRowForFile('file.txt').should('be.visible')
		triggerActionForFile('file.txt', 'details')

		cy.get('[data-cy-sidebar]')
			.should('be.visible')
			.findByRole('button', { name: 'Actions' })
			.should('be.visible')
			.click()

		cy.findByRole('menuitem', { name: 'Add tags' })
			.click()

		createNewTagInDialog(tag)
	})
})
