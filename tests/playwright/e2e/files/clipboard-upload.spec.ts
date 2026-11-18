/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/files-page.ts'
import { test as sharingTest } from '../../support/fixtures/files-sharing-page.ts'
import { pasteImages, PNG_CONTENT } from '../../support/utils/clipboard.ts'
import { DAV_FILES_ENDPOINT, mkdir, uploadContent } from '../../support/utils/dav.ts'
import { BUNDLED_PERMISSIONS, createShare, updateShare, waitForShare } from '../../support/utils/sharing.ts'
import { getToast } from '../../support/utils/toast.ts'

test.describe('files: Clipboard image upload', () => {
	test('can paste an image into the current folder', async ({ page, filesListPage }) => {
		await filesListPage.open()
		await filesListPage.waitForListLoaded()

		const uploaded = page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith('/clipboard-image.png'))

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['clipboard-image.png'],
		)

		expect(handled).toBe(true)

		const response = await uploaded

		expect(response.ok()).toBe(true)

		await expect(filesListPage.getRowForFile('clipboard-image.png')).toBeVisible()

		const storedFile = await page.request.get(response.url())

		expect(storedFile.ok()).toBe(true)
		expect(await storedFile.body()).toEqual(Buffer.from(PNG_CONTENT, 'base64'))
	})

	test('can paste multiple images into a nested folder', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/photos')
		await mkdir(page.request, user, '/photos/clipboard')

		await filesListPage.open()
		await filesListPage.navigateToFolder('photos/clipboard')
		await filesListPage.waitForListLoaded()

		const filenames = ['first.png', 'second.png']

		const uploads = Promise.all(filenames.map((name) => page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith(`/photos/clipboard/${name}`))))

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			filenames,
		)

		expect(handled).toBe(true)

		for (const response of await uploads) {
			expect(response.ok()).toBe(true)
		}

		for (const name of filenames) {
			await expect(filesListPage.getRowForFile(name)).toBeVisible()
		}

		await filesListPage.open()
		await filesListPage.waitForListLoaded()

		for (const name of filenames) {
			await expect(filesListPage.getRowForFile(name)).toHaveCount(0)
		}
	})

	test('can keep both images when a filename conflict occurs', async ({ page, user, filesListPage }) => {
		const content = Buffer.from(PNG_CONTENT, 'base64')

		await uploadContent(
			page.request,
			user,
			content,
			'image/png',
			'/duplicate.png',
		)

		await uploadContent(
			page.request,
			user,
			'unrelated content',
			'text/plain',
			'/unrelated.txt',
		)

		await filesListPage.open()
		await filesListPage.waitForListLoaded()

		await expect(filesListPage.getRowForFile('duplicate.png')).toBeVisible()

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['duplicate.png'],
		)

		expect(handled).toBe(true)

		const dialog = page.getByRole('dialog')

		await expect(dialog).toBeVisible()

		const uploaded = page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& decodeURIComponent(response.url()).endsWith('/duplicate (1).png'))

		await dialog.getByRole('button', { name: 'Keep both', exact: true }).click()

		expect((await uploaded).ok()).toBe(true)

		await expect(filesListPage.getRowForFile('duplicate.png')).toBeVisible()
		await expect(filesListPage.getRowForFile('duplicate (1).png')).toBeVisible()
		await expect(filesListPage.getRowForFile('unrelated.txt')).toBeVisible()
	})

	test('does not upload clipboard images while renaming a file', async ({ page, user, filesListPage }) => {
		await uploadContent(
			page.request,
			user,
			'original content',
			'text/plain',
			'/document.txt',
		)

		await filesListPage.open()
		await filesListPage.waitForListLoaded()

		await filesListPage.triggerActionForFile('document.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('document.txt')

		await expect(input).toBeVisible()
		await input.focus()
		await expect(input).toBeFocused()

		let putFired = false

		page.on('request', (request) => {
			if (request.method() === 'PUT' && DAV_FILES_ENDPOINT.test(request.url())) {
				putFired = true
			}
		})

		const handled = await pasteImages(input, ['ignored.png'])

		expect(handled).toBe(false)

		await expect(input).toBeFocused()
		await expect(filesListPage.getRowForFile('ignored.png')).toHaveCount(0)

		await input.fill('renamed')
		await expect(input).toHaveValue('renamed')

		await input.press('Escape')

		await expect(filesListPage.getRowForFile('document.txt')).toBeVisible()
		await expect(page.getByRole('dialog')).toHaveCount(0)

		expect(putFired).toBe(false)
	})

	test('uploads into the original folder when navigating away', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/original')
		await mkdir(page.request, user, '/other')

		await filesListPage.open()
		await filesListPage.navigateToFolder('original')
		await filesListPage.waitForListLoaded()

		let uploadStarted = false
		let releaseUpload!: () => void

		const uploadGate = new Promise<void>((resolve) => {
			releaseUpload = resolve
		})

		// Pause the actual upload request until navigation completes.
		await page.route('**/navigation.png', async (route) => {
			if (route.request().method() !== 'PUT') {
				await route.continue()
				return
			}

			uploadStarted = true
			await uploadGate
			await route.continue()
		})

		const [response] = await Promise.all([
			page.waitForResponse((response) => response.request().method() === 'PUT'
				&& DAV_FILES_ENDPOINT.test(response.url())
				&& response.url().endsWith('/original/navigation.png')),
			(async () => {
				try {
					const handled = await pasteImages(
						filesListPage.getContentArea(),
						['navigation.png'],
					)

					expect(handled).toBe(true)

					await expect.poll(() => uploadStarted).toBe(true)

					await filesListPage.navigateToBreadcrumb('All files')
					await filesListPage.navigateToFolder('other')
					await filesListPage.waitForListLoaded()
				} finally {
					releaseUpload()
				}
			})(),
		])

		expect(response.ok()).toBe(true)

		await expect(filesListPage.getRowForFile('navigation.png')).toHaveCount(0)

		await filesListPage.navigateToBreadcrumb('All files')
		await filesListPage.navigateToFolder('original')
		await filesListPage.waitForListLoaded()

		await expect(filesListPage.getRowForFile('navigation.png')).toBeVisible()
	})

	test('does not upload clipboard images when the storage quota is exhausted', async ({ page, user, filesListPage }) => {
		// Ensure the user has already consumed more than one byte.
		await uploadContent(
			page.request,
			user,
			Buffer.from(PNG_CONTENT, 'base64'),
			'image/png',
			'/existing.png',
		)

		await runOcc([
			'user:setting',
			user.userId,
			'files',
			'quota',
			'1 B',
		])

		await filesListPage.open()
		await filesListPage.waitForListLoaded()

		await expect(filesListPage.getRowForFile('existing.png')).toBeVisible()
		await expect(page.locator('[data-cy-upload-picker]')).toHaveCount(0)

		let putFired = false

		page.on('request', (request) => {
			if (request.method() === 'PUT' && DAV_FILES_ENDPOINT.test(request.url())) {
				putFired = true
			}
		})

		const handled = await pasteImages(
			filesListPage.getContentArea(),
			['quota-blocked.png'],
		)

		expect(handled).toBe(true)

		await expect(getToast(page, 'You have used your space quota and cannot upload files anymore.')).toBeVisible()
		await expect(filesListPage.getRowForFile('quota-blocked.png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('existing.png')).toBeVisible()

		expect(putFired).toBe(false)
	})
})

