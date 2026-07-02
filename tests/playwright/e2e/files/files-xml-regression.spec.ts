/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'

/**
 * Regression: https://github.com/nextcloud/server/issues/43331
 * Files whose names contain XML entities (e.g. "&amp;.txt") were wrongly
 * displayed and could no longer be renamed or deleted.
 */
test.describe('Files: XML entities in file names', () => {
	test('renames a file to a name with XML entities and keeps it after reload', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/and.txt')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('and.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('and.txt')
		await expect(input).toBeVisible()

		const renamed = page.waitForResponse((r) => r.request().method() === 'MOVE' && r.url().includes('/remote.php/dav/files/'))
		await input.fill('&amp;.txt')
		await input.press('Enter')
		await renamed

		// The literal name is kept, not decoded to "&.txt"
		await expect(filesListPage.getRowForFile('&amp;.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('&.txt')).toHaveCount(0)

		await page.reload()
		await expect(filesListPage.getRowForFile('&amp;.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('&.txt')).toHaveCount(0)
	})

	test('can delete a file whose name contains XML entities', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/&amp;.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('&amp;.txt')).toBeVisible()

		const deleted = page.waitForResponse((r) => r.request().method() === 'DELETE' && r.url().includes('/remote.php/dav/files/'))
		await filesListPage.triggerActionForFile('&amp;.txt', 'delete')
		await deleted

		await expect(filesListPage.getRowForFile('&amp;.txt')).toHaveCount(0)

		await page.reload()
		await expect(filesListPage.getRowForFile('&amp;.txt')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('&.txt')).toHaveCount(0)
	})
})
