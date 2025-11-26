/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { getRowForFile } from '../files/FilesUtils.ts'
import { doesNotHaveAction, nameVersion, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions naming', () => {
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

	it('Names the versions', () => {
		nameVersion(2, 'v1')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(2).contains('v1')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})

		nameVersion(1, 'v2')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(1).contains('v2')
		})

		nameVersion(0, 'v3')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(0).contains('v3 (Current version)')
		})
	})

	context('Name versions of shared file', () => {
		context('with edit permission', () => {
			before(() => {
				setupTestSharedFileFromUser(user, randomFileName, { update: true })
				openVersionsPanel(randomFileName)
			})

			it('Names the versions', () => {
				nameVersion(2, 'v1 - shared')
				cy.get('#tab-files_versions').within(() => {
					cy.get('[data-files-versions-version]').eq(2).contains('v1 - shared')
					cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
				})

				nameVersion(1, 'v2 - shared')
				cy.get('#tab-files_versions').within(() => {
					cy.get('[data-files-versions-version]').eq(1).contains('v2 - shared')
				})

				nameVersion(0, 'v3 - shared')
				cy.get('#tab-files_versions').within(() => {
					cy.get('[data-files-versions-version]').eq(0).contains('v3 - shared (Current version)')
				})
			})
		})

		context('without edit permission', () => {
			let recipient: User

			beforeEach(() => {
				setupTestSharedFileFromUser(user, randomFileName, { update: false })
					.then(($recipient) => {
						recipient = $recipient
						openVersionsPanel(randomFileName)
					})
			})

			it('Does not show action', () => {
				cy.get('[data-files-versions-version]').eq(0).find('.action-item__menutoggle').should('not.exist')
				cy.get('[data-files-versions-version]').eq(0).get('[data-cy-version-action="label"]').should('not.exist')

				doesNotHaveAction(1, 'label')
				doesNotHaveAction(2, 'label')
			})

			it('Does not work without update permission through direct API access', () => {
				let fileId: string | undefined
				let versionId: string | undefined

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
					const base = Cypress.config('baseUrl')!.replace(/index\.php\/?/, '')
					return cy.request({
						method: 'PROPPATCH',
						url: `${base}/remote.php/dav/versions/${recipient.userId}/versions/${fileId}/${versionId}`,
						auth: { user: recipient.userId, pass: recipient.password },
						headers: {
							cookie: '',
						},
						body: `<?xml version="1.0"?>
							<d:propertyupdate xmlns:d="DAV:"
								xmlns:oc="http://owncloud.org/ns"
								xmlns:nc="http://nextcloud.org/ns"
								xmlns:ocs="http://open-collaboration-services.org/ns">
							<d:set>
								<d:prop>
									<nc:version-label>not authorized labeling</nc:version-label>
								</d:prop>
							</d:set>
							</d:propertyupdate>`,
						failOnStatusCode: false,
					})
				}).then(({ status }) => {
					expect(status).to.equal(403)
				})
			})
		})
	})
})
