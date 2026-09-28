/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getRowForFile, haveValidity, triggerActionForFile } from '../../files/FilesUtils.ts'
import { getShareUrl, setupPublicShare } from './PublicShareUtils.ts'

describe('files_sharing: Public share - renaming files', { testIsolation: true }, () => {

	beforeEach(() => {
		setupPublicShare()
			.then(() => cy.logout())
			.then(() => cy.visit(getShareUrl()))
	})

	it('can rename a file', () => {
		// All are visible by default
		getRowForFile('foo.txt').should('be.visible')

		triggerActionForFile('foo.txt', 'rename')

		getRowForFile('foo.txt')
			.findByRole('textbox', { name: 'Filename' })
			.should('be.visible')
			.type('{selectAll}other.txt')
			.should(haveValidity(''))
			.type('{enter}')

		// See it is renamed
		getRowForFile('other.txt').should('be.visible')
	})
})
