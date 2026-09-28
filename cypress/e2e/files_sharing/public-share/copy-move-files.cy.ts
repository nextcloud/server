/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { copyFile, getRowForFile, moveFile, navigateToFolder } from '../../files/FilesUtils.ts'
import { getShareUrl, setupPublicShare } from './PublicShareUtils.ts'

describe('files_sharing: Public share - copy and move files', { testIsolation: true }, () => {

	beforeEach(() => {
		setupPublicShare()
			.then(() => cy.logout())
			.then(() => cy.visit(getShareUrl()))
	})

	it('Can copy a file to new folder', () => {
		getRowForFile('foo.txt').should('be.visible')
		getRowForFile('subfolder').should('be.visible')

		copyFile('foo.txt', 'subfolder')

		// still visible
		getRowForFile('foo.txt').should('be.visible')
		navigateToFolder('subfolder')

		cy.url().should('contain', 'dir=/subfolder')
		getRowForFile('foo.txt').should('be.visible')
		getRowForFile('bar.txt').should('be.visible')
		getRowForFile('subfolder').should('not.exist')
	})

	it('Can move a file to new folder', () => {
		getRowForFile('foo.txt').should('be.visible')
		getRowForFile('subfolder').should('be.visible')

		moveFile('foo.txt', 'subfolder')

		// wait until visible again
		getRowForFile('subfolder').should('be.visible')

		// file should be moved -> not exist anymore
		getRowForFile('foo.txt').should('not.exist')
		navigateToFolder('subfolder')

		cy.url().should('contain', 'dir=/subfolder')
		getRowForFile('foo.txt').should('be.visible')
		getRowForFile('subfolder').should('not.exist')
	})
})
