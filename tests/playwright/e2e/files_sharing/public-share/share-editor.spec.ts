/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/sharing-page.ts'
import { mkdir } from '../../../support/utils/dav.ts'
import { createLinkShare, openSharingPanel } from '../../../support/utils/sharing.ts'

/** The link-share side of the share editor, driven by the share owner. */
test.describe('files_sharing: Link share editor', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/test')
		await createLinkShare(page.request, '/test')
		await filesListPage.open()
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/53566, where
	 * an apostrophe in the label was rendered as `&#39;`.
	 */
	test('lists a share label with special characters as typed', async ({ filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, 'test')

		await sharingTab.openLinkShareDetails()
		await sharingTab.openAdvancedSettings()
		await sharingTab.labelInput().fill('Alice\' share')
		await sharingTab.save()

		await expect(sharingTab.linkShareEntries()).toHaveCount(1)
		await expect(sharingTab.linkShareEntries().first()).toContainText('Share link (Alice\' share)')
	})

	/**
	 * Regression test: "Hide download" must survive both re-opening the editor and
	 * a page reload — the checkbox used to fall back to its default.
	 */
	test('keeps "Hide download" after saving and reloading', async ({ page, filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, 'test')

		await sharingTab.openLinkShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Hide download')).not.toBeChecked()
		await sharingTab.setCheckbox('Hide download', true, { persists: true })
		await sharingTab.closeDetails()

		// Still set when the editor is opened again …
		await sharingTab.openLinkShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Hide download')).toBeChecked()

		// … and after a reload, i.e. it was really stored
		await page.reload()
		await openSharingPanel(filesListPage, sharingTab, 'test')
		await sharingTab.openLinkShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Hide download')).toBeChecked()
	})
})

test.describe('files_sharing: Email share editor', () => {
	/**
	 * A brand new email share must keep the "Hide download" option that was set
	 * before it was ever saved.
	 */
	test('keeps "Hide download" set while creating the share', async ({ page, user, filesListPage, sharingTab }) => {
		await mkdir(page.request, user, '/test')
		await filesListPage.open()

		await openSharingPanel(filesListPage, sharingTab, 'test')
		await sharingTab.pickRecipient('test@example.com', { external: true })

		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Hide download')).not.toBeChecked()
		await sharingTab.setCheckbox('Hide download', true)
		await sharingTab.save()

		await sharingTab.openLinkShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.checkbox('Hide download')).toBeChecked()
	})
})
