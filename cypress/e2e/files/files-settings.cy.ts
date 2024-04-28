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
import { getRowForFile } from './FilesUtils'

const showHiddenFiles = () => {
	// Open the files settings
	cy.get('[data-cy-files-navigation-settings-button] a').click({ force: true })
	// Toggle the hidden files setting
	cy.get('[data-cy-files-settings-setting="show_hidden"]').within(() => {
		cy.get('input').should('not.be.checked')
		cy.get('input').check({ force: true })
	})
	// Close the dialog
	cy.get('[data-cy-files-navigation-settings] button[aria-label="Close"]').click()
}

describe('files: Hide or show hidden files', { testIsolation: true }, () => {
	let user: User

	const setupFiles = () => cy.createRandomUser().then(($user) => {
		user = $user

		cy.uploadContent(user, new Blob([]), 'text/plain', '/.file')
		cy.uploadContent(user, new Blob([]), 'text/plain', '/visible-file')
		cy.mkdir(user, '/.folder')
		cy.login(user)
	})

	context('view: All files', { testIsolation: false }, () => {
		before(setupFiles)

		it('hides dot-files by default', () => {
			cy.visit('/apps/files')

			getRowForFile('visible-file').should('be.visible')
			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
		})
	})

	context('view: Personal files', { testIsolation: false }, () => {
		before(setupFiles)

		it('hides dot-files by default', () => {
			cy.visit('/apps/files/personal')

			getRowForFile('visible-file').should('be.visible')
			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
		})
	})

	context('view: Recent files', { testIsolation: false }, () => {
		before(() => {
			setupFiles().then(() => {
				// also add hidden file in hidden folder
				cy.uploadContent(user, new Blob([]), 'text/plain', '/.folder/other-file')
				cy.login(user)
			})
		})

		it('hides dot-files by default', () => {
			cy.visit('/apps/files/recent')

			getRowForFile('visible-file').should('be.visible')
			getRowForFile('.file').should('not.exist')
			getRowForFile('.folder').should('not.exist')
			getRowForFile('other-file').should('not.exist')
		})

		it('can show hidden files', () => {
			showHiddenFiles()

			getRowForFile('visible-file').should('be.visible')
			// Now the files should be visible
			getRowForFile('.file').should('be.visible')
			getRowForFile('.folder').should('be.visible')
			getRowForFile('other-file').should('be.visible')
		})
	})
})
