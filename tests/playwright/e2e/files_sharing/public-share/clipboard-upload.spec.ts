/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { pasteImages, PNG_CONTENT } from '../../../support/utils/clipboard.ts'
import { DAV_FILES_ENDPOINT, getFileContent, mkdir, uploadContent } from '../../../support/utils/dav.ts'
import { BUNDLED_PERMISSIONS, createLinkShare } from '../../../support/utils/sharing.ts'
import { getToast } from '../../../support/utils/toast.ts'

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

		expect(handled).toBe(true)

		await expect(getToast(page, 'You do not have permission to upload or create files here.')).toBeVisible()
		await expect(filesListPage.getRowForFile('read-only.png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('existing.txt')).toBeVisible()

		const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/read-only/read-only.png`)

		expect(storedFile.status()).toBe(404)
		expect(putFired).toBe(false)
	})

	test('can paste an image into a file-drop share while preserving privacy and standard uploads', async ({
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

		const uploaded = page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith('/file-drop-image.png'))

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['file-drop-image.png'],
		)

		expect(handled).toBe(true)
		expect((await uploaded).ok()).toBe(true)

		const uploadedList = page.getByRole('list', { name: 'Successfully uploaded files' })

		await expect(page.getByRole('heading', { name: 'Successfully uploaded files' })).toBeVisible()
		await expect(uploadedList.getByText('file-drop-image.png')).toBeVisible()
		await expect(page.getByRole('dialog')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('private.txt')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('file-drop-image.png')).toHaveCount(0)

		// Verify the image was stored in the owner's folder without modification.
		const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop/file-drop-image.png`)

		expect(storedFile.ok()).toBe(true)
		expect(await storedFile.body()).toEqual(Buffer.from(PNG_CONTENT, 'base64'))
		expect(await getFileContent(ownerRequest, user, '/file-drop/private.txt')).toBe('private content')

		// Standard File Drop uploads must remain functional after a clipboard paste.
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

		await expect(uploadedList.getByText('standard-upload.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('standard-upload.txt')).toHaveCount(0)
	})

	test('can paste multiple images into a file-drop share', async ({
		page,
		user,
		ownerRequest,
		publicShare,
		filesListPage,
	}) => {
		await mkdir(ownerRequest, user, '/file-drop-multi')

		const share = await createLinkShare(ownerRequest, '/file-drop-multi', {
			permissions: BUNDLED_PERMISSIONS.FILE_DROP,
		})

		await publicShare.open(share.url)
		await expect(publicShare.fileDropDescription('file-drop-multi')).toBeVisible()

		const filenames = ['first.png', 'second.png']

		const uploads = Promise.all(filenames.map((name) => page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith(`/${name}`))))

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			filenames,
		)

		expect(handled).toBe(true)

		for (const response of await uploads) {
			expect(response.ok()).toBe(true)
		}

		const uploadedList = page.getByRole('list', { name: 'Successfully uploaded files' })

		await expect(page.getByRole('heading', { name: 'Successfully uploaded files' })).toBeVisible()

		for (const name of filenames) {
			await expect(uploadedList.getByText(name)).toBeVisible()
			await expect(filesListPage.getRowForFile(name)).toHaveCount(0)

			const storedFile = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop-multi/${name}`)

			expect(storedFile.ok()).toBe(true)
			expect(await storedFile.body()).toEqual(Buffer.from(PNG_CONTENT, 'base64'))
		}
	})

	test('renames duplicate clipboard filenames in a file-drop share without overwriting', async ({
		page,
		user,
		ownerRequest,
		publicShare,
		filesListPage,
	}) => {
		await mkdir(ownerRequest, user, '/file-drop-dup')

		// A valid 1x1 PNG that differs from PNG_CONTENT so overwrite can be detected.
		const existingContent = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64')

		await uploadContent(
			ownerRequest,
			user,
			existingContent,
			'image/png',
			'/file-drop-dup/duplicate.png',
		)

		const share = await createLinkShare(ownerRequest, '/file-drop-dup', {
			permissions: BUNDLED_PERMISSIONS.FILE_DROP,
		})

		await publicShare.open(share.url)
		await expect(publicShare.fileDropDescription('file-drop-dup')).toBeVisible()

		const waitForDuplicatePut = () => page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith('/duplicate.png'))

		const firstUpload = waitForDuplicatePut()

		const firstHandled = await pasteImages(
			filesListPage.getContentArea(),
			['duplicate.png'],
		)

		expect(firstHandled).toBe(true)
		expect((await firstUpload).ok()).toBe(true)

		await expect(page.getByRole('list', { name: 'Successfully uploaded files' }).getByText('duplicate.png')).toBeVisible()
		await expect(page.getByRole('dialog')).toHaveCount(0)

		// Existing destination content must stay intact; File Drop renames server-side.
		await expect.poll(async () => {
			const original = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop-dup/duplicate.png`)
			const renamed = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop-dup/duplicate%20(2).png`)

			return {
				originalOk: original.ok(),
				originalBody: Buffer.from(await original.body()).equals(existingContent),
				renamedOk: renamed.ok(),
				renamedBody: Buffer.from(await renamed.body()).equals(Buffer.from(PNG_CONTENT, 'base64')),
			}
		}).toEqual({
			originalOk: true,
			originalBody: true,
			renamedOk: true,
			renamedBody: true,
		})

		const secondUpload = waitForDuplicatePut()

		const secondHandled = await pasteImages(
			filesListPage.getContentArea(),
			['duplicate.png'],
		)

		expect(secondHandled).toBe(true)
		expect((await secondUpload).ok()).toBe(true)

		await expect.poll(async () => {
			const third = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop-dup/duplicate%20(3).png`)

			return {
				ok: third.ok(),
				bodyMatches: Buffer.from(await third.body()).equals(Buffer.from(PNG_CONTENT, 'base64')),
			}
		}).toEqual({
			ok: true,
			bodyMatches: true,
		})

		const original = await ownerRequest.get(`/remote.php/dav/files/${encodeURIComponent(user.userId)}/file-drop-dup/duplicate.png`)

		expect(original.ok()).toBe(true)
		expect(Buffer.from(await original.body()).equals(existingContent)).toBe(true)
		await expect(filesListPage.getRowForFile('duplicate.png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('duplicate (2).png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('duplicate (3).png')).toHaveCount(0)
	})
})
