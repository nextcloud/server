/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, rm } from '../../support/utils/dav.ts'

test.describe('Files hotkey handling', () => {
	// Each test seeds its own user with exactly two folders (abcd, zyx) and no
	// welcome.txt, so the keyboard-navigation and delete assertions are isolated
	// and parallel-safe.
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/abcd')
		await mkdir(page.request, user, '/zyx')
		await rm(page.request, user, '/welcome.txt')
		await filesListPage.open()
	})

	test('Pressing "arrow down" should go to first file', async ({ page, filesListPage }) => {
		await filesListPage.getFilesList().press('ArrowDown')

		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)
		const fileId = Number(new URL(page.url()).pathname.split('/').at(-1))
		await expect(filesListPage.getRowForFileId(fileId)).toHaveAttribute('data-cy-files-list-row-name', 'abcd')
	})

	test('Pressing "arrow up" should go to last file', async ({ page, filesListPage }) => {
		await filesListPage.getFilesList().press('ArrowUp')

		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)
		const fileId = Number(new URL(page.url()).pathname.split('/').at(-1))
		await expect(filesListPage.getRowForFileId(fileId)).toHaveAttribute('data-cy-files-list-row-name', 'zyx')
	})

	test('Pressing D should open the sidebar once', async ({ page, filesListPage, filesSidebar }) => {
		await filesListPage.getFilesList().press('ArrowDown')
		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)

		await filesListPage.getFilesList().press('d')

		await expect(filesSidebar.sidebar()).toBeVisible()
	})

	test('Pressing F2 should rename the file', async ({ page, filesListPage }) => {
		await filesListPage.getFilesList().press('ArrowDown')
		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)

		await filesListPage.getFilesList().press('F2')

		await expect(filesListPage.getRenameInputForFolder('abcd')).toBeVisible()
	})

	test('Pressing S should toggle favorite', async ({ page, filesListPage }) => {
		await filesListPage.getFilesList().press('ArrowDown')
		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)

		await filesListPage.getFilesList().press('s')
		await expect(filesListPage.getFavoriteIconForFile('abcd')).toBeVisible()

		await filesListPage.getFilesList().press('s')
		await expect(filesListPage.getFavoriteIconForFile('abcd')).toHaveCount(0)
	})

	test('Pressing DELETE should delete the folder', async ({ page, filesListPage }) => {
		await filesListPage.getFilesList().press('ArrowDown')
		await expect(page).toHaveURL(/\/apps\/files\/files\/\d+/)
		await expect(filesListPage.getRows()).toHaveCount(2)

		await filesListPage.getFilesList().press('Delete')

		await expect(filesListPage.getRows()).toHaveCount(1)
	})
})
