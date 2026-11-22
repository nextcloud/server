/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer actions menu', () => {
	// The viewer links its header menu to the Files actions. Actions rendered by
	// a custom component (e.g. the sharing status) have no static label/icon and
	// must be filtered out, otherwise they show up as blank menu entries.
	test('does not render blank entries for custom-rendered file actions', async ({ page, filesListPage, uploadMedia, openFile, viewerPage }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		const toggle = viewerPage.actionsToggle()
		await toggle.click()

		const menuItems = page.getByRole('menuitem')
		await expect(menuItems.first()).toBeVisible()

		// Every menu entry must have a non-empty accessible label.
		const labels = await menuItems.allInnerTexts()
		expect(labels.length).toBeGreaterThan(0)
		for (const label of labels) {
			expect(label.trim()).not.toBe('')
		}

		// The sharing-status action is custom-rendered and must be absent.
		await expect(page.getByRole('menuitem', { name: /sharing/i })).toHaveCount(0)
	})
})
