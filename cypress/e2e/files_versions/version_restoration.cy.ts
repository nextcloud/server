/**
 * @copyright Copyright (c) 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { User } from '@nextcloud/cypress'
import { assertVersionContent, doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, restoreVersion, uploadThreeVersions } from './filesVersionsUtils'
import { getRowForFile } from '../files/FilesUtils'

describe('Versions restoration', () => {
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

	it('Current version does not have restore action', () => {
		doesNotHaveAction(0, 'restore')
	})

	it('Restores initial version', () => {
		restoreVersion(2)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})
	})

	it('Downloads versions and assert there content', () => {
		assertVersionContent(randomFileName, 0, 'v1')
		assertVersionContent(randomFileName, 1, 'v3')
		assertVersionContent(randomFileName, 2, 'v2')
	})

	context('Restore versions of shared file', () => {
		it('Works with update permission', () => {
			setupTestSharedFileFromUser(user, randomFileName, { update: true })
			openVersionsPanel(randomFileName)

			it('Restores initial version', () => {
				restoreVersion(2)
				cy.get('#tab-version_vue').within(() => {
					cy.get('[data-files-versions-version]').should('have.length', 3)
					cy.get('[data-files-versions-version]').eq(0).contains('Current version')
					cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
				})
			})

			it('Downloads versions and assert there content', () => {
				assertVersionContent(randomFileName, 0, 'v1')
				assertVersionContent(randomFileName, 1, 'v3')
				assertVersionContent(randomFileName, 2, 'v2')
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
			let hostname: string
			let fileId: string|undefined
			let versionId: string|undefined

			setupTestSharedFileFromUser(user, randomFileName, { update: false })
				.then(recipient => {
					openVersionsPanel(randomFileName)

					cy.url().then(url => { hostname = new URL(url).hostname })
					getRowForFile(randomFileName).invoke('attr', 'data-cy-files-list-row-fileid').then(_fileId => { fileId = _fileId })
					cy.get('[data-files-versions-version]').eq(1).invoke('attr', 'data-files-versions-version').then(_versionId => { versionId = _versionId })

					cy.then(() => {
						cy.logout()
						cy.request({
							method: 'MOVE',
							auth: { user: recipient.userId, pass: recipient.password },
							headers: {
								cookie: '',
								Destination: 'https://nextcloud_server1.test/remote.php/dav/versions/admin/restore/target',
							},
							url: `http://${hostname}/remote.php/dav/versions/${recipient.userId}/versions/${fileId}/${versionId}`,
							failOnStatusCode: false,
						})
							.then(({ status }) => {
								expect(status).to.equal(403)
							})
					})
				})
		})
	})
})
