/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { createLinkShare, GRID_VIEW_ATTRIBUTE, seedSharedFolder } from '../../../support/utils/sharing.ts'

const SHARE_NAME = 'shared'

/**
 * Which view mode a public share opens in. The view is identified by the toggle
 * the header offers: "Switch to grid view" means we are in list view, and the
 * other way round.
 */
test.describe('files_sharing: Public share - default view mode', () => {
	test.beforeEach(async ({ user, ownerRequest }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
	})

	test('opens in list view by default', async ({ page, ownerRequest, publicShare, filesListPage }) => {
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`)
		await publicShare.open(share.url)

		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(page.getByRole('button', { name: 'Switch to grid view' })).toBeEnabled()
	})

	test('can be toggled by the visitor', async ({ page, ownerRequest, publicShare, filesListPage }) => {
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`)
		await publicShare.open(share.url)

		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()

		await page.getByRole('button', { name: 'Switch to grid view' }).click()

		// The toggle now offers the way back, i.e. we are in grid view
		await expect(page.getByRole('button', { name: 'Switch to list view' })).toBeEnabled()
		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
	})

	test('opens in grid view when the share asks for it', async ({ page, ownerRequest, publicShare, filesListPage }) => {
		// "Show files in grid view" in the share editor stores this attribute
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, { attributes: GRID_VIEW_ATTRIBUTE })
		await publicShare.open(share.url)

		await expect(filesListPage.getRowForFile('foo.txt')).toBeVisible()
		await expect(page.getByRole('button', { name: 'Switch to list view' })).toBeEnabled()
	})
})
