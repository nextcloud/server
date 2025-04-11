/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { assertVersionContent, doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils'
import type { User } from '@nextcloud/cypress'
import { getRowForFile } from '../files/FilesUtils'

describe('Versions download', () => {
	let randomFileName = ''
	let user: User

	before(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'

		cy.createRandomUser()
			.then((_user) => {
				user = _user
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Download versions and assert their content', () => {
		assertVersionContent(0, 'v3')
		assertVersionContent(1, 'v2')
		assertVersionContent(2, 'v1')
	})

	context('Download versions of shared file', () => {
		it('Works with download permission', () => {
			setupTestSharedFileFromUser(user, randomFileName, { download: true })
			openVersionsPanel(randomFileName)

			assertVersionContent(0, 'v3')
			assertVersionContent(1, 'v2')
			assertVersionContent(2, 'v1')
		})

		it('Does not show action without download permission', () => {
			setupTestSharedFileFromUser(user, randomFileName, { download: false })
			openVersionsPanel(randomFileName)

			cy.get('[data-files-versions-version]').eq(0).find('.action-item__menutoggle').should('not.exist')
			cy.get('[data-files-versions-version]').eq(0).get('[data-cy-version-action="download"]').should('not.exist')

			doesNotHaveAction(1, 'download')
			doesNotHaveAction(2, 'download')
		})

		it('Does not work without download permission through direct API access', () => {
			let fileId: string|undefined
			let versionId: string|undefined

			setupTestSharedFileFromUser(user, randomFileName, { download: false })
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
