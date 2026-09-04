/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, openSharingPanel } from '../../support/utils/sharing.ts'

/**
 * The unified sharing API is the killswitch for the new sidebar: with no API
 * version advertised in the capabilities, the sidebar falls back to the legacy
 * sections. That is the state the API ships in, which the share editor fixtures
 * this spec builds on already set for their worker.
 */
test.describe('files_sharing: sidebar without the unified sharing API', () => {
	test('falls back to the legacy share sections', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await mkdir(page.request, user, '/legacy')
		await createShare(page.request, '/legacy', recipient.userId)
		await filesListPage.open()
		await openSharingPanel(filesListPage, sharingTab, 'legacy')

		const tab = page.locator('.sharingTab')
		await expect(tab.getByText('Internal shares')).toBeVisible()
		await expect(tab.locator('.unified-share-list')).toHaveCount(0)
		// The legacy editor still lists the share it was given.
		await expect(tab.getByText(recipient.userId).first()).toBeVisible()
	})
})
