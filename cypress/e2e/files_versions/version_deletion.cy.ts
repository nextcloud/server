/**
 * @copyright Copyright (c) 2024 Louis Chmn <louis@chmn.me>
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
import { doesNotHaveAction, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions, deleteVersion } from './filesVersionsUtils'
import { navigateToFolder, getRowForFile } from '../files/FilesUtils'

describe('Versions restoration', () => {
	const folderName = 'shared_folder'
	const randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
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
			let hostname: string
			let fileId: string|undefined
			let versionId: string|undefined

			setupTestSharedFileFromUser(user, folderName, { delete: false })
				.then(recipient => {
					navigateToFolder(folderName)
					openVersionsPanel(randomFilePath)

					cy.url().then(url => { hostname = new URL(url).hostname })
					getRowForFile(randomFileName).invoke('attr', 'data-cy-files-list-row-fileid').then(_fileId => { fileId = _fileId })
					cy.get('[data-files-versions-version]').eq(1).invoke('attr', 'data-files-versions-version').then(_versionId => { versionId = _versionId })

					cy.then(() => {
						cy.logout()
						cy.request({
							method: 'DELETE',
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
