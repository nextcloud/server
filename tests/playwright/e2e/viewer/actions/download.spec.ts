/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, sharingTest as test, uploadMediaFile } from '../../../support/fixtures/viewer-page.ts'
import { mkdir } from '../../../support/utils/dav.ts'
import { createShare, DOWNLOAD_DISABLED_ATTRIBUTE, waitForShare } from '../../../support/utils/sharing.ts'

test.describe('Viewer download restrictions', () => {
	// The owner shares a folder with the current user, forbidding download. The
	// recipient must then get no download control in the viewer.
	test('does not expose a download control when download is forbidden', async ({ page, user, owner, ownerRequest, filesListPage, viewerPage }) => {
		await mkdir(ownerRequest, owner, '/Photos')
		await uploadMediaFile(ownerRequest, owner, 'image1.jpg', '/Photos/image1.jpg', 'image/jpeg')
		await createShare(ownerRequest, '/Photos', user.userId, { attributes: DOWNLOAD_DISABLED_ATTRIBUTE })
		await waitForShare(page.request, user, '', 'Photos')

		await filesListPage.open()
		await filesListPage.getRowNameLinkForFile('Photos').click()
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()

		await filesListPage.getRowNameLinkForFile('image1.jpg').click()
		await viewerPage.isVisible()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		// No download link nor download action is offered in the viewer.
		await expect(viewerPage.modal.locator('a[download]')).toHaveCount(0)
		await expect(viewerPage.modal.getByRole('button', { name: /download/i })).toHaveCount(0)
	})
})
