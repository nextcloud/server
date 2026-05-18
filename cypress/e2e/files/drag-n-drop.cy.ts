/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/e2e-test-server/cypress'

import { getRowForFile, navigateToFolder } from './FilesUtils.ts'

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

		// see the warning
		cy.get('.toast-warning').should('exist')

		// close all toasts
		cy.get('.toastify')
			.findAllByRole('button', { name: 'Close' })
			.click({ multiple: true })

		getRowForFile('first.txt').should('be.visible')
		getRowForFile('second.txt').should('be.visible')
		getRowForFile('Foo').should('not.exist')
		getRowForFile('Bar').should('not.exist')
	})
})

// Regression coverage for https://github.com/nextcloud/server/issues/60139
// The per-row drop handler in FileEntryMixin used to pass raw FileSystemEntry
// objects to @nextcloud/upload's batchUpload; on some Chromium builds the
// instanceof-based conversion silently failed and the chunk uploader crashed
// with "e.slice is not a function". The fix routes the per-row drop through
// the same dataTransferToFileTree pipeline as the main file-list drop.
//
// Sibling describe (not nested) so the outer suite's `beforeEach` doesn't
// spin up an unused user before each test in this block.
describe('files: Drag and Drop onto a folder row', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		cy.createRandomUser().then((u) => {
			user = u
			cy.mkdir(user, '/subfolder')
			cy.login(user)
		})
		cy.visit('/apps/files')
		getRowForFile('subfolder').should('be.visible')
	})

	it('can drop a single file onto a subfolder row', () => {
		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		getRowForFile('subfolder').selectFile({
			fileName: 'dropped-into-subfolder.txt',
			contents: ['hello '.repeat(1024)],
		}, { action: 'drag-drop' })

		cy.wait('@uploadFile').its('request.url')
			.should('match', /\/subfolder\/dropped-into-subfolder\.txt$/)

		cy.get('[data-cy-upload-picker] progress').should('not.be.visible')

		navigateToFolder('/subfolder')
		getRowForFile('dropped-into-subfolder.txt').should('be.visible')
	})

	it('can drop multiple files onto a subfolder row', () => {
		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		getRowForFile('subfolder').selectFile([
			{ fileName: 'one.txt', contents: ['A'.repeat(1024)] },
			{ fileName: 'two.txt', contents: ['B'.repeat(1024)] },
		], { action: 'drag-drop' })

		// Both files must land under the subfolder, not the current dir.
		cy.wait(['@uploadFile', '@uploadFile']).then((intercepts) => {
			const urls = intercepts.map((i) => i.request.url).sort()
			expect(urls).to.have.length(2)
			urls.forEach((url) => {
				expect(url).to.match(/\/subfolder\/(one|two)\.txt$/)
			})
		})

		cy.get('[data-cy-upload-picker] progress').should('not.be.visible')

		navigateToFolder('/subfolder')
		getRowForFile('one.txt').should('be.visible')
		getRowForFile('two.txt').should('be.visible')
	})

	it('opens the conflict picker when dropping a colliding name onto a subfolder row', () => {
		// Pre-populate the subfolder with a file the drop will collide with.
		cy.uploadContent(user, new Blob(['original']), 'text/plain', '/subfolder/collide.txt')

		// Reload so the pre-populated file lands in the store before the drop.
		// The drop handler reads filesStore.getNodesByPath first and only
		// fetches fresh contents when the cache is empty, so a stale cache
		// from the beforeEach visit would let the upload proceed without
		// triggering the conflict picker. If this ever flaps on CI, replace
		// the visit with cy.reload() + an explicit wait on store settlement.
		cy.visit('/apps/files')
		getRowForFile('subfolder').should('be.visible')

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		getRowForFile('subfolder').selectFile({
			fileName: 'collide.txt',
			contents: ['replacement '.repeat(1024)],
		}, { action: 'drag-drop' })

		// Wait for the conflict picker to appear, then assert no PUT has
		// fired yet — chained so the upload-count check happens *after* the
		// dialog is visible, enforcing the "dialog blocks upload" invariant.
		cy.findByRole('dialog').should('be.visible').then(() => {
			cy.get('@uploadFile.all').should('have.length', 0)
		})
	})
})
