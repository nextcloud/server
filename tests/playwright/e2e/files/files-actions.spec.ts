/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { rm, uploadContent } from '../../support/utils/dav.ts'

// A representative subset of the default actions, not the full feature set.
const expectedRowActions = ['move-copy', 'delete', 'details']
const expectedSelectionActions = ['move-copy', 'delete']

test.describe('Files: Actions', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		// New users get welcome.txt — remove it so the list contains only our test file
		await rm(page.request, user, '/welcome.txt')
		await uploadContent(page.request, user, Buffer.alloc(0), 'image/jpeg', '/image.jpg')
		await filesListPage.open()
	})

	test('shows the standard row actions', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('image.jpg')).toBeVisible()

		const menu = await filesListPage.openActionsMenuForFile('image.jpg')
		for (const actionId of expectedRowActions) {
			await expect(filesListPage.getActionButtonInMenu(menu, actionId)).toBeVisible()
		}
	})

	test('shows the standard actions for a selection', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('image.jpg')).toBeVisible()

		await filesListPage.selectRowForFile('image.jpg')
		await expect(filesListPage.getSelectionActionsToolbar()).toBeVisible()

		await filesListPage.openSelectionActionsMenu()
		for (const actionId of expectedSelectionActions) {
			await expect(filesListPage.getSelectionActionEntry(actionId)).toBeVisible()
		}
	})
})
