/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test.describe('Files', () => {
	test('Login with a user and open the files app', async ({ filesListPage }) => {
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('welcome.txt')).toBeVisible()
	})

	test('Opens a valid file shows it as active', async ({ page, user, filesListPage }) => {
		const fileId = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/original.txt')

		await page.goto(`apps/files/files/${fileId}`)

		const row = filesListPage.getRowForFileId(Number(fileId))
		await expect(row).toBeVisible()
		await expect(row).toHaveAttribute('data-cy-files-list-row-name', 'original.txt')
		await expect(row).toBeActiveRow()
		await expect(page.getByText('The file could not be found')).toHaveCount(0)
	})

	test('Opens a valid folder shows its content', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/folder')

		await page.goto('apps/files/files?dir=/folder')
		await filesListPage.waitForList()

		await expect(filesListPage.getBreadcrumbs()).toContainText('folder')
		await expect(page.getByText('The file could not be found')).toHaveCount(0)
	})

	test('Opens an unknown file show an error', async ({ page }) => {
		await page.goto('apps/files/files/123456')

		// The error toast is shown once the (failing) PROPFIND resolves
		await expect(page.getByText('The file could not be found')).toBeVisible()
	})
})
