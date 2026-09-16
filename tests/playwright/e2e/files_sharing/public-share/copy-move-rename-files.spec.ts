/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { BUNDLED_PERMISSIONS, createLinkShare, seedSharedFolder } from '../../../support/utils/sharing.ts'

const SHARE_NAME = 'shared'

/**
 * A public share that allows uploading and editing — the permission bundle the
 * editor calls "Allow upload and editing" — so its content can be reorganized
 * by a guest.
 */
test.describe('files_sharing: Public share - copy, move and rename files', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, {
			permissions: BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE,
		})
		await publicShare.open(share.url)
	})

	test('can copy a file to another folder', async ({ page, filesListPage, copyMoveDialog }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		await filesListPage.triggerActionForFile('foo.txt', 'move-copy')
		await copyMoveDialog.copyToFolder('subfolder')

		// The copy source stays where it is
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await filesListPage.navigateToFolder('subfolder')

		await expect(page).toHaveURL(/dir=\/subfolder/)
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('bar.txt')).toBeVisible()
	})

	test('can move a file to another folder', async ({ page, filesListPage, copyMoveDialog }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		await filesListPage.triggerActionForFile('foo.txt', 'move-copy')
		await copyMoveDialog.moveToFolder('subfolder')

		// Moved out of the current folder
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()
		await expect(filesListPage.getRowForFile('foo.txt')).toHaveCount(0)

		await filesListPage.navigateToFolder('subfolder')

		await expect(page).toHaveURL(/dir=\/subfolder/)
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
	})

	test('can rename a file', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await filesListPage.renameFile('foo.txt', 'other.txt')

		await expect(filesListPage.getRowForFile('other.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('foo.txt')).toHaveCount(0)
	})
})
