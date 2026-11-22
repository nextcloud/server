/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { createShare, openSharingPanel, waitForShare } from '../../support/utils/sharing.ts'

const NOTE = 'Hello, this is the note.'

test.describe('files_sharing: Note to recipient', () => {
	test('is shown to the recipient inside the shared folder', async ({ page, user, recipient, recipientRequest, filesListPage }) => {
		await mkdir(page.request, user, '/folder')
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/folder/file')
		await createShare(page.request, '/folder', recipient.userId, { note: NOTE })
		await waitForShare(recipientRequest, recipient, '', 'folder')

		await login(page.request, recipient)
		await filesListPage.open()
		await filesListPage.navigateToFolder('folder')

		await expect(page.getByText(NOTE)).toBeVisible()
	})

	test('is shown to the recipient even when the folder is empty', async ({ page, user, recipient, recipientRequest, filesListPage }) => {
		await mkdir(page.request, user, '/folder')
		await createShare(page.request, '/folder', recipient.userId, { note: NOTE })
		await waitForShare(recipientRequest, recipient, '', 'folder')

		await login(page.request, recipient)
		await filesListPage.open()
		await filesListPage.navigateToFolder('folder')

		await expect(page.getByText(NOTE)).toBeVisible()
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/46188, where
	 * re-opening a share hid the note it already had.
	 */
	test('is filled in when the share is edited again', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await mkdir(page.request, user, '/folder')
		await createShare(page.request, '/folder', recipient.userId, { note: NOTE })
		await filesListPage.open()

		await openSharingPanel(filesListPage, sharingTab, 'folder')
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()

		await expect(sharingTab.checkbox('Note to recipient')).toBeChecked()
		await expect(sharingTab.noteInput()).toHaveValue(NOTE)
	})
})
