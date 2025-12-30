/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomBytes } from 'crypto'
import { getRowForFile } from '../files/FilesUtils.ts'
import { addTagToFile } from './utils.ts'

describe('Systemtags: Files view', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('See first assigned tag in the file list', () => {
		const tag = randomBytes(8).toString('base64')
		addTagToFile('folder', tag)

		// open the tags view
		cy.visit('/apps/files/tags').then(() => {
			// see the tag
			getRowForFile('folder').should('not.exist')
			getRowForFile('file.txt').should('not.exist')
			cy.findByRole('cell', { name: tag })
				.should('be.visible')
				.click()

			// see that the tag has its content
			getRowForFile('folder').should('be.visible')
			getRowForFile('file.txt').should('not.exist')
		})
	})
})