sharingTest.describe('files: Clipboard image upload in internal shares', () => {
	sharingTest('respects writable and read-only sharing permissions', async ({
		page,
		user,
		owner,
		ownerRequest,
		filesListPage,
	}) => {
		const folderName = 'clipboard-shared'

		await mkdir(ownerRequest, owner, `/${folderName}`)

		const share = await createShare(
			ownerRequest,
			`/${folderName}`,
			user.userId,
			{ permissions: BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE },
		)

		await waitForShare(
			page.request,
			user,
			'',
			folderName,
			(permissions) => permissions.includes('C'),
		)

		await filesListPage.open()
		await filesListPage.navigateToFolder(folderName)
		await filesListPage.waitForListLoaded()

		const uploaded = page.waitForResponse((response) => response.request().method() === 'PUT'
			&& DAV_FILES_ENDPOINT.test(response.url())
			&& response.url().endsWith(`/${folderName}/shared-image.png`))

		const writablePaste = await pasteImages(
			filesListPage.getContentArea(),
			['shared-image.png'],
		)

		expect(writablePaste).toBe(true)
		expect((await uploaded).ok()).toBe(true)

		await expect(filesListPage.getRowForFile('shared-image.png')).toBeVisible()

		// Revoke write permission and wait for the change to propagate.
		await updateShare(ownerRequest, share.id, {
			permissions: BUNDLED_PERMISSIONS.READ_ONLY,
		})

		await waitForShare(
			page.request,
			user,
			'',
			folderName,
			(permissions) => !permissions.includes('C'),
		)

		await filesListPage.open()
		await filesListPage.navigateToFolder(folderName)
		await filesListPage.waitForListLoaded()

		await expect(page.locator('[data-cy-upload-picker]')).toHaveCount(0)

		let putFired = false

		page.on('request', (request) => {
			if (request.method() === 'PUT' && DAV_FILES_ENDPOINT.test(request.url())) {
				putFired = true
			}
		})

		const readOnlyPaste = await pasteImages(
			filesListPage.getContentArea(),
			['read-only.png'],
		)

		expect(readOnlyPaste).toBe(true)

		await expect(getToast(page, 'You do not have permission to upload or create files here.')).toBeVisible()
		await expect(filesListPage.getRowForFile('read-only.png')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('shared-image.png')).toBeVisible()

		expect(putFired).toBe(false)
	})
})
