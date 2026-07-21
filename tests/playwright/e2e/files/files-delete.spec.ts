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

		// One listener per file, registered before triggering the action. Each
		// predicate matches its own file's URL — with identical predicates all
		// listeners can resolve to the same first response, so distinct DELETE
		// requests would never actually be verified.
		const deleteResponses = Promise.all(files.map((file) => page.waitForResponse(
			(r) => r.url().includes(`/remote.php/dav/files/${user.userId}/root/${file}`) && r.request().method() === 'DELETE',
			{ timeout: 15000 },
		)))

		await filesListPage.selectAll()
		await filesListPage.triggerSelectionAction('delete')

		await page.getByRole('dialog', { name: 'Confirm deletion' })
			.getByRole('button', { name: 'Delete files' })
			.click()

		await deleteResponses

		// Assert the user-visible end state (rows gone) rather than raw response
		// codes — a one-shot status check flakes on transient DAV lock responses.
		for (const file of files) {
			await expect(filesListPage.getRowForFile(file)).toHaveCount(0)
		}
	})
})
