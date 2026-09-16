/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test.describe('Files: Delete', () => {
	test('can delete a file', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await filesListPage.open()

		const row = filesListPage.getRowForFile('file.txt')
		await expect(row).toBeVisible()
		// Preview must finish loading before delete — a loading preview can lock the file
		await expect(row.locator('.files-list__row-icon-preview--loaded')).toBeVisible()

		const deleteResponse = page.waitForResponse(
			(r) => r.url().includes('/remote.php/dav/files/') && r.request().method() === 'DELETE',
			{ timeout: 10000 },
		)
		await filesListPage.triggerActionForFile('file.txt', 'delete')
		expect((await deleteResponse).status()).toBe(204)
	})

	test('can delete multiple files', async ({ page, user, filesListPage }) => {
		const files = Array.from({ length: 5 }, (_, i) => `file${i}.txt`)
		await mkdir(page.request, user, '/root')
		for (const file of files) {
			await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', `/root/${file}`)
		}
		await filesListPage.open()
		await filesListPage.navigateToFolder('root')

		// All 5 preview thumbnails must finish loading before we delete
		await expect(page.locator('.files-list__row-icon-preview--loaded')).toHaveCount(5)

		// Retry the bulk delete until the folder is empty. A transient DAV lock
		// (423) on a freshly-uploaded file makes its DELETE fail and the app keeps
		// the row, so a single pass can leave a file behind. Re-selecting and
		// re-deleting whatever remains converges on the empty end state without
		// depending on every concurrent DELETE succeeding on the first try.
		await expect(async () => {
			await filesListPage.selectAll()
			await filesListPage.triggerSelectionAction('delete')
			await page.getByRole('dialog', { name: 'Confirm deletion' })
				.getByRole('button', { name: 'Delete files' })
				.click()

			await expect(filesListPage.getRows()).toHaveCount(0)
		}).toPass({ timeout: 30_000 })
	})
})
