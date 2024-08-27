/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile, triggerActionForFile } from './FilesUtils'

const haveValidity = (validity: string | RegExp) => {
	if (typeof validity === 'string') {
		return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.equal(validity)
	}
	return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.match(validity)
}

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
			.should(haveValidity(/forbidden file or folder name/i))
	})
})
