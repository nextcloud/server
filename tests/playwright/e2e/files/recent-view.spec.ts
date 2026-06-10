/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'

test.describe('Files: Recent view', () => {
	test.beforeEach(async ({ page, user }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
	})

	test('shows a recently created file in the recent view', async ({ filesListPage }) => {
		await filesListPage.open('recent')
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
	})

	/**
	 * Regression: the recent view loaded files with an invalid source, so the
	 * delete action failed. Deleting from the recent view must work and remove
	 * the file everywhere.
	 */
	test('can delete a file from the recent view', async ({ page, filesListPage }) => {
		await filesListPage.open('recent')
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const deleted = page.waitForResponse(
			(r) => r.request().method() === 'DELETE' && r.url().includes('/remote.php/dav/files/'),
		)
		await filesListPage.triggerActionForFile('file.txt', 'delete')
		await deleted

		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)

		// Gone from the default view too
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)
	})
})
