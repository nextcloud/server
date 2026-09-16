/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { createFileDataTransfer, dropFilesOn } from '../../support/utils/drag-drop.ts'

test.describe('files: Drag and Drop', () => {
	test.beforeEach(async ({ filesListPage }) => {
		await filesListPage.open()
	})

	test('can drop a file', async ({ page, filesListPage }) => {
		const uploaded = page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().includes('/remote.php/dav/files/'))
		const dataTransfer = await createFileDataTransfer(page, [{ name: 'single-file.txt', content: 'hello '.repeat(1024) }])

		await filesListPage.getContentArea().dispatchEvent('dragover', { dataTransfer })
		await expect(filesListPage.getDropArea()).toBeVisible()

		await dropFilesOn(filesListPage.getDropArea(), dataTransfer)
		await uploaded

		await expect(filesListPage.getRowForFile('single-file.txt')).toBeVisible()
		await expect(filesListPage.getRowSizeForFile('single-file.txt')).toContainText('6 KB')
	})

	test('can drop multiple files', async ({ page, filesListPage }) => {
		const dataTransfer = await createFileDataTransfer(page, [
			{ name: 'first.txt', content: 'Hello' },
			{ name: 'second.txt', content: 'World' },
		])

		await filesListPage.getContentArea().dispatchEvent('dragover', { dataTransfer })
		await expect(filesListPage.getDropArea()).toBeVisible()

		await dropFilesOn(filesListPage.getDropArea(), dataTransfer)

		await expect(filesListPage.getRowForFile('first.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('second.txt')).toBeVisible()
	})

	test('ignores dropped folders (legacy File API)', async ({ page, filesListPage }) => {
		// A synthetic DataTransfer already uses the legacy File API path; a File
		// with the directory mime type stands in for a dropped folder and must be
		// skipped with a warning while the real files still upload.
		const dataTransfer = await createFileDataTransfer(page, [
			{ name: 'first.txt', content: 'Hello' },
			{ name: 'second.txt', content: 'World' },
			{ name: 'Foo', content: '', type: 'httpd/unix-directory' },
		])

		await filesListPage.getContentArea().dispatchEvent('dragover', { dataTransfer })
		await expect(filesListPage.getDropArea()).toBeVisible()

		await dropFilesOn(filesListPage.getDropArea(), dataTransfer)

		await expect(page.locator('.toast-warning')).toBeVisible()
		await expect(filesListPage.getRowForFile('first.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('second.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('Foo')).toHaveCount(0)
	})
})

// Regression coverage for https://github.com/nextcloud/server/issues/60139:
// per-row drops must route through the same pipeline as the main-list drop and
// upload into the target folder.
test.describe('files: Drag and Drop onto a folder row', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/subfolder')
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()
	})

	test('can drop a single file onto a subfolder row', async ({ page, filesListPage }) => {
		const uploaded = page.waitForResponse((r) => r.request().method() === 'PUT' && /\/subfolder\/dropped-into-subfolder\.txt$/.test(r.url()))
		const dataTransfer = await createFileDataTransfer(page, [{ name: 'dropped-into-subfolder.txt', content: 'hello '.repeat(1024) }])

		await dropFilesOn(filesListPage.getRowForFile('subfolder'), dataTransfer)
		await uploaded

		await filesListPage.navigateToFolder('subfolder')
		await expect(filesListPage.getRowForFile('dropped-into-subfolder.txt')).toBeVisible()
	})

	test('can drop multiple files onto a subfolder row', async ({ page, filesListPage }) => {
		const uploads = Promise.all([
			page.waitForResponse((r) => r.request().method() === 'PUT' && /\/subfolder\/one\.txt$/.test(r.url())),
			page.waitForResponse((r) => r.request().method() === 'PUT' && /\/subfolder\/two\.txt$/.test(r.url())),
		])
		const dataTransfer = await createFileDataTransfer(page, [
			{ name: 'one.txt', content: 'A'.repeat(1024) },
			{ name: 'two.txt', content: 'B'.repeat(1024) },
		])

		await dropFilesOn(filesListPage.getRowForFile('subfolder'), dataTransfer)
		await uploads

		await filesListPage.navigateToFolder('subfolder')
		await expect(filesListPage.getRowForFile('one.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('two.txt')).toBeVisible()
	})

	test('opens the conflict picker when dropping a colliding name onto a subfolder row', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, 'original', 'text/plain', '/subfolder/collide.txt')
		// Reload so the pre-populated file is in the store before the drop
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		let putFired = false
		page.on('request', (r) => {
			if (r.method() === 'PUT' && r.url().includes('/remote.php/dav/files/')) {
				putFired = true
			}
		})

		const dataTransfer = await createFileDataTransfer(page, [{ name: 'collide.txt', content: 'replacement '.repeat(1024) }])
		await dropFilesOn(filesListPage.getRowForFile('subfolder'), dataTransfer)

		// The conflict dialog blocks the upload until resolved
		await expect(page.getByRole('dialog')).toBeVisible()
		expect(putFired).toBe(false)
	})
})
