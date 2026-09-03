/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer rename action', () => {
	// The Files rename action edits the file-list row, which the viewer cannot
	// host, so the viewer renames through its own dialog instead.
	test('renames the current file from the viewer', async ({ page, filesListPage, uploadMedia, openFile, viewerPage }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		await viewerPage.runAction('Rename')

		const dialog = page.getByRole('dialog', { name: 'Rename file' })
		await expect(dialog).toBeVisible()
		const input = dialog.getByRole('textbox')
		await input.fill('renamed.jpg')
		await dialog.getByRole('button', { name: 'Rename' }).click()

		await expect(dialog).toBeHidden()
		// The header reflects the new name.
		await expect(async () => {
			expect(await viewerPage.currentName()).toBe('renamed.jpg')
		}).toPass()
	})
})
