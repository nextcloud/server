/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, haveValidity, renameFile, triggerActionForFile } from './FilesUtils'

describe('files: Rename nodes', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => cy.createRandomUser().then(($user) => {
		user = $user

		cy.uploadContent(user, new Blob([]), 'text/plain', '/file.txt')
		cy.login(user)
		cy.visit('/apps/files')
	}))

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
		const { resolve, promise } = Promise.withResolvers()

		getRowForFile('file.txt').should('be.visible')

		// intercept the rename (MOVE)
		// the callback will wait until the promise resolve (so we have time to check the loading state)
		cy.intercept(
			'MOVE',
			/\/remote.php\/dav\/files\//,
			(request) => {
				// we need to wait in the onResponse handler as the intercept handler times out otherwise
				request.on('response', async () => { await promise })
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
			.then(() => resolve(null))

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

	/**
	 * This is a regression test of: https://github.com/nextcloud/server/issues/47438
	 * The issue was that the renaming state was not reset when the new name moved the file out of the view of the current files list
	 * due to virtual scrolling the renaming state was not changed then by the UI events (as the component was taken out of DOM before any event handling).
	 */
	it('correctly resets renaming state', () => {
		for (let i = 1; i <= 20; i++) {
			cy.uploadContent(user, new Blob([]), 'text/plain', `/file${i}.txt`)
		}
		cy.viewport(1200, 500) // 500px is smaller then 20 * 50 which is the place that the files take up
		cy.login(user)
		cy.visit('/apps/files')

		getRowForFile('file.txt').should('be.visible')
		// Z so it is shown last
		renameFile('file.txt', 'zzz.txt')
		// not visible any longer
		getRowForFile('zzz.txt').should('not.be.visible')
		// scroll file list to bottom
		cy.get('[data-cy-files-list]').scrollTo('bottom')
		cy.screenshot()
		// The file is no longer in rename state
		getRowForFile('zzz.txt')
			.should('be.visible')
			.findByRole('textbox', { name: 'Filename' })
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
})
