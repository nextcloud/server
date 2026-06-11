/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '../../support/fixtures/systemtags-files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { clearTags } from '../../support/utils/systemtags.ts'

test.describe('Systemtags: Files view', () => {
	test.afterAll(async () => await clearTags())

	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/folder')
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await filesListPage.open()
	})

	test('See first assigned tag in the file list', async ({ page, filesListPage }) => {
		const tag = crypto.randomUUID()

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		// Assign tag to the folder via the Manage Tags picker
		await filesListPage.openTagPickerForFile('folder')
		await filesListPage.createNewTagInPicker(tag)
		await filesListPage.applyTagPicker()

		// Navigate to the tags view
		await page.goto('apps/files/tags')
		await expect(filesListPage.getRowForFile('folder')).not.toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).not.toBeVisible()

		// The tag should appear as a cell in the tags list view
		await expect(page.getByRole('cell', { name: tag })).toBeVisible()
		await page.getByRole('cell', { name: tag }).click()

		// Only the folder (tagged) is shown; file.txt (untagged) is absent
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).not.toBeVisible()
	})
})
