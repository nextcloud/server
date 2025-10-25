/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { getRowForFile } from '../files/FilesUtils.ts'
import { assertVersionContent, doesNotHaveAction, openVersionsPanel, restoreVersion, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions restoration', () => {
	let randomFileName = ''
	let user: User

	before(() => {
		randomFileName = randomString(10) + '.txt'

		cy.createRandomUser()
			.then((_user) => {
				user = _user
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Current version does not have restore action', () => {
		doesNotHaveAction(0, 'restore')
	})

	it('Restores initial version', () => {
		restoreVersion(2)

		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})
	})

	it('Downloads versions and assert there content', () => {
		assertVersionContent(0, 'v1')
		assertVersionContent(1, 'v3')
		assertVersionContent(2, 'v2')
	})

	context('Restore versions of shared file', () => {
		it('Works with update permission', () => {
			setupTestSharedFileFromUser(user, randomFileName, { update: true })
			openVersionsPanel(randomFileName)

			it('Restores initial version', () => {
				restoreVersion(2)
				cy.get('#tab-files_versions').within(() => {
					cy.get('[data-files-versions-version]').should('have.length', 3)
					cy.get('[data-files-versions-version]').eq(0).contains('Current version')
					cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
				})
			})

			it('Downloads versions and assert there content', () => {
				assertVersionContent(0, 'v1')
				assertVersionContent(1, 'v3')
				assertVersionContent(2, 'v2')
			})
		})

		it('Does not show action without delete permission', () => {
			setupTestSharedFileFromUser(user, randomFileName, { update: false })
			openVersionsPanel(randomFileName)

			cy.get('[data-files-versions-version]').eq(0).find('.action-item__menutoggle').should('not.exist')
			cy.get('[data-files-versions-version]').eq(0).get('[data-cy-version-action="restore"]').should('not.exist')

			doesNotHaveAction(1, 'restore')
			doesNotHaveAction(2, 'restore')
		})

		it('Does not work without update permission through direct API access', () => {
			let fileId: string | undefined
			let versionId: string | undefined

			setupTestSharedFileFromUser(user, randomFileName, { update: false })
				.then((recipient) => {
					openVersionsPanel(randomFileName)

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
							method: 'MOVE',
							url: `${base}/remote.php/dav/versions/${recipient.userId}/versions/${fileId}/${versionId}`,
							auth: { user: recipient.userId, pass: recipient.password },
							headers: {
								cookie: '',
								Destination: `${base}}/remote.php/dav/versions/${recipient.userId}/restore/target`,
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
