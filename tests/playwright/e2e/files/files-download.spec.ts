/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Download, Page } from '@playwright/test'
import { readFile } from 'node:fs/promises'
import { addUser, runOcc } from '@nextcloud/e2e-test-server/docker'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { User } from '@nextcloud/e2e-test-server'
import { test, expect } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { getZipEntries } from '../../support/utils/zip.ts'

/**
 * Register the download listener before running the trigger and return the
 * resulting download. Playwright requires `waitForEvent('download')` to be
 * pending before the action that starts the download (the Cypress original
 * instead read a file off the downloads folder afterwards).
 */
async function triggerDownload(page: Page, action: () => Promise<void>): Promise<Download> {
	const downloadPromise = page.waitForEvent('download')
	await action()
	return downloadPromise
}

/**
 * Read a download's body as UTF-8 text.
 *
 * @param download The Playwright download event payload
 */
async function readDownloadText(download: Download): Promise<string> {
	const path = await download.path()
	return readFile(path, 'utf-8')
}

test.describe('Files: Download files using file actions', () => {
	test('can download file', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('file.txt', 'download'))

		expect(download.suggestedFilename()).toBe('file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})

	test('can download folder', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/subfolder')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/subfolder/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('subfolder', 'download'))

		expect(download.suggestedFilename()).toBe('subfolder.zip')
		expect(await getZipEntries(download)).toEqual([
			'subfolder/',
			'subfolder/file.txt',
		])
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	test('can download file with hash name', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/#file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('#file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('#file.txt', 'download'))

		expect(download.suggestedFilename()).toBe('#file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	test('can download file from folder with hash name', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/#folder')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/#folder/file.txt')
		await filesListPage.open()

		await filesListPage.navigateToFolder('#folder')
		// All are visible by default
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerActionForFile('file.txt', 'download'))

		expect(download.suggestedFilename()).toBe('file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})
})

test.describe('Files: Download files using default action', () => {
	test('can download file', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.getDownloadButtonForFile('file.txt').click())

		expect(download.suggestedFilename()).toBe('file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	test('can download file with hash name', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/#file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('#file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.getDownloadButtonForFile('#file.txt').click())

		expect(download.suggestedFilename()).toBe('#file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/44855
	 */
	test('can download file from folder with hash name', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/#folder')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/#folder/file.txt')
		await filesListPage.open()

		await filesListPage.navigateToFolder('#folder')
		// All are visible by default
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.getDownloadButtonForFile('file.txt').click())

		expect(download.suggestedFilename()).toBe('file.txt')
		expect(await readDownloadText(download)).toBe('<content>')
	})
})

test.describe('Files: Download files using selection', () => {
	test('can download selected files', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/subfolder')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/subfolder/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()
		await filesListPage.selectRowForFile('subfolder')

		// see that one file is selected
		await expect(page.getByText('1 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe('subfolder.zip')
		expect(await getZipEntries(download)).toEqual([
			'subfolder/',
			'subfolder/file.txt',
		])
	})

	test('can download multiple selected files', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/file.txt')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/other file.txt')
		await filesListPage.open()

		await filesListPage.selectRowForFile('file.txt')
		await filesListPage.selectRowForFile('other file.txt')

		// see that two files are selected
		await expect(page.getByText('2 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe('download.zip')
		expect(await getZipEntries(download)).toEqual([
			'file.txt',
			'other file.txt',
		])
	})

	/**
	 * Regression test of https://help.nextcloud.com/t/unable-to-download-files-on-nextcloud-when-multiple-files-selected/221327/5
	 */
	test('can download selected files with special characters', async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, '<content>', 'text/plain', '/1+1.txt')
		await uploadContent(page.request, user, '<content>', 'text/plain', '/some@other.txt')
		await filesListPage.open()

		await filesListPage.selectRowForFile('some@other.txt')
		await filesListPage.selectRowForFile('1+1.txt')

		// see that two files are selected
		await expect(page.getByText('2 selected')).toBeVisible()

		const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

		expect(download.suggestedFilename()).toBe('download.zip')
		expect(await getZipEntries(download)).toEqual([
			'1+1.txt',
			'some@other.txt',
		])
	})

	/**
	 * Regression test of https://help.nextcloud.com/t/unable-to-download-files-on-nextcloud-when-multiple-files-selected/221327/5
	 *
	 * This test does not use the shared `user` fixture: it needs an email-like
	 * uid, which `createRandomUser()` cannot produce, so it provisions its own
	 * user via the docker helper and logs in at the API level.
	 */
	test('can download selected files with email uid', async ({ page, context, filesListPage }) => {
		const randomString = (length: number) => Math.random().toString(36).slice(2, 2 + length)
		const uid = `${randomString(5)}@${randomString(3)}`
		const emailUser = new User(uid, uid, 'en')

		await addUser(emailUser)
		await login(context.request, emailUser)

		try {
			await uploadContent(page.request, emailUser, '<content>', 'text/plain', '/file.txt')
			await uploadContent(page.request, emailUser, '<content>', 'text/plain', '/other file.txt')
			await filesListPage.open()

			await filesListPage.selectRowForFile('file.txt')
			await filesListPage.selectRowForFile('other file.txt')

			// see that two files are selected
			await expect(page.getByText('2 selected')).toBeVisible()

			const download = await triggerDownload(page, () => filesListPage.triggerSelectionAction('download'))

			expect(download.suggestedFilename()).toBe('download.zip')
			expect(await getZipEntries(download)).toEqual([
				'file.txt',
				'other file.txt',
			])
		} finally {
			await runOcc(['user:delete', uid])
		}
	})
})
