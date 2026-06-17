/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { navigateToFolder } from '../files/FilesUtils.ts'
import { deleteVersion, doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions deletion', () => {
	const folderName = 'shared_folder'
	const randomFileName = randomString(10) + '.txt'
	const randomFilePath = `/${folderName}/${randomFileName}`
	let user: User
	let versionCount = 0

	beforeEach(() => {
		cy.createRandomUser()
			.then((_user) => {
				user = _user
				cy.mkdir(user, `/${folderName}`)
				uploadThreeVersions(user, randomFilePath)
				versionCount = 3
				cy.login(user)
				cy.visit('/apps/files')
			})
	})

	it('Delete initial version', () => {
		navigateToFolder(folderName)
		openVersionsPanel(randomFilePath)

		cy.get('[data-files-versions-version]')
			.should('have.length', versionCount)
		deleteVersion(--versionCount)
		cy.get('[data-files-versions-version]')
			.should('have.length', versionCount)
	})

	it('Delete versions of shared file with delete permission', () => {
		setupTestSharedFileFromUser(user, folderName, { delete: true })
		navigateToFolder(folderName)
		openVersionsPanel(randomFilePath)

		cy.get('[data-files-versions-version]').should('have.length', versionCount)
		deleteVersion(--versionCount)
		cy.get('[data-files-versions-version]').should('have.length', versionCount)
	})

	it('Delete versions of shared file without delete permission', () => {
		setupTestSharedFileFromUser(user, folderName, { delete: false })
		navigateToFolder(folderName)
		openVersionsPanel(randomFilePath)

		doesNotHaveAction(0, 'delete')
		doesNotHaveAction(1, 'delete')
		doesNotHaveAction(2, 'delete')
	})
})
