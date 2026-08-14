/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { getFileContent, mkdir, uploadContent } from '../../../support/utils/dav.ts'
import { BUNDLED_PERMISSIONS, createLinkShare } from '../../../support/utils/sharing.ts'

const SHARE_NAME = 'shared'

/**
 * A file-drop share ("File request" in the share editor): visitors may upload
 * but never see what is already there.
 */
test.describe('files_sharing: Public share - file drop', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await mkdir(ownerRequest, user, `/${SHARE_NAME}`)
		await uploadContent(ownerRequest, user, 'content', 'text/plain', `/${SHARE_NAME}/foo.txt`)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, {
			permissions: BUNDLED_PERMISSIONS.FILE_DROP,
		})
		await publicShare.open(share.url)
	})

	test('cannot see the share content', async ({ user, ownerRequest, publicShare, filesListPage }) => {
		await expect(publicShare.fileDropDescription(SHARE_NAME)).toBeVisible()

		// The file is there …
		expect(await getFileContent(ownerRequest, user, `/${SHARE_NAME}/foo.txt`)).toBe('content')
		// … but never listed for the visitor
		await expect(filesListPage.getRowForFile('foo.txt')).toHaveCount(0)
	})

	test('offers uploading as the only action of the new-content menu', async ({ page, publicShare }) => {
		await expect(publicShare.fileDropDescription(SHARE_NAME)).toBeVisible()

		await page.getByRole('button', { name: 'New' }).click()

		const menu = page.getByRole('menu')
		await expect(menu.getByRole('menuitem')).toHaveCount(2)
		await expect(menu.getByRole('menuitem', { name: 'Upload files' })).toBeVisible()
		await expect(menu.getByRole('menuitem', { name: 'Upload folders' })).toBeVisible()
	})

	test('offers the same options on the dedicated upload button', async ({ page, publicShare }) => {
		await expect(publicShare.fileDropDescription(SHARE_NAME)).toBeVisible()

		await page.getByRole('button', { name: 'Upload', exact: true }).click()

		const menu = page.getByRole('menu')
		await expect(menu.getByRole('menuitem')).toHaveCount(2)
		await expect(menu.getByRole('menuitem', { name: 'Upload files' })).toBeVisible()
		await expect(menu.getByRole('menuitem', { name: 'Upload folders' })).toBeVisible()
	})

	test('can upload files and reports the progress', async ({ page, user, ownerRequest, publicShare }) => {
		await expect(publicShare.fileDropDescription(SHARE_NAME)).toBeVisible()

		// Hold the second upload's *response* back so the progress bar can be
		// observed mid-flight: the bytes are sent (which is what progress counts)
		// but the upload is not finished yet. Delaying the request instead would
		// stall the transfer and never move the bar.
		const { promise: held, resolve: release } = Promise.withResolvers<void>()
		await page.route(/\/public\.php\/dav\/files\//, async (route) => {
			if (route.request().url().includes('first.txt')) {
				await route.continue()
				return
			}
			const response = await route.fetch()
			await held
			await route.fulfill({ response })
		})

		await page.getByRole('button', { name: 'Upload', exact: true }).click()
		await publicShare.uploadFiles('Upload files', [
			{ name: 'first.txt', mimeType: 'text/plain', buffer: Buffer.from('8 bytes!') },
			{ name: 'second.md', mimeType: 'text/markdown', buffer: Buffer.from('x'.repeat(128)) },
		])

		// While the second file is still in flight the bar reports the first one as
		// done — a partial value, not a finished upload. The exact percentage is
		// the uploader's own byte accounting, so only the range is asserted.
		const progress = page.getByRole('progressbar')
		await expect(progress).toBeVisible()
		await expect.poll(async () => Number(await progress.getAttribute('value') ?? 0), {
			message: 'the progress bar should report partial progress',
		}).toBeGreaterThan(0)
		expect(Number(await progress.getAttribute('value'))).toBeLessThan(100)

		release()

		await expect.poll(() => getFileContent(ownerRequest, user, `/${SHARE_NAME}/first.txt`).catch(() => ''))
			.toBe('8 bytes!')
		await expect.poll(() => getFileContent(ownerRequest, user, `/${SHARE_NAME}/second.md`).catch(() => ''))
			.toBe('x'.repeat(128))
	})
})
