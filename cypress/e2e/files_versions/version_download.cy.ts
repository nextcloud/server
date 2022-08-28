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
			let hostname: string
			let fileId: string|undefined
			let versionId: string|undefined

			setupTestSharedFileFromUser(user, randomFileName, { download: false })
				.then(recipient => {
					openVersionsPanel(randomFileName)

					cy.url().then(url => { hostname = new URL(url).hostname })
					getRowForFile(randomFileName).invoke('attr', 'data-cy-files-list-row-fileid').then(_fileId => { fileId = _fileId })
					cy.get('[data-files-versions-version]').eq(1).invoke('attr', 'data-files-versions-version').then(_versionId => { versionId = _versionId })

					cy.then(() => {
						cy.logout()
						cy.request({
							auth: { user: recipient.userId, pass: recipient.password },
							headers: {
								cookie: '',
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
