/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'
import { navigateToFolder, triggerActionForFile } from '../files/FilesUtils.ts'

describe('Versions on shares', () => {
	const randomSharedFolderName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
	const randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
	const randomFilePath = `${randomSharedFolderName}/${randomFileName}`
	let alice: User
	let bob: User

	before(() => {
		cy.createRandomUser()
			.then((user) => {
				alice = user
			})
			.then(() => {
				cy.mkdir(alice, `/${randomSharedFolderName}`)
				return setupTestSharedFileFromUser(alice, randomSharedFolderName, {})
			})
			.then((user) => { bob = user })
			.then(() => uploadThreeVersions(alice, randomFilePath))
	})

	it('See sharees display name as author', () => {
		cy.login(bob)
		cy.visit('/apps/files')

		navigateToFolder(randomSharedFolderName)

		triggerActionForFile(randomFileName, 'details')
		cy.findByRole('tab', { name: 'Versions' }).click()

		cy.findByRole('tabpanel', { name: 'Versions' })
			.findByRole('list', { name: 'File versions' })
			.findAllByRole('listitem')
			.first()
			.find('[data-cy-files-version-author-name]')
			.should('be.visible')
			.and('contain.text', alice.userId)
	})
})
