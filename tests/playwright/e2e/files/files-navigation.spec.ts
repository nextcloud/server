/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '../../support/fixtures/files-page.ts'
import { mkdir } from '../../support/utils/dav.ts'

test.describe('Files: Navigation', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/foo')
		await mkdir(page.request, user, '/foo/bar')
		await mkdir(page.request, user, '/foo/bar/baz')
		await filesListPage.open()
	})

	test('shows root folder and can navigate to a deeply nested folder', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo')).toBeVisible()
		await filesListPage.navigateToFolder('foo/bar/baz')

		// deepest folder is empty — no file rows rendered
		await expect(page.locator('[data-cy-files-list-row-fileid]')).toHaveCount(0)
	})

	test('highlights the previous folder when navigating back and forward', async ({ page, filesListPage }) => {
		await filesListPage.navigateToFolder('foo/bar/baz')
		await expect(page.locator('[data-cy-files-list-row-fileid]')).toHaveCount(0)

		// Navigate back through each level — the folder we came from is highlighted
		await page.goBack()
		await expect(filesListPage.getRowForFile('baz')).toBeVisible()
		await expect(filesListPage.getRowForFile('baz')).toBeActiveRow()

		await page.goBack()
		await expect(filesListPage.getRowForFile('bar')).toBeVisible()
		await expect(filesListPage.getRowForFile('bar')).toBeActiveRow()

		await page.goBack()
		await expect(filesListPage.getRowForFile('foo')).toBeVisible()
		await expect(filesListPage.getRowForFile('foo')).toBeActiveRow()

		// Navigate forward — the folder we re-entered is highlighted
		await page.goForward()
		await expect(filesListPage.getRowForFile('bar')).toBeVisible()
		await expect(filesListPage.getRowForFile('bar')).toBeActiveRow()

		await page.goForward()
		await expect(filesListPage.getRowForFile('baz')).toBeVisible()
		await expect(filesListPage.getRowForFile('baz')).toBeActiveRow()
	})
})
