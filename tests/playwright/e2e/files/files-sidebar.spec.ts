/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test.describe('Files: Sidebar', () => {
	let fileId: number

	test.beforeEach(async ({ user, page, filesListPage }) => {
		await mkdir(page.request, user, '/folder')
		fileId = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file')
		await filesListPage.open()
	})

	test('opens the sidebar', async ({ filesListPage, filesSidebar }) => {
		await expect(filesListPage.getRowForFile('file')).toBeVisible()

		await filesListPage.triggerActionForFile('file', 'details')

		await expect(filesSidebar.sidebar()).toBeVisible()
		await expect(filesSidebar.heading('file')).toBeVisible()
	})

	test('changes the current fileid', async ({ page, filesListPage, filesSidebar }) => {
		await expect(filesListPage.getRowForFile('file')).toBeVisible()

		await filesListPage.triggerActionForFile('file', 'details')

		await expect(filesSidebar.sidebar()).toBeVisible()
		await expect(page).toHaveURL(new RegExp(`apps/files/files/${fileId}`))
	})

	test('changes the sidebar content on other file', async ({ filesListPage, filesSidebar }) => {
		await expect(filesListPage.getRowForFile('file')).toBeVisible()

		await filesListPage.triggerActionForFile('file', 'details')

		await expect(filesSidebar.sidebar()).toBeVisible()
		// Wait for the first file's heading to be stable before switching
		await expect(filesSidebar.heading('file')).toBeVisible()

		await filesListPage.triggerActionForFile('folder', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()
		await expect(filesSidebar.heading('folder')).toBeVisible()
	})

	test('closes the sidebar on navigation', async ({ filesListPage, filesSidebar }) => {
		await expect(filesListPage.getRowForFile('file')).toBeVisible()
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()

		// Open the sidebar
		await filesListPage.triggerActionForFile('file', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()

		// Navigate into the folder — sidebar should close
		await filesListPage.navigateToFolder('folder')
		await expect(filesSidebar.sidebar()).not.toBeVisible()
	})

	test('closes the sidebar on delete', async ({ page, filesListPage, filesSidebar, user }) => {
		await expect(filesListPage.getRowForFile('file')).toBeVisible()

		// Open the sidebar
		await filesListPage.triggerActionForFile('file', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()
		// Wait for the sidebar to be fully rendered before deleting
		await expect(filesSidebar.heading('file')).toBeVisible()

		const deleteResponse = page.waitForResponse(
			(response) => response.url().includes(`/remote.php/dav/files/${user.userId}/file`)
				&& response.request().method() === 'DELETE',
			{ timeout: 10000 },
		)

		await filesListPage.triggerActionForFile('file', 'delete')
		await deleteResponse

		await expect(filesSidebar.sidebar()).not.toBeVisible()
	})

	test('changes the fileid on delete', async ({ page, filesListPage, filesSidebar, user }) => {
		const otherFileId = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/folder/other')

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await filesListPage.navigateToFolder('folder')
		await expect(filesListPage.getRowForFile('other')).toBeVisible()

		// Open the sidebar for the inner file
		await filesListPage.triggerActionForFile('other', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()
		await expect(page).toHaveURL(new RegExp(`apps/files/files/${otherFileId}`))
		// Wait for the sidebar to be fully rendered before deleting
		await expect(filesSidebar.heading('other')).toBeVisible()

		const deleteResponse = page.waitForResponse(
			(response) => response.url().includes(`/remote.php/dav/files/${user.userId}/folder/other`)
				&& response.request().method() === 'DELETE',
			{ timeout: 10000 },
		)

		await filesListPage.triggerActionForFile('other', 'delete')
		await deleteResponse

		await expect(filesSidebar.sidebar()).not.toBeVisible()
		await expect(page).not.toHaveURL(new RegExp(`apps/files/files/${otherFileId}`))
	})
})
