/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, openSharingPanel } from '../../support/utils/sharing.ts'

test.describe('files_sharing: User share editor', () => {
	test.beforeEach(async ({ page, user, recipient, filesListPage }) => {
		await mkdir(page.request, user, '/test')
		await createShare(page.request, '/test', recipient.userId)
		await filesListPage.open()
	})

	test('cancelling the edition resets to the previous state', async ({ page, filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, 'test')

		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Set expiration date')).not.toBeChecked()
		await sharingTab.setCheckbox('Set expiration date', true)
		// A default expiration date is automatically added to the input
		await expect(sharingTab.checkbox('Allow download and sync')).toBeChecked()
		await sharingTab.setCheckbox('Allow download and sync', false)
		await expect(sharingTab.checkbox('Note to recipient')).not.toBeChecked()
		await sharingTab.setCheckbox('Note to recipient', true)
		await sharingTab.noteInput().fill('The note')
		await expect(sharingTab.checkbox('Custom permissions')).not.toBeChecked()
		await sharingTab.setCheckbox('Custom permissions', true)
		await expect(sharingTab.checkbox('Edit')).toBeChecked()
		await sharingTab.setCheckbox('Edit', false)
		await sharingTab.cancel()

		// Back to the original state when the editor is opened again …
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Set expiration date')).not.toBeChecked()
		await expect(sharingTab.checkbox('Allow download and sync')).toBeChecked()
		await expect(sharingTab.checkbox('Note to recipient')).not.toBeChecked()
		await expect(sharingTab.checkbox('Custom permissions')).not.toBeChecked()

		// … and after a reload, i.e. it was not stored
		await page.reload()
		await openSharingPanel(filesListPage, sharingTab, 'test')
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Set expiration date')).not.toBeChecked()
		await expect(sharingTab.checkbox('Allow download and sync')).toBeChecked()
		await expect(sharingTab.checkbox('Note to recipient')).not.toBeChecked()
		await expect(sharingTab.checkbox('Custom permissions')).not.toBeChecked()
	})
})
