/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '../../support/fixtures/files-page.ts'
import { mkdir } from '../../support/utils/dav.ts'

test.describe('Files: Duplicated node regression', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/only once')
		await filesListPage.open()
	})

	/**
	 * Regression: https://github.com/nextcloud/server/issues/47904
	 * Deleting a node and recreating it with the same name left two rows in the list.
	 */
	test('does not duplicate a node after delete and recreate', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('only once')).toBeVisible()

		const deleted = page.waitForResponse(
			(r) => r.request().method() === 'DELETE' && r.url().includes('/remote.php/dav/files/'),
		)
		await filesListPage.triggerActionForFile('only once', 'delete')
		await deleted
		await expect(filesListPage.getRowForFile('only once')).toHaveCount(0)

		await filesListPage.createFolder('only once')

		await expect(filesListPage.getRowForFile('only once')).toHaveCount(1)
	})
})
