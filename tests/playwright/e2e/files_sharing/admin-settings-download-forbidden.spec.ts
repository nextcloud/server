/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../support/fixtures/files-sharing-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { createShare, DOWNLOAD_DISABLED_ATTRIBUTE, SharePermission, waitForShare } from '../../support/utils/sharing.ts'

/**
 * A share whose download is switched off must not offer a download action to the
 * recipient — neither while view-without-download is allowed instance-wide (the
 * file is then viewable but not downloadable) nor when it is off.
 *
 * The browser is logged in as the recipient here; `owner` seeds the share.
 */
test.describe('files_sharing: Download forbidden', () => {
	test.beforeEach(async () => {
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_allow_view_without_download'])
	})

	test.afterAll(async () => {
		await runOcc(['config:app:delete', 'core', 'shareapi_allow_view_without_download'])
	})

	test('offers no download action for a folder', async ({ page, user, owner, ownerRequest, filesListPage }) => {
		await mkdir(ownerRequest, owner, '/folder')
		await createShare(ownerRequest, '/folder', user.userId, {
			permissions: SharePermission.READ,
			attributes: DOWNLOAD_DISABLED_ATTRIBUTE,
		})
		await waitForShare(page.request, user, '', 'folder')

		await filesListPage.open()
		let menu = await filesListPage.openActionsMenuForFile('folder')
		await expect(menu.getByRole('menuitem', { name: 'Download' })).toHaveCount(0)

		// Also with view-without-download disabled the action stays away
		await runOcc(['config:app:set', '--value', 'no', 'core', 'shareapi_allow_view_without_download'])

		await filesListPage.open()
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		menu = await filesListPage.openActionsMenuForFile('folder')
		await expect(menu.getByRole('menuitem', { name: 'Download' })).toHaveCount(0)
	})

	test('offers no download action for a file', async ({ page, user, owner, ownerRequest, filesListPage }) => {
		await uploadContent(ownerRequest, owner, Buffer.alloc(0), 'text/plain', '/file.txt')
		await createShare(ownerRequest, '/file.txt', user.userId, {
			permissions: SharePermission.READ,
			attributes: DOWNLOAD_DISABLED_ATTRIBUTE,
		})
		await waitForShare(page.request, user, '', 'file.txt')

		await filesListPage.open()
		let menu = await filesListPage.openActionsMenuForFile('file.txt')
		await expect(menu.getByRole('menuitem', { name: 'Download' })).toHaveCount(0)

		await runOcc(['config:app:set', '--value', 'no', 'core', 'shareapi_allow_view_without_download'])

		await filesListPage.open()
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		menu = await filesListPage.openActionsMenuForFile('file.txt')
		await expect(menu.getByRole('menuitem', { name: 'Download' })).toHaveCount(0)
	})
})
