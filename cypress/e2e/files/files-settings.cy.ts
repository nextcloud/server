/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getRowForFile } from './FilesUtils.ts'

describe('files: Set default view', { testIsolation: true }, () => {
	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			cy.login($user)
		})
	})

	it('Defaults to the "files" view', () => {
		cy.visit('/apps/files')

		// See URL and current view
		cy.url().should('match', /\/apps\/files\/files/)
		cy.get('[data-cy-files-content-breadcrumbs]')
			.findByRole('button', {
				name: 'All files',
				description: 'Reload current directory',
			})

		// See the option is also selected
		// Open the files settings
		cy.findByRole('link', { name: 'Files settings' }).click({ force: true })
		// Toggle the setting
		cy.findByRole('dialog', { name: 'Files settings' })
			.should('be.visible')
			.within(() => {
				cy.findByRole('group', { name: 'Default view' })
					.findByRole('radio', { name: 'All files' })
					.should('be.checked')
			})
	})

	it('Can set it to personal files', () => {
		cy.visit('/apps/files')

		// Open the files settings
		cy.findByRole('link', { name: 'Files settings' }).click({ force: true })
		// Toggle the setting
		cy.findByRole('dialog', { name: 'Files settings' })
			.should('be.visible')
			.within(() => {
				cy.findByRole('group', { name: 'Default view' })
					.findByRole('radio', { name: 'Personal files' })
					.check({ force: true })
			})

		cy.visit('/apps/files')
		cy.url().should('match', /\/apps\/files\/personal/)
		cy.get('[data-cy-files-content-breadcrumbs]')
			.findByRole('button', {
				name: 'Personal files',
				description: 'Reload current directory',
			})
	})
})

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

/**
 * Helper to toggle the hidden files settings
 */
function showHiddenFiles() {
	// Open the files settings
	cy.get('[data-cy-files-navigation-settings-button] a').click({ force: true })
	// Toggle the hidden files setting
	cy.findByRole('switch', { name: /show hidden files/i })
		.as('hiddenFiles')
		.scrollIntoView()
	cy.get('@hiddenFiles')
		.should('not.be.checked')
		.check({ force: true })

	// Close the dialog
	cy.get('[data-cy-files-navigation-settings] button[aria-label="Close"]').click()
}
