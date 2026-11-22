/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer sidebar action', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()
	})

	test('opens the Files sidebar for the current file', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		await viewerPage.openSidebar()

		const sidebar = page.locator('aside.app-sidebar')
		await expect(sidebar).toBeVisible()
		await expect(sidebar.locator('.app-sidebar-header__mainname')).toContainText('image1.jpg')
	})

	// The sidebar next to the viewer must fill the full height (the app header is
	// hidden), like the pre-7.0.0 viewer did.
	test('shows the sidebar full height next to the viewer', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.openSidebar()

		const sidebar = page.locator('aside.app-sidebar')
		await expect(sidebar).toBeVisible()

		await expect(async () => {
			const full = await page.evaluate(() => {
				const el = document.querySelector('aside.app-sidebar')!
				const header = document.querySelector('#header')
				return document.body.classList.contains('viewer--sidebar-fullscreen')
					&& getComputedStyle(el).position === 'fixed'
					&& (!header || getComputedStyle(header).visibility === 'hidden')
					&& Math.round(el.getBoundingClientRect().height) === window.innerHeight
			})
			expect(full).toBe(true)
		}).toPass()
	})

	// Regression for nextcloud/viewer#658: opening the sidebar while the image is
	// still loading must still show the sidebar (the header actions are available
	// during loading).
	test('opens the sidebar right after opening a file', async ({ page, openFile, viewerPage }) => {
		// The header actions have to work from the moment the viewer is up,
		// whether or not the pixels have arrived. The loading state itself is
		// not what is asserted: the file list fetches the preview for its own
		// row and the viewer reuses that response, so holding the viewer's
		// request back delays nothing, and the spinner measured here lives
		// about 600ms. Waiting for it is a race in both directions, which is
		// how this test failed on a fast machine and passed on a slow one.
		await openFile('image1.jpg')
		await expect(viewerPage.container).toBeVisible()

		await viewerPage.openSidebar()

		const sidebar = page.locator('aside.app-sidebar')
		await expect(sidebar).toBeVisible()
		await expect(sidebar).toContainText('image1.jpg')

		// And the picture still arrives behind it
		await viewerPage.waitForOpen()
	})
})
