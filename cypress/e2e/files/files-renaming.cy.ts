/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, haveValidity, triggerActionForFile } from './FilesUtils'

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
})
