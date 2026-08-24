/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
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

	test('show loading indicator when navigating', async ({ page, filesListPage }) => {
		await filesListPage.navigateToFolder('foo/bar/baz')
		await expect(page.locator('[data-cy-files-list-row-fileid]')).toHaveCount(0)

		// Block the PROPFIND request to simulate a slow network and show the loading indicator
		let releaseNavigation!: () => void
		const navigationBlocked = new Promise<void>((resolve) => {
			releaseNavigation = resolve
		})
		let blockedNavigationRequest = false
		await page.route(/remote\.php\/dav\/files\//, async (route) => {
			const request = route.request()
			if (!blockedNavigationRequest && request.method() === 'PROPFIND' && request.url().includes('/foo/bar')) {
				blockedNavigationRequest = true
				await navigationBlocked
			}

			await route.continue()
		})

		// Navigate back to the parent folder — the PROPFIND request will be blocked
		const navigationResponse = page.waitForResponse((response) => response.url().includes('/remote.php/dav/files/')
			&& response.request().method() === 'PROPFIND'
			&& response.url().includes('/foo/bar'))

		await page.goBack()

		await expect.poll(() => new URL(page.url()).searchParams.get('dir')).toBe('/foo/bar')
		await expect(page.locator('[data-cy-files-loading]')).toBeVisible()
		expect(blockedNavigationRequest).toBe(true)

		// Release the blocked navigation request and wait for it to complete
		releaseNavigation()
		await navigationResponse
		await page.unroute(/remote\.php\/dav\/files\//)

		// Wait for the loading indicator to disappear and the folder to be rendered
		await expect(page.locator('[data-cy-files-loading]')).not.toBeVisible()
		await expect(filesListPage.getRowForFile('baz')).toBeVisible()
		await expect(filesListPage.getRowForFile('baz')).toBeActiveRow()
	})
})
