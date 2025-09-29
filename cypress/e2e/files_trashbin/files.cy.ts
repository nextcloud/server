/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'

// @ts-expect-error package has wrong typings
import { deleteDownloadsFolderBeforeEach } from 'cypress-delete-downloads-folder'
import { deleteFileWithRequest, getRowForFileId, selectAllFiles, triggerActionForFileId } from '../files/FilesUtils.ts'

describe('files_trashbin: download files', { testIsolation: true }, () => {
	let user: User
	const fileids: number[] = []

	deleteDownloadsFolderBeforeEach()

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
				.then(({ headers }) => fileids.push(Number.parseInt(headers['oc-fileid'])))
				.then(() => deleteFileWithRequest(user, '/file.txt'))
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/other-file.txt')
				.then(({ headers }) => fileids.push(Number.parseInt(headers['oc-fileid'])))
				.then(() => deleteFileWithRequest(user, '/other-file.txt'))
		})
	})

	beforeEach(() => {
		cy.login(user)
		cy.visit('/apps/files/trashbin')
	})

	it('can download file', () => {
		getRowForFileId(fileids[0]).should('be.visible')
		getRowForFileId(fileids[1]).should('be.visible')

		triggerActionForFileId(fileids[0], 'download')

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	it('can download a file using default action', () => {
		getRowForFileId(fileids[0])
			.should('be.visible')
			.findByRole('button', { name: 'Download' })
			.click({ force: true })

		const downloadsFolder = Cypress.config('downloadsFolder')
		cy.readFile(`${downloadsFolder}/file.txt`, { timeout: 15000 })
			.should('exist')
			.and('have.length.gt', 8)
			.and('equal', '<content>')
	})

	// TODO: Fix this as this dependens on the webdav zip folder plugin not working for trashbin (and never worked with old NC legacy download ajax as well)
	it('does not offer bulk download', () => {
		cy.get('[data-cy-files-list-row-checkbox]').should('have.length', 2)
		selectAllFiles()
		cy.get('.files-list__selected').should('contain.text', '2 selected')
		cy.get('[data-cy-files-list-selection-action="restore"]').should('be.visible')
		cy.get('[data-cy-files-list-selection-action="download"]').should('not.exist')
	})
})
