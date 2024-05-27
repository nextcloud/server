/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getRowForFile } from './FilesUtils.ts'

describe('files: Drag and Drop', { testIsolation: true }, () => {
	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
		cy.visit('/apps/files')
	})

	it('can drop a file', () => {
		const dataTransfer = new DataTransfer()
		dataTransfer.items.add(new File([], 'single-file.txt'))

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		// Make sure the drop notice is not visible
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')

		// Trigger the drop notice
		cy.get('main.app-content').trigger('dragover', { dataTransfer })
		cy.get('[data-cy-files-drag-drop-area]').should('be.visible')

		// Upload drop a file
		cy.get('[data-cy-files-drag-drop-area]').selectFile({
			fileName: 'single-file.txt',
			contents: ['hello '.repeat(1024)],
		}, { action: 'drag-drop' })

		cy.wait('@uploadFile')

		// Make sure the upload is finished
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')
		cy.get('[data-cy-upload-picker] progress').should('not.be.visible')
		cy.get('@uploadFile.all').should('have.length', 1)

		getRowForFile('single-file.txt').should('be.visible')
		getRowForFile('single-file.txt').find('[data-cy-files-list-row-size]').should('contain', '6 KB')
	})

	it('can drop multiple files', () => {
		const dataTransfer = new DataTransfer()
		dataTransfer.items.add(new File([], 'first.txt'))
		dataTransfer.items.add(new File([], 'second.txt'))

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		// Make sure the drop notice is not visible
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')

		// Trigger the drop notice
		cy.get('main.app-content').trigger('dragover', { dataTransfer })
		cy.get('[data-cy-files-drag-drop-area]').should('be.visible')

		// Upload drop a file
		cy.get('[data-cy-files-drag-drop-area]').selectFile([
			{
				fileName: 'first.txt',
				contents: ['Hello'],
			},
			{
				fileName: 'second.txt',
				contents: ['World'],
			},
		], { action: 'drag-drop' })

		cy.wait('@uploadFile')

		// Make sure the upload is finished
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')
		cy.get('[data-cy-upload-picker] progress').should('not.be.visible')
		cy.get('@uploadFile.all').should('have.length', 2)

		getRowForFile('first.txt').should('be.visible')
		getRowForFile('second.txt').should('be.visible')
	})

	it('will ignore legacy Folders', () => {
		cy.window().then((win) => {
			// Remove the Filesystem API to force the legacy File API
			// See how cypress mocks the Filesystem API in https://github.com/cypress-io/cypress/blob/74109094a92df3bef073dda15f17194f31850d7d/packages/driver/src/cy/commands/actions/selectFile.ts#L24-L37
			Object.defineProperty(win.DataTransferItem.prototype, 'getAsEntry', { get: undefined })
			Object.defineProperty(win.DataTransferItem.prototype, 'webkitGetAsEntry', { get: undefined })
		})

		const dataTransfer = new DataTransfer()
		dataTransfer.items.add(new File([], 'first.txt'))
		dataTransfer.items.add(new File([], 'second.txt'))

		// Legacy File API (not FileSystem API), will treat Folders as Files
		// with empty type and empty content
		dataTransfer.items.add(new File([], 'Foo', { type: 'httpd/unix-directory' }))
		dataTransfer.items.add(new File([], 'Bar'))

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		// Make sure the drop notice is not visible
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')

		// Trigger the drop notice
		cy.get('main.app-content').trigger('dragover', { dataTransfer })
		cy.get('[data-cy-files-drag-drop-area]').should('be.visible')

		// Upload drop a file
		cy.get('[data-cy-files-drag-drop-area]').selectFile([
			{
				fileName: 'first.txt',
				contents: ['Hello'],
			},
			{
				fileName: 'second.txt',
				contents: ['World'],
			},
			{
				fileName: 'Foo',
				contents: {},
			},
			{
				fileName: 'Bar',
				contents: { mimeType: 'httpd/unix-directory' },
			},
		], { action: 'drag-drop' })

		cy.wait('@uploadFile')

		// Make sure the upload is finished
		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')
		cy.get('[data-cy-upload-picker] progress').should('not.be.visible')
		cy.get('@uploadFile.all').should('have.length', 2)

		getRowForFile('first.txt').should('be.visible')
		getRowForFile('second.txt').should('be.visible')
		getRowForFile('Foo').should('not.exist')
		getRowForFile('Bar').should('not.exist')
	})
})
