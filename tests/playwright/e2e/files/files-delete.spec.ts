/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '../../support/fixtures/files-page.ts'
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
		await mkdir(page.request, user, '/root')
		for (let i = 0; i < 5; i++) {
			await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', `/root/file${i}.txt`)
		}
		await filesListPage.open()
		await filesListPage.navigateToFolder('root')

		// All 5 preview thumbnails must finish loading before we delete
		await expect(page.locator('.files-list__row-icon-preview--loaded')).toHaveCount(5)

		// Set up listeners for all 5 DELETE responses before triggering the action
		const deleteResponses = Promise.all(
			Array.from({ length: 5 }, () =>
				page.waitForResponse(
					(r) => r.url().includes(`/remote.php/dav/files/${user.userId}/root/`) && r.request().method() === 'DELETE',
					{ timeout: 15000 },
				),
			),
		)

		await filesListPage.selectAll()
		await filesListPage.triggerSelectionAction('delete')

		await page.getByRole('dialog', { name: 'Confirm deletion' })
			.getByRole('button', { name: 'Delete files' })
			.click()

		const responses = await deleteResponses
		for (const response of responses) {
			expect(response.status()).toBe(204)
		}
	})
})
