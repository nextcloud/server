/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, rm, uploadContent } from '../../support/utils/dav.ts'

/**
 * Whether the browser cache holds a listing for the folder
 *
 * @param page - The page of the logged-in user
 * @param uid - The user id
 * @param path - The folder path in the files view
 */
async function hasCachedListing(page: Page, uid: string, path: string): Promise<boolean> {
	return page.evaluate(([uid, key]) => new Promise<boolean>((resolve) => {
		const open = indexedDB.open(`nextcloud_files_folders_${uid}`)
		open.onerror = () => resolve(false)
		open.onsuccess = () => {
			const db = open.result
			if (!db.objectStoreNames.contains('listings')) {
				db.close()
				return resolve(false)
			}
			const get = db.transaction('listings').objectStore('listings').get(key)
			get.onsuccess = () => {
				db.close()
				resolve(get.result !== undefined)
			}
		}
	}), [uid, `files:${path}`] as const)
}

test.describe('Files: cached folder listings', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/cached')
		await uploadContent(page.request, user, 'a', 'text/plain', '/cached/a.txt')
		await page.goto('apps/files/files?dir=/cached')
		await filesListPage.waitForList()
		await expect(filesListPage.getRowForFile('a.txt')).toBeVisible()
		await expect.poll(() => hasCachedListing(page, user.userId, '/cached')).toBe(true)
	})

	test('shows the last listing while the folder is reloading', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, 'b', 'text/plain', '/cached/b.txt')

		// Hold the folder PROPFIND until the cached listing has been checked
		let release!: () => void
		const held = new Promise<void>((resolve) => {
			release = resolve
		})
		await page.route(/remote\.php\/dav\/files\//, async (route) => {
			if (route.request().method() === 'PROPFIND') {
				await held
			}
			await route.continue()
		})

		await page.reload()
		await expect(filesListPage.getRowForFile('a.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('b.txt')).toHaveCount(0)
		await expect(page.getByRole('img', { name: 'File list is reloading' })).toBeVisible()

		release()
		await expect(filesListPage.getRowForFile('b.txt')).toBeVisible()
		await page.unroute(/remote\.php\/dav\/files\//)
	})

	test('forgets the listing of a deleted folder', async ({ page, user, filesListPage }) => {
		await rm(page.request, user, '/cached')

		await page.reload()
		await expect(page.locator('[data-cy-files-content-error]')).toBeVisible()
		await expect(filesListPage.getRowForFile('a.txt')).toHaveCount(0)
		await expect.poll(() => hasCachedListing(page, user.userId, '/cached')).toBe(false)
	})
})
