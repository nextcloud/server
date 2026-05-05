/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { ShareType } from '@nextcloud/sharing'
import { deleteDownloadsFolderBeforeEach } from '../../support/utils/deleteDownloadsFolder.ts'
import { randomString } from '../../support/utils/randomString.ts'
import { deleteFileWithRequest, getRowForFileId, selectAllFiles, triggerActionForFileId } from '../files/FilesUtils.ts'

describe('files_trashbin: download files', { testIsolation: true }, () => {
	let user: User
	const fileids: [number, number] = [0, 0]

	deleteDownloadsFolderBeforeEach()

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/file.txt')
				.then(({ headers }) => fileids[0] = Number.parseInt(headers['oc-fileid']))
				.then(() => deleteFileWithRequest(user, '/file.txt'))
			cy.uploadContent(user, new Blob(['<content>']), 'text/plain', '/other-file.txt')
				.then(({ headers }) => fileids[1] = Number.parseInt(headers['oc-fileid']))
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

describe('files_trashbin: file row', { testIsolation: true }, () => {
	let alice: User
	let bob: User
	let randomGroupName: string
	let fileId: number

	before(() => {
		randomGroupName = randomString(10)
		cy.runOccCommand(`group:add ${randomGroupName}`)

		cy.createRandomUser().then((user) => {
			alice = user

			cy.modifyUser(alice, 'display', 'Alice')

			cy.mkdir(alice, '/Shared')
		})

		cy.createRandomUser().then((user) => {
			bob = user

			cy.modifyUser(bob, 'display', 'Bob')

			cy.runOccCommand(`group:adduser ${randomGroupName} ${bob.userId}`)
		})
	})

	it('shows data for file deleted by owner', () => {
		cy.uploadContent(alice, new Blob(['<content>']), 'text/plain', '/test-file.txt')
			.then(({ headers }) => fileId = Number.parseInt(headers['oc-fileid']))
			.then(() => deleteFileWithRequest(alice, '/test-file.txt'))

		cy.login(alice)
		cy.visit('/apps/files/trashbin')

		getRowForFileId(fileId).should('be.visible')
		// The full name includes one span for the name and one span for the
		// extension, so text() returns a space when composing them even if it
		// will not be visible when rendered in the browser.
		getRowForFileId(fileId).find('[data-cy-files-list-row-name]').should((element) => expect(element.text().trim()).to.equal('test-file .txt'))
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--original-location"]').should('have.text', 'All files')
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--deleted-by"]').should('have.text', 'You')
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--deleted"]').should('have.text', 'few seconds ago')
	})

	it('shows data for file deleted by sharee in a folder shared with a group', () => {
		cy.createShare(alice, '/Shared', ShareType.Group, randomGroupName)

		cy.uploadContent(alice, new Blob(['<content>']), 'text/plain', '/Shared/test-file.txt')
			.then(({ headers }) => fileId = Number.parseInt(headers['oc-fileid']))
			.then(() => deleteFileWithRequest(bob, '/Shared/test-file.txt'))

		cy.login(alice)
		cy.visit('/apps/files/trashbin')

		getRowForFileId(fileId).should('be.visible')
		// The full name includes one span for the name and one span for the
		// extension, so text() returns a space when composing them even if it
		// will not be visible when rendered in the browser.
		getRowForFileId(fileId).find('[data-cy-files-list-row-name]').should((element) => expect(element.text().trim()).to.equal('test-file .txt'))
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--original-location"]').should('have.text', 'Shared')
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--deleted-by"]').should('have.text', 'Bob')
		getRowForFileId(fileId).find('[data-cy-files-list-row-column-custom="files_trashbin--deleted"]').should('have.text', 'few seconds ago')
	})
})
