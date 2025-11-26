/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { getRowForFile, navigateToFolder } from '../files/FilesUtils.ts'
import { deleteVersion, doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions restoration', () => {
	const folderName = 'shared_folder'
	const randomFileName = randomString(10) + '.txt'
	const randomFilePath = `/${folderName}/${randomFileName}`
	let user: User
	let versionCount = 0

	before(() => {
		cy.createRandomUser()
			.then((_user) => {
				user = _user
				cy.mkdir(user, `/${folderName}`)
				uploadThreeVersions(user, randomFilePath)
				uploadThreeVersions(user, randomFilePath)
				versionCount = 6
				cy.login(user)
				cy.visit('/apps/files')
				navigateToFolder(folderName)
				openVersionsPanel(randomFilePath)
			})
	})

	it('Delete initial version', () => {
		cy.get('[data-files-versions-version]').should('have.length', versionCount)
		deleteVersion(2)
		versionCount--
		cy.get('[data-files-versions-version]').should('have.length', versionCount)
	})

	context('Delete versions of shared file', () => {
		it('Works with delete permission', () => {
			setupTestSharedFileFromUser(user, folderName, { delete: true })
			navigateToFolder(folderName)
			openVersionsPanel(randomFilePath)

			cy.get('[data-files-versions-version]').should('have.length', versionCount)
			deleteVersion(2)
			versionCount--
			cy.get('[data-files-versions-version]').should('have.length', versionCount)
		})

		it('Does not work without delete permission', () => {
			setupTestSharedFileFromUser(user, folderName, { delete: false })
			navigateToFolder(folderName)
			openVersionsPanel(randomFilePath)

			doesNotHaveAction(0, 'delete')
			doesNotHaveAction(1, 'delete')
			doesNotHaveAction(2, 'delete')
		})

		it('Does not work without delete permission through direct API access', () => {
			let fileId: string | undefined
			let versionId: string | undefined

			setupTestSharedFileFromUser(user, folderName, { delete: false })
				.then((recipient) => {
					navigateToFolder(folderName)
					openVersionsPanel(randomFilePath)

					getRowForFile(randomFileName)
						.should('be.visible')
						.invoke('attr', 'data-cy-files-list-row-fileid')
						.then(($fileId) => { fileId = $fileId })

					cy.get('[data-files-versions-version]')
						.eq(1)
						.invoke('attr', 'data-files-versions-version')
						.then(($versionId) => { versionId = $versionId })

					cy.logout()
					cy.then(() => {
						const base = Cypress.config('baseUrl')!.replace(/\/index\.php\/?$/, '')
						return cy.request({
							method: 'DELETE',
							url: `${base}/remote.php/dav/versions/${recipient.userId}/versions/${fileId}/${versionId}`,
							auth: { user: recipient.userId, pass: recipient.password },
							headers: {
								cookie: '',
							},
							failOnStatusCode: false,
						})
					}).then(({ status }) => {
						expect(status).to.equal(403)
					})
				})
		})
	})
})
