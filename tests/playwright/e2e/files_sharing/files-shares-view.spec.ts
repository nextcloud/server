/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { createShare, waitForShare } from '../../support/utils/sharing.ts'

/**
 * Regression test of https://github.com/nextcloud/server/issues/46108: the
 * shares views list shared entries with an "Open in files" action, which has to
 * land in the folder itself.
 */
test('files_sharing: opens a shared folder from the shares views', async ({ page, user, recipient, recipientRequest, filesListPage }) => {
	await mkdir(page.request, user, '/folder')
	await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/folder/file')
	await createShare(page.request, '/folder', recipient.userId)
	await waitForShare(recipientRequest, recipient, '', 'folder')

	// The sharer sees it in "Shared with others"
	await filesListPage.open('sharingout')
	await expect(filesListPage.getRowForFile('folder')).toBeVisible()

	await filesListPage.getRowForFile('folder').getByRole('button', { name: /open in files/i }).click()

	await expect(page).toHaveURL(/apps\/files\/files\/.+dir=\/folder/)
	await expect(filesListPage.getRowForFile('file')).toBeVisible()

	// And the recipient the same in "Shared with you"
	await login(page.request, recipient)
	await filesListPage.open('sharingin')
	await expect(filesListPage.getRowForFile('folder')).toBeVisible()

	await filesListPage.getRowForFile('folder').getByRole('button', { name: /open in files/i }).click()

	await expect(page).toHaveURL(/apps\/files\/files\/.+dir=\/folder/)
	await expect(filesListPage.getRowForFile('file')).toBeVisible()
})
