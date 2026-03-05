/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { calculateViewportHeight, createFolder, getRowForFile, haveValidity, renameFile, triggerActionForFile } from './FilesUtils.ts'

describe('files: Rename nodes', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			// remove welcome file
			cy.rm(user, '/welcome.txt')
			// create a file called "file.txt"
			cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')

			// login and visit files app
			cy.login(user)
		})
		cy.visit('/apps/files')
	})

	it('can rename a file', () => {
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')

		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}other.txt')
			.should(haveValidity(''))
			.type('{enter}')

		// See it is renamed
		getRowForFile('other.txt').should('be.visible')
	})

	/**
	 * If this test gets flaky than we have a problem:
	 * It means that the selection is not reliable set to the basename
	 */
	it('only selects basename of file', () => {
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')

		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.should((el) => {
				const input = el.get(0) as HTMLInputElement
				expect(input.selectionStart).to.equal(0)
				expect(input.selectionEnd).to.equal('file'.length)
			})
	})

	it('show validation error on file rename', () => {
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')

		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}.htaccess')
			// See validity
			.should(haveValidity(/reserved name/i))
	})

	it('shows accessible loading information', () => {
		const { resolve, promise } = Promise.withResolvers<void>()

		getRowForFile('file.txt').should('be.visible')

		// intercept the rename (MOVE)
		// the callback will wait until the promise resolve (so we have time to check the loading state)
		cy.intercept(
			'MOVE',
			/\/remote.php\/dav\/files\//,
			(request) => {
				// we need to wait in the onResponse handler as the intercept handler times out otherwise
				request.on('response', async () => {
					await promise
				})
			},
		).as('moveFile')

		// Start the renaming
		triggerActionForFile('file.txt', 'rename')
		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}new-name.txt{enter}')

		// Loading state is visible
		getRowForFile('new-name.txt')
			.findByRole('img', { name: 'File is loading' })
			.should('be.visible')
		// checkbox is not visible
		getRowForFile('new-name.txt')
			.findByRole('checkbox', { name: /^Toggle selection/ })
			.should('not.exist')

		cy.log('Resolve promise to preoceed with MOVE request')
			.then(() => resolve())

		// Ensure the request is done (file renamed)
		cy.wait('@moveFile')

		// checkbox visible again
		getRowForFile('new-name.txt')
			.findByRole('checkbox', { name: /^Toggle selection/ })
			.should('exist')
		// see the loading state is gone
		getRowForFile('new-name.txt')
			.findByRole('img', { name: 'File is loading' })
			.should('not.exist')
	})

	it('cancel renaming on esc press', () => {
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')

		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}other.txt')
			.should(haveValidity(''))
			.type('{esc}')

		// See it is not renamed
		getRowForFile('other.txt').should('not.exist')
		getRowForFile('file.txt')
			.should('be.visible')
			.find('input[type="text"]')
			.should('not.exist')
	})

	it('cancel on enter if no new name is entered', () => {
		// All are visible by default
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')

		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{enter}')

		// See it is not renamed
		getRowForFile('file.txt')
			.should('be.visible')
			.find('input[type="text"]')
			.should('not.exist')
	})

	/**
	 * This is a regression test of: https://github.com/nextcloud/server/issues/47438
	 * The issue was that the renaming state was not reset when the new name moved the file out of the view of the current files list
	 * due to virtual scrolling the renaming state was not changed then by the UI events (as the component was taken out of DOM before any event handling).
	 */
	it('correctly resets renaming state', () => {
		// Create 19 additional files
		for (let i = 1; i <= 19; i++) {
			cy.uploadContent(user, new Blob([]), 'text/plain', `/file${i}.txt`)
		}

		// Calculate and setup a viewport where only the first 4 files are visible, causing 6 rows to be rendered
		cy.viewport(768, 500)
		cy.login(user)
		calculateViewportHeight(4)
			.then((height) => cy.viewport(768, height))

		cy.visit('/apps/files')

		getRowForFile('file.txt')
			.should('be.visible')
		// Z so it is shown last
		renameFile('file.txt', 'zzz.txt')
		// not visible any longer
		getRowForFile('zzz.txt')
			.should('not.exist')
		// scroll file list to bottom
		cy.get('[data-cy-files-list]')
			.scrollTo('bottom')
		cy.screenshot()
		// The file is no longer in rename state
		getRowForFile('zzz.txt')
			.should('be.visible')
			.findByRole('textbox', { name: 'Filename' })
			.should('not.exist')
	})

	it('shows warning on extension change - select new extension', () => {
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')
		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}file.md')
			.type('{enter}')

		// See warning dialog
		cy.findByRole('dialog', { name: 'Change file extension' })
			.should('be.visible')
			.findByRole('button', { name: 'Use .md' })
			.click()

		// See it is renamed
		getRowForFile('file.md').should('be.visible')
	})

	it('shows warning on extension change - select old extension', () => {
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')
		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}document.md')
			.type('{enter}')

		// See warning dialog
		cy.findByRole('dialog', { name: 'Change file extension' })
			.should('be.visible')
			.findByRole('button', { name: 'Keep .txt' })
			.click()

		// See it is renamed
		getRowForFile('document.txt').should('be.visible')
	})

	it('shows warning on extension removal', () => {
		getRowForFile('file.txt').should('be.visible')

		triggerActionForFile('file.txt', 'rename')
		getRowForFile('file.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}file')
			.type('{enter}')

		cy.findByRole('dialog', { name: 'Change file extension' })
			.should('be.visible')
			.findByRole('button', { name: 'Keep .txt' })
			.should('be.visible')
		cy.findByRole('dialog', { name: 'Change file extension' })
			.findByRole('button', { name: 'Remove extension' })
			.should('be.visible')
			.click()

		// See it is renamed
		getRowForFile('file').should('be.visible')
		getRowForFile('file.txt').should('not.exist')
	})

	it('does not show warning on folder renaming with a dot', () => {
		createFolder('folder.2024')

		getRowForFile('folder.2024').should('be.visible')

		triggerActionForFile('folder.2024', 'rename')
		getRowForFile('folder.2024')
			.findByRole('textbox', { name: 'Folder name' })
			.should('be.visible')
			.type('{selectAll}folder.2025')
			.should(haveValidity(''))
			.type('{enter}')

		// See warning dialog
		cy.get('[role=dialog]').should('not.exist')

		// See it is not renamed
		getRowForFile('folder.2025').should('be.visible')
	})
})
