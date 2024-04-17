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
import { getRowForFile, triggerActionForFile } from './FilesUtils'

describe('Files: Delete action', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.mkdir(user, '/folder')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/file')
		cy.login(user)
		cy.visit('/apps/files')
	}))

	it('can delete a file', () => {
		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'delete')

		getRowForFile('file').should('not.exist')
		cy.contains('.toast-success', '"Delete file" action').should('be.visible')

		// reload to check if file is really deleted
		cy.reload()
		// list loaded
		getRowForFile('folder').should('be.visible')
		// file is really deleted
		getRowForFile('file').should('not.exist')
	})

	it('retries to delete a locked file', () => {
		cy.intercept({
			method: 'DELETE',
			pathname: /remote\.php\/dav\/files/,
			times: 1,
		 }, { statusCode: 423, body: {} }).as('deleteAndMock')

		cy.intercept({
			method: 'DELETE',
			pathname: /remote\.php\/dav\/files/,
		}).as('delete')

		getRowForFile('file').should('be.visible')

		triggerActionForFile('file', 'delete')

		// wait for delete first request
		cy.wait('@delete')
		// Also wait for the mock
		cy.wait('@deleteAndMock')
		cy.contains('.toast-warning', 'file is currently locked').should('be.visible')
		// now wait for the second real delete request
		cy.wait('@delete', { timeout: 15000 })
		cy.contains('.toast-success', '"Delete file" action').should('be.visible')
	})
})
