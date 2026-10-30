/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { BUNDLED_PERMISSIONS, createLinkShare, seedSharedFolder } from '../../../support/utils/sharing.ts'

const SHARE_NAME = 'shared'

test.describe('files_sharing: Public share - view only', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, {
			permissions: BUNDLED_PERMISSIONS.READ_ONLY,
		})
		await publicShare.open(share.url)
	})

	test('can see the files list', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()
	})

	test('can navigate to a subfolder', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		await filesListPage.navigateToFolder('subfolder')

		await expect(filesListPage.getRowForFile('bar.txt')).toBeVisible()
	})

	test('cannot upload files', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		// Without CREATE permission the share offers no way to add content
		await expect(page.getByRole('button', { name: 'New' })).toHaveCount(0)
		await expect(page.getByRole('button', { name: /^Upload/ })).toHaveCount(0)
	})

	test('offers downloading as the only file action', async ({ page, filesListPage }) => {
		const menu = await filesListPage.openActionsMenuForFile('foo.txt')

		await expect(menu.getByRole('menuitem')).toHaveCount(1)
		await expect(menu.getByRole('menuitem', { name: 'Download' })).toBeVisible()

		const downloadPromise = page.waitForEvent('download')
		await menu.getByRole('menuitem', { name: 'Download' }).click()
		const download = await downloadPromise

		expect(download.suggestedFilename()).toBe('foo.txt')
	})
})

test.describe('files_sharing: Public share - view only without download', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, {
			permissions: BUNDLED_PERMISSIONS.READ_ONLY,
			hideDownload: true,
		})
		await publicShare.open(share.url)
	})

	test('can see the files list', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
	})

	test('offers no file actions at all', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await expect(filesListPage.getRowForFile('foo.txt').getByRole('button', { name: 'Actions' })).toHaveCount(0)
	})

	test('can navigate to a subfolder, which also has no actions', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('subfolder')).toBeVisible()

		await filesListPage.navigateToFolder('subfolder')

		await expect(filesListPage.getRowForFile('bar.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('bar.txt').getByRole('button', { name: 'Actions' })).toHaveCount(0)
	})

	test('cannot upload files', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await expect(page.getByRole('button', { name: 'New' })).toHaveCount(0)
		await expect(page.getByRole('button', { name: /^Upload/ })).toHaveCount(0)
	})
})
