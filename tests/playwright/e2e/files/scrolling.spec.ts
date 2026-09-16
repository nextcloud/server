/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '../../support/fixtures/files-page.ts'
import { rm, uploadContent } from '../../support/utils/dav.ts'
import { fitFilesListToRows, isFullyInViewport } from '../../support/utils/viewport.ts'

/**
 * Seed `count` empty files named `1.txt … {count}.txt` for the given user and
 * return their file ids keyed by number. Each test seeds its own user, so the
 * data is isolated and the suite is safe to run in parallel.
 */
async function seedNumberedFiles(request: APIRequestContext, user: User, count: number): Promise<Record<number, number>> {
	// Drop the default file so only the numbered ones are present
	await rm(request, user, '/welcome.txt')
	const fileIds: Record<number, number> = {}
	for (let i = 1; i <= count; i++) {
		fileIds[i] = Number(await uploadContent(request, user, Buffer.alloc(0), 'text/plain', `/${i}.txt`))
	}
	return fileIds
}

test.describe('Files: Scrolling to the selected file (list view)', () => {
	let fileIds: Record<number, number>

	test.beforeEach(async ({ page, user, filesListPage }) => {
		fileIds = await seedNumberedFiles(page.request, user, 10)
		await filesListPage.open()
		// Fit exactly six rows so four of the ten files are virtualized off-screen
		await fitFilesListToRows(page, 6)
	})

	test('shows the first rows and keeps the rest off-screen', async ({ page, filesListPage }) => {
		await page.goto(`apps/files/files/${fileIds[1]}`)
		await filesListPage.waitForList()

		await expect(filesListPage.getRowForFile('1.txt')).toBeVisible()
		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('1.txt'))).toBe(true)
		// A file well past the fold exists but is not on screen
		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('10.txt'))).toBe(false)
	})

	test('scrolls a file below the fold into view', async ({ page, filesListPage }) => {
		await page.goto(`apps/files/files/${fileIds[8]}`)
		await filesListPage.waitForList()

		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('8.txt'))).toBe(true)
	})

	test('scrolls to the last page and reveals the footer', async ({ page, filesListPage }) => {
		await page.goto(`apps/files/files/${fileIds[10]}`)
		await filesListPage.waitForList()

		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('10.txt'))).toBe(true)
		// The last page cannot scroll further, so the summary footer comes into view
		const footer = filesListPage.getFilesList().locator('tfoot')
		await expect(footer).toContainText('10 files')
		await expect.poll(() => isFullyInViewport(footer)).toBe(true)
	})
})

test.describe('Files: Scrolling to the selected file (grid view)', () => {
	let fileIds: Record<number, number>

	test.beforeEach(async ({ page, user, filesListPage }) => {
		fileIds = await seedNumberedFiles(page.request, user, 12)
		await filesListPage.open()
		await filesListPage.enableGridView()
		// Fit exactly three grid rows so the last row is virtualized off-screen
		await fitFilesListToRows(page, 3, true)
	})

	test('shows the first grid rows and keeps the last off-screen', async ({ page, filesListPage }) => {
		await page.goto(`apps/files/files/${fileIds[1]}`)
		await filesListPage.waitForList()

		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('1.txt'))).toBe(true)
		// A file in the last grid row exists but is not on screen
		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('12.txt'))).toBe(false)
	})

	test('scrolls the last grid row into view', async ({ page, filesListPage }) => {
		await page.goto(`apps/files/files/${fileIds[12]}`)
		await filesListPage.waitForList()

		await expect.poll(() => isFullyInViewport(filesListPage.getRowForFile('12.txt'))).toBe(true)
	})
})
