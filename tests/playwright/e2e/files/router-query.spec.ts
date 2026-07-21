/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { test as baseTest, expect } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

type SeededIds = { imageId: number, folderId: number, archiveId: number }

// Seed an image (known viewer type), a folder and an archive (unknown type).
// The `viewer` app is enabled by default in the test server.
const test = baseTest.extend<{ ids: SeededIds }>({
	ids: async ({ page, user }, use) => {
		const image = readFileSync(resolve(process.cwd(), 'cypress/fixtures/image.jpg'))
		const imageId = Number(await uploadContent(page.request, user, image, 'image/jpeg', '/image.jpg'))
		const folderId = Number(await mkdir(page.request, user, '/folder'))
		const archiveId = Number(await uploadContent(page.request, user, Buffer.alloc(0), 'application/zstd', '/archive.zst'))
		await use({ imageId, folderId, archiveId })
	},
})

/** Fails the test if a browser download starts during its lifetime. */
function assertNoDownload(page: Page): void {
	page.on('download', (download) => {
		throw new Error(`Unexpected download started: ${download.suggestedFilename()}`)
	})
}

test.describe('Check router query flags', () => {
	test.describe('"opendetails"', () => {
		for (const { label, key, name } of [
			{ label: 'known file type', key: 'imageId' as const, name: 'image.jpg' },
			{ label: 'unknown file type', key: 'archiveId' as const, name: 'archive.zst' },
			{ label: 'folder', key: 'folderId' as const, name: 'folder' },
		]) {
			test(`open details for ${label}`, async ({ page, ids, filesSidebar }) => {
				assertNoDownload(page)
				await page.goto(`apps/files/files/${ids[key]}?opendetails`)

				// Sidebar opens for the node …
				await expect(filesSidebar.sidebar()).toBeVisible()
				await expect(filesSidebar.heading(name)).toBeVisible()
				// … but the viewer does not, and nothing is downloaded
				await expect(page.getByRole('dialog', { name })).toHaveCount(0)
			})
		}
	})

	test.describe('"openfile"', () => {
		const viewerShowsImage = async (page: Page, imageId: number) => {
			const dialog = page.getByRole('dialog', { name: 'image.jpg' })
			await expect(dialog).toBeVisible()
			await expect(dialog.locator(`img[src*="fileId=${imageId}"]`)).toBeVisible()
		}

		test('opens files with default action', async ({ page, ids }) => {
			await page.goto(`apps/files/files/${ids.imageId}?openfile`)
			await viewerShowsImage(page, ids.imageId)
		})

		test('opens files with default action using explicit query state', async ({ page, ids }) => {
			await page.goto(`apps/files/files/${ids.imageId}?openfile=true`)
			await viewerShowsImage(page, ids.imageId)
		})

		test('does not open files with default action when using explicit `false`', async ({ page, ids, filesListPage }) => {
			await page.goto(`apps/files/files/${ids.imageId}?openfile=false`)

			await expect(filesListPage.getRowForFileId(ids.imageId)).toBeActiveRow()
			await expect(page.getByRole('dialog', { name: 'image.jpg' })).toHaveCount(0)
		})

		test('does not open folders but shows details', async ({ page, ids, filesSidebar, filesListPage }) => {
			await page.goto(`apps/files/files/${ids.folderId}?openfile`)

			// The query is rewritten to opendetails
			await expect(page).toHaveURL(/[?&]opendetails(&|=|$)/)
			await expect(page).not.toHaveURL(/openfile/)

			await expect(filesSidebar.sidebar()).toBeVisible()
			await expect(filesSidebar.heading('folder')).toBeVisible()
			// the folder was not entered
			await expect(filesListPage.getRowForFileId(ids.imageId)).toBeVisible()
		})

		test('does not open unknown file types but shows details', async ({ page, ids, filesSidebar }) => {
			assertNoDownload(page)
			await page.goto(`apps/files/files/${ids.archiveId}?openfile`)

			await expect(page).toHaveURL(/[?&]opendetails(&|=|$)/)
			await expect(page).not.toHaveURL(/openfile/)

			await expect(filesSidebar.sidebar()).toBeVisible()
			await expect(filesSidebar.heading('archive.zst')).toBeVisible()
		})
	})
})
