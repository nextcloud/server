/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { pasteImages, PNG_CONTENT } from '../../../support/utils/clipboard.ts'
import { DAV_FILES_ENDPOINT, getFileContent, mkdir, uploadContent } from '../../../support/utils/dav.ts'
import { BUNDLED_PERMISSIONS, createLinkShare } from '../../../support/utils/sharing.ts'

test.describe('files_sharing: Public share - clipboard image upload', () => {
	test('can paste an image into a writable public share', async ({
		page,
		user,
		ownerRequest,
		publicShare,
		filesListPage,
	}) => {
		await mkdir(ownerRequest, user, '/writable')

		const share = await createLinkShare(ownerRequest, '/writable', {
			permissions: BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE,
		})

		await publicShare.open(share.url)
		await filesListPage.waitForList()
		await filesListPage.waitForListLoaded()

		const uploaded = page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith('/public-image.png'))

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['public-image.png'],
		)

		expect(handled).toBe(true)
		expect((await uploaded).ok()).toBe(true)

		await expect(filesListPage.getRowForFile('public-image.png')).toBeVisible()

		// Verify the image was stored in the owner's folder without modification.
		const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/writable/public-image.png`)

		expect(storedFile.ok()).toBe(true)
		expect(await storedFile.body()).toEqual(Buffer.from(PNG_CONTENT, 'base64'))
	})

	test('does not upload clipboard images into a read-only public share', async ({
		page,
		user,
		ownerRequest,
		publicShare,
		filesListPage,
	}) => {
		await mkdir(ownerRequest, user, '/read-only')

		await uploadContent(
			ownerRequest,
			user,
			'existing content',
			'text/plain',
			'/read-only/existing.txt',
		)

		const share = await createLinkShare(ownerRequest, '/read-only', {
			permissions: BUNDLED_PERMISSIONS.READ_ONLY,
		})

		await publicShare.open(share.url)
		await filesListPage.waitForList()
		await filesListPage.waitForListLoaded()

		await expect(filesListPage.getRowForFile('existing.txt')).toBeVisible()
		await expect(page.locator('[data-cy-upload-picker]')).toHaveCount(0)

		let putFired = false

		page.on('request', (request) => {
			if (request.method() === 'PUT' && DAV_FILES_ENDPOINT.test(request.url())) {
				putFired = true
			}
		})

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['read-only.png'],
		)

		expect(handled).toBe(false)

		await expect(filesListPage.getRowForFile('read-only.png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('existing.txt')).toBeVisible()

		const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/read-only/read-only.png`)

		expect(storedFile.status()).toBe(404)
		expect(putFired).toBe(false)
	})

	test('ignores clipboard images in a file-drop share while preserving standard uploads', async ({
		page,
		user,
		ownerRequest,
		publicShare,
		filesListPage,
	}) => {
		await mkdir(ownerRequest, user, '/file-drop')

		await uploadContent(
			ownerRequest,
			user,
			'private content',
			'text/plain',
			'/file-drop/private.txt',
		)

		const share = await createLinkShare(ownerRequest, '/file-drop', {
			permissions: BUNDLED_PERMISSIONS.FILE_DROP,
		})

		await publicShare.open(share.url)

		await expect(publicShare.fileDropDescription('file-drop')).toBeVisible()
		await expect(filesListPage.getRowForFile('private.txt')).toHaveCount(0)

		let clipboardPutFired = false

		page.on('request', (request) => {
			if (request.method() === 'PUT'
				&& DAV_FILES_ENDPOINT.test(request.url())
				&& request.url().endsWith('/file-drop-image.png')) {
				clipboardPutFired = true
			}
		})

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['file-drop-image.png'],
		)

		expect(handled).toBe(false)

		await expect(publicShare.fileDropDescription('file-drop')).toBeVisible()

		// Standard File Drop uploads must remain functional after an ignored paste.
		await page.getByRole('button', { name: 'Upload', exact: true }).click()

		await publicShare.uploadFiles('Upload files', [
			{
				name: 'standard-upload.txt',
				mimeType: 'text/plain',
				buffer: Buffer.from('standard upload content'),
			},
		])

		await expect.poll(() => getFileContent(
			ownerRequest,
			user,
			'/file-drop/standard-upload.txt',
		).catch(() => '')).toBe('standard upload content')

		// Uploaded files must remain hidden from the guest.
		await expect(filesListPage.getRowForFile('standard-upload.txt')).toHaveCount(0)

		// Verify the clipboard image was never stored in the owner's folder.
		const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop/file-drop-image.png`)

		expect(storedFile.status()).toBe(404)
		expect(clipboardPutFired).toBe(false)
	})
})
