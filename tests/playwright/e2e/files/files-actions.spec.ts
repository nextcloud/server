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

	test('keeps the row menu open when it is reopened right after closing it', async ({ page, filesListPage }) => {
		const toggle = filesListPage.getActionsButtonForFile('image.jpg')
		await filesListPage.openActionsMenuForFile('image.jpg')

		for (let round = 1; round <= 3; round++) {
			await page.keyboard.press('Escape')
			await expect(toggle).toHaveAttribute('aria-expanded', 'false')
			await toggle.click({ force: true })
			await expect(toggle).toHaveAttribute('aria-expanded', 'true')
			// Late events of the previous closing must not close the menu again
			await page.waitForTimeout(300)
			await expect(toggle, `round ${round}`).toHaveAttribute('aria-expanded', 'true')
		}
	})
})
