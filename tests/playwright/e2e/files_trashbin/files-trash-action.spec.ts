/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { rm, uploadContent } from '../../support/utils/dav.ts'

const FILE_COUNT = 5

test.describe('files_trashbin: empty trashbin action', () => {
	test.beforeEach(async ({ page, user }) => {
		// Create FILE_COUNT files and move them all to the trash
		for (let index = 0; index < FILE_COUNT; index++) {
			await uploadContent(page.request, user, '<content>', 'text/plain', `/file${index}.txt`)
			await rm(page.request, user, `/file${index}.txt`)
		}
	})

	test('can empty trashbin', async ({ page, filesListPage }) => {
		await filesListPage.open()
		// Home holds only the default welcome file and offers no empty-trash action
		await expect(filesListPage.getRows()).toHaveCount(1)
		await expect(filesListPage.getListActionButton('empty-trash')).toHaveCount(0)

		await filesListPage.open('trashbin')
		await expect(filesListPage.getRows()).toHaveCount(FILE_COUNT)

		const emptied = page.waitForResponse((r) => r.request().method() === 'DELETE' && r.url().includes('/remote.php/dav/trashbin/'))
		await filesListPage.triggerListAction('empty-trash')

		// Confirm in the dialog
		await page.getByRole('dialog')
			.getByRole('button', { name: 'Empty deleted files' })
			.click()

		expect((await emptied).status()).toBe(204)
		await expect(filesListPage.getRows()).toHaveCount(0)
	})

	test('cancelling the empty trashbin action does not delete anything', async ({ page, filesListPage }) => {
		await filesListPage.open('trashbin')
		await expect(filesListPage.getRows()).toHaveCount(FILE_COUNT)

		await filesListPage.triggerListAction('empty-trash')

		// Cancel the dialog: no request is sent and the files remain
		await page.getByRole('dialog')
			.getByRole('button', { name: 'Cancel' })
			.click()

		await expect(filesListPage.getRows()).toHaveCount(FILE_COUNT)
	})
})
