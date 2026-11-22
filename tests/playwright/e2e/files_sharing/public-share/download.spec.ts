/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Download, Page } from '@playwright/test'

import { readFile } from 'node:fs/promises'
import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { uploadContent } from '../../../support/utils/dav.ts'
import { createLinkShare, seedSharedFolder } from '../../../support/utils/sharing.ts'
import { getZipEntries } from '../../../support/utils/zip.ts'

const SHARE_NAME = 'a-folder-share'

/**
 * Register the download listener before the action that triggers it — Playwright
 * needs `waitForEvent('download')` to be pending first.
 */
async function triggerDownload(page: Page, action: () => Promise<void>): Promise<Download> {
	const downloadPromise = page.waitForEvent('download')
	await action()
	return downloadPromise
}

/** Read a download's body as UTF-8 text. */
async function readDownloadText(download: Download): Promise<string> {
	return readFile(await download.path(), 'utf-8')
}

test.describe('files_sharing: Public share - downloading a shared file', () => {
	/**
	 * A file share behaves like a folder share except for the download: its source
	 * is the share token, so the displayed name comes from the share itself.
	 */
	test('can download the shared file', async ({ page, user, ownerRequest, publicShare, filesListPage }) => {
		const fileId = await uploadContent(ownerRequest, user, '<content>foo</content>', 'text/plain', '/file.txt')
		const share = await createLinkShare(ownerRequest, '/file.txt')
		await publicShare.open(share.url)

		const row = filesListPage.getRowForFileId(Number(fileId))
		await expect(row).toBeVisible()
		// The extension is rendered in its own element, so allow whitespace in between
		await expect(row.locator('[data-cy-files-list-row-name]')).toHaveText(/file\s*\.txt/)

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFileId(Number(fileId), 'download'))

		expect(download.suggestedFilename()).toBe('file.txt')
		expect(await readDownloadText(download)).toBe('<content>foo</content>')
	})
})

test.describe('files_sharing: Public share - downloading from a shared folder', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`)
		await publicShare.open(share.url)
	})

	test('can download everything by selecting all', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await filesListPage.selectAll()
		await expect(page.getByText('2 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe(`${SHARE_NAME}.zip`)
		expect(await getZipEntries(download)).toEqual([
			'foo.txt',
			'subfolder/',
			'subfolder/bar.txt',
		])
	})

	test('can download a selected folder', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		await filesListPage.selectRowForFile('subfolder')
		await expect(page.getByText('1 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe('subfolder.zip')
		expect(await getZipEntries(download)).toEqual([
			'subfolder/',
			'subfolder/bar.txt',
		])
	})

	test('can download a folder by its row action', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('subfolder', 'download'))

		expect(download.suggestedFilename()).toBe('subfolder.zip')
		expect(await getZipEntries(download)).toEqual([
			'subfolder/',
			'subfolder/bar.txt',
		])
	})

	test('can download a file by its row action', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('foo.txt', 'download'))

		expect(download.suggestedFilename()).toBe('foo.txt')
		expect(await readDownloadText(download)).toBe('<content>foo</content>')
	})

	test('can download a selected file', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await filesListPage.selectRowForFile('foo.txt')
		await expect(page.getByText('1 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe('foo.txt')
		expect(await readDownloadText(download)).toBe('<content>foo</content>')
	})
})
