/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

import type { User } from "@nextcloud/cypress"

const startCopyMove = (file: string) => {
	cy.get(`.files-fileList tr[data-file="${file}"`)
		.find('.fileactions [data-action="menu"]')
		.click()
	cy.get('.fileActionsMenu .action-movecopy').click()
}

describe('Login with a new user and open the files app', function() {
	before(function() {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	Cypress.on('uncaught:exception', (err) => {
		// This can happen because of blink engine & skeleton animation, its not a bug just engine related.
		if (err.message.includes('ResizeObserver loop limit exceeded')) {
		  return false
		}
	})

	it('See the default file welcome.txt in the files list', function() {
		cy.visit('/apps/files')
		cy.get('.files-fileList tr').should('contain', 'welcome.txt')
	})

	it('See the file list sorting order is saved', function() {
		cy.intercept('PUT', /api\/v1\/views\/files\/sorting_direction$/).as('sorting_direction')

		cy.visit('/apps/files')
		// default to sorting by name
		cy.get('.files-filestable th.column-name .sort-indicator').should('be.visible')
		// change to size
		cy.get('.files-filestable th').contains('Size').click()
		// size sorting should be active
		cy.get('.files-filestable th.column-name .sort-indicator').should('not.be.visible')
		cy.get('.files-filestable th.column-size .sort-indicator').should('be.visible')
		cy.wait('@sorting_direction')

		// Re-visit
		cy.visit('/apps/files')
		// now sorting by name should be disabled and sorting by size should be enabled
		cy.get('.files-filestable th.column-name .sort-indicator').should('not.be.visible')
		cy.get('.files-filestable th.column-size .sort-indicator').should('be.visible')
	})
})

describe('Testing the copy move action (FilePicker)', () => {
	let currentUser: User
	beforeEach(function() {
		cy.createRandomUser().then((user) => {
			currentUser = user
			cy.login(user)
		})
	})

	afterEach(function() {
		cy.deleteUser(currentUser)
	})

	it('Copy a file in its same folder', () => {
		cy.visit('/apps/files')
		// When I start the move or copy operation for "welcome.txt"
		startCopyMove('welcome.txt')
		// And I copy to the last selected folder in the file picker
		cy.get('.dialog__actions button').contains('Copy').click()
		// Then I see that the file list contains a file named "welcome.txt"
		cy.get('.files-fileList tr').should('contain', 'welcome.txt')
		// And I see that the file list contains a file named "welcome (copy).txt"
		cy.get('.files-fileList tr').should('contain', 'welcome (copy).txt')
	})

	it('copy a file twice in its same folder', () => {
		cy.visit('/apps/files')
		// When I start the move or copy operation for "welcome.txt"
		startCopyMove('welcome.txt')
		// And I copy to the last selected folder in the file picker
		cy.get('.dialog__actions button').contains('Copy').click()
		// When I start the move or copy operation for "welcome.txt"
		startCopyMove('welcome.txt')
		// And I copy to the last selected folder in the file picker
		cy.get('.dialog__actions button').contains('Copy').click()
		// Then I see that the file list contains a file named "welcome.txt"
		cy.get('.files-fileList tr').should('contain', 'welcome.txt')
		// And I see that the file list contains a file named "welcome (copy).txt"
		cy.get('.files-fileList tr').should('contain', 'welcome (copy).txt')
		// And I see that the file list contains a file named "welcome (copy 2).txt"
		cy.get('.files-fileList tr').should('contain', 'welcome (copy 2).txt')
	})

	it('copy a copy of a file in its same folder', () => {
		cy.visit('/apps/files')
		// When I start the move or copy operation for "welcome.txt"
		startCopyMove('welcome.txt')
		// And I copy to the last selected folder in the file picker
		cy.get('.dialog__actions button').contains('Copy').click()
		// When I start the move or copy operation for "welcome (copy).txt"
		startCopyMove('welcome (copy).txt')
		// And I copy to the last selected folder in the file picker
		cy.get('.dialog__actions button').contains('Copy').click()
		// Then I see that the file list contains a file named "welcome.txt"
		cy.get('.files-fileList tr').should('contain', 'welcome.txt')
		// And I see that the file list contains a file named "welcome (copy).txt"
		cy.get('.files-fileList tr').should('contain', 'welcome (copy).txt')
		// And I see that the file list contains a file named "welcome (copy 2).txt"
		cy.get('.files-fileList tr').should('contain', 'welcome (copy 2).txt')
	})
})
