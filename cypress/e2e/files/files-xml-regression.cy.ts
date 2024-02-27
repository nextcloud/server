/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen
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

import { getRowForFile, triggerActionForFile } from './FilesUtils.ts'

/**
 * This is a regression test for https://github.com/nextcloud/server/issues/43331
 * Where files with XML entities in their names were wrongly displayed and could no longer be renamed / deleted etc.
 */
describe('Files: Can handle XML entities in file names', { testIsolation: false }, () => {
	before(() => {
		cy.createRandomUser().then((user) => {
			cy.uploadContent(user, new Blob(), 'text/plain', '/and.txt')
			cy.login(user)
			cy.visit('/apps/files/')
		})
	})

	it('Can reanme to a file name containing XML entities', () => {
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('renameFile')
		triggerActionForFile('and.txt', 'rename')
		getRowForFile('and.txt')
			.find('form[aria-label="Rename file"] input')
			.type('{selectAll}&amp;.txt{enter}')

		cy.wait('@renameFile')
		getRowForFile('&amp;.txt').should('be.visible')
	})

	it('After a reload the filename is preserved', () => {
		cy.reload()
		getRowForFile('&amp;.txt').should('be.visible')
		getRowForFile('&.txt').should('not.exist')
	})

	it('Can delete the file', () => {
		cy.intercept('DELETE', /\/remote.php\/dav\/files\//).as('deleteFile')
		triggerActionForFile('&amp;.txt', 'delete')
		cy.wait('@deleteFile')

		cy.contains('.toast-success', /Delete .* successfull/)
			.should('be.visible')
		getRowForFile('&amp;.txt').should('not.exist')

		cy.reload()
		getRowForFile('&amp;.txt').should('not.exist')
		getRowForFile('&.txt').should('not.exist')
	})
})
