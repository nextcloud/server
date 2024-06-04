/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import { getRowForFile, navigateToFolder, triggerActionForFile } from './FilesUtils'

describe('Files: Sidebar', { testIsolation: true }, () => {
	let user: User
	let fileId: number = 0

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file').then((response) => {
			fileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
		})
		cy.login(user)
	}))

	it('opens the sidebar', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[cy-data-sidebar]').should('be.visible')
	})

	it('changes the current fileid', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'details')

		cy.get('[cy-data-sidebar]').should('be.visible')
		cy.url().should('contain', `apps/files/files/${fileId}`)
	})

	it('closes the sidebar on delete', () => {
		cy.visit('/apps/files')
		getRowForFile('file').should('be.visible')

		// open the sidebar
		triggerActionForFile('file', 'details')
		// validate it is open
		cy.get('[cy-data-sidebar]').should('be.visible')

		triggerActionForFile('file', 'delete')
		cy.get('[cy-data-sidebar]').should('not.exist')
	})

	it('changes the fileid on delete', () => {
		cy.uploadContent(user, new Blob([]), 'text/plain', '/folder/other').then((response) => {
			const otherFileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
			cy.login(user)
			cy.visit('/apps/files')

			getRowForFile('folder').should('be.visible')
			navigateToFolder('folder')
			getRowForFile('other').should('be.visible')

			// open the sidebar
			triggerActionForFile('other', 'details')
			// validate it is open
			cy.get('[cy-data-sidebar]').should('be.visible')
			cy.url().should('contain', `apps/files/files/${otherFileId}`)

			triggerActionForFile('other', 'delete')
			cy.get('[cy-data-sidebar]').should('not.exist')
			// Ensure the URL is changed
			cy.url().should('not.contain', `apps/files/files/${otherFileId}`)
		})
	})
})
