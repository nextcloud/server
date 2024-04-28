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

import { assertVersionContent, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions, nameVersion } from './filesVersionsUtils'
import { clickOnBreadcrumbs, closeSidebar, copyFile, moveFile, navigateToFolder } from '../files/FilesUtils'
import type { User } from '@nextcloud/cypress'

/**
 *
 * @param filePath
 */
function assertVersionsContent(filePath: string) {
	const path = filePath.split('/').slice(0, -1).join('/')

	clickOnBreadcrumbs('All files')

	if (path !== '') {
		navigateToFolder(path)
	}

	openVersionsPanel(filePath)

	cy.get('[data-files-versions-version]').should('have.length', 3)
	assertVersionContent(0, 'v3')
	assertVersionContent(1, 'v2')
	assertVersionContent(2, 'v1')
}

describe('Versions cross share move and copy', () => {
	let randomSharedFolderName = ''
	let randomFileName = ''
	let randomFilePath = ''
	let alice: User
	let bob: User

	before(() => {
		randomSharedFolderName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)

		cy.createRandomUser()
			.then((user) => {
				alice = user
				cy.mkdir(alice, `/${randomSharedFolderName}`)
				setupTestSharedFileFromUser(alice, randomSharedFolderName, {})
			})
			.then((user) => { bob = user })
	})

	beforeEach(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
		randomFilePath = `${randomSharedFolderName}/${randomFileName}`
		uploadThreeVersions(alice, randomFilePath)

		cy.login(bob)
		cy.visit('/apps/files')
		navigateToFolder(randomSharedFolderName)
		openVersionsPanel(randomFilePath)
		nameVersion(2, 'v1')
		closeSidebar()
	})

	it('Also moves versions when bob moves the file out of a received share', () => {
		moveFile(randomFileName, '/')
		assertVersionsContent(randomFileName)
		// TODO: move that in assertVersionsContent when copying files keeps the versions' metadata
		cy.get('[data-files-versions-version]').eq(2).contains('v1')
	})

	it('Also copies versions when bob copies the file out of a received share', () => {
		copyFile(randomFileName, '/')
		assertVersionsContent(randomFileName)
	})

	context('When a file is in a subfolder', () => {
		let randomSubFolderName
		let randomSubSubFolderName

		beforeEach(() => {
			randomSubFolderName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
			randomSubSubFolderName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
			clickOnBreadcrumbs('All files')
			cy.mkdir(bob, `/${randomSharedFolderName}/${randomSubFolderName}`)
			cy.mkdir(bob, `/${randomSharedFolderName}/${randomSubFolderName}/${randomSubSubFolderName}`)
			cy.login(bob)
			navigateToFolder(randomSharedFolderName)
			moveFile(randomFileName, `${randomSubFolderName}/${randomSubSubFolderName}`)
		})

		it('Also moves versions when bob moves the containing folder out of a received share', () => {
			moveFile(randomSubFolderName, '/')
			assertVersionsContent(`${randomSubFolderName}/${randomSubSubFolderName}/${randomFileName}`)
			// TODO: move that in assertVersionsContent when copying files keeps the versions' metadata
			cy.get('[data-files-versions-version]').eq(2).contains('v1')
		})

		it('Also copies versions when bob copies the containing folder out of a received share', () => {
			copyFile(randomSubFolderName, '/')
			assertVersionsContent(`${randomSubFolderName}/${randomSubSubFolderName}/${randomFileName}`)
		})
	})
})
