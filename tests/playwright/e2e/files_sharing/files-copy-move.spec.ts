/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-sharing-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { ALL_PERMISSIONS, createShare, SharePermission, waitForShare } from '../../support/utils/sharing.ts'

const EMPTY = Buffer.alloc(0)

test.describe('files_sharing: Move or copy files', () => {
	test('can create a file in a shared folder', async ({ page, user, owner, ownerRequest, filesListPage }) => {
		await mkdir(ownerRequest, owner, '/folder')
		await createShare(ownerRequest, '/folder', user.userId)
		await waitForShare(page.request, user, '', 'folder')

		// The recipient adds a file into the shared folder, then sees it there
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/folder/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await filesListPage.navigateToFolder('folder')
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
	})

	test('can copy a file to a shared folder', async ({ page, user, owner, ownerRequest, filesListPage, copyMoveDialog }) => {
		await mkdir(ownerRequest, owner, '/folder')
		await createShare(ownerRequest, '/folder', user.userId)
		await waitForShare(page.request, user, '', 'folder')

		await uploadContent(page.request, user, EMPTY, 'text/plain', '/file.txt')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await filesListPage.triggerActionForFile('file.txt', 'move-copy')
		await copyMoveDialog.copyToFolder('folder')

		await filesListPage.navigateToFolder('folder')
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
	})

	test('cannot copy a file to a shared folder with no create permission', async ({ page, user, owner, ownerRequest, filesListPage, copyMoveDialog }) => {
		await mkdir(ownerRequest, owner, '/folder')
		await mkdir(ownerRequest, owner, '/folder/inner-folder')
		await createShare(ownerRequest, '/folder', user.userId, { permissions: ALL_PERMISSIONS & ~SharePermission.CREATE })
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/file.txt')

		// Wait for the create restriction (no C) to reach the recipient's listing
		await waitForShare(page.request, user, '', 'folder', (p) => !p.includes('C'))

		await filesListPage.open()
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await filesListPage.triggerActionForFile('file.txt', 'move-copy')
		const propfind = page.waitForResponse((r) => r.request().method() === 'PROPFIND'
			&& r.url().includes('/remote.php/dav/files/')
			&& !!r.url().match(/\/folder\/?$/))
		await copyMoveDialog.navigateTo('folder')
		await propfind // wait for the PROPFIND to finish
		// see the content of the folde = loading finished
		await expect(copyMoveDialog.dialog().getByText(/inner-folder/)).toBeVisible()
		// now the button should be disabled
		await expect(copyMoveDialog.confirmButton('Copy to folder')).toBeDisabled()
	})

	test('cannot move a file from shared folder with no delete permission', async ({ page, user, owner, ownerRequest, filesListPage, copyMoveDialog }) => {
		await mkdir(ownerRequest, owner, '/folder')
		await uploadContent(ownerRequest, owner, EMPTY, 'text/plain', '/folder/file.txt')
		await createShare(ownerRequest, '/folder', user.userId, { permissions: ALL_PERMISSIONS & ~SharePermission.DELETE })

		// create the target
		await mkdir(page.request, user, '/owned-folder')

		// Wait for the delete restriction (no D) to reach the recipient's listing
		await waitForShare(page.request, user, '/folder', 'file.txt', (p) => !p.includes('D'))

		await filesListPage.open()
		await filesListPage.navigateToFolder('folder')
		await filesListPage.triggerActionForFile('file.txt', 'move-copy')
		const propfind = page.waitForResponse((r) => r.request().method() === 'PROPFIND'
			&& r.url().includes('/remote.php/dav/files/')
			&& !!r.url().match(/\/owned-folder\/?$/))
		await copyMoveDialog.goToAllFiles()
		await copyMoveDialog.navigateTo('owned-folder')
		await propfind // wait for the PROPFIND to finish

		// can copy but not move
		await expect(copyMoveDialog.confirmButton('Copy to owned-folder')).toBeVisible()
		await expect(copyMoveDialog.confirmButton('Move to owned-folder')).toHaveCount(0)
	})
})
