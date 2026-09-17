/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

test.describe('Viewer accessibility', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await uploadMedia('image2.jpg', 'image2.jpg', 'image/jpeg')
		await uploadMedia('video1.mp4', 'video1.mp4', 'video/mp4')
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()
	})

	test('exposes the viewer as a labelled dialog with accessible controls', async ({ page, openFile, viewerPage }) => {
		await openFile('image2.jpg')
		await viewerPage.waitForOpen()

		// The modal is a dialog labelled by the current file name.
		const dialog = page.locator('.viewer__modal[role="dialog"]')
		await expect(dialog).toBeVisible()
		expect(await viewerPage.currentName()).toBe('image2.jpg')

		// Navigation and close controls have accessible names.
		await expect(viewerPage.nextButton).toBeVisible()
		await expect(viewerPage.previousButton).toBeVisible()
		await expect(viewerPage.closeButton).toBeVisible()
	})
})
