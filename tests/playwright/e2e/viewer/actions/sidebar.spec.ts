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
	test('opens the sidebar while the image is still loading', async ({ page, openFile, viewerPage }) => {
		// The previews of the file list are held back as well, so the whole test
		// runs against delayed responses and needs more than the default budget.
		test.slow()

		// Hold the preview response so the viewer stays in its loading state long
		// enough to interact with the header while loading.
		await page.route('**/core/preview*', async (route) => {
			await new Promise((resolve) => setTimeout(resolve, 3000))
			await route.continue()
		})

		await openFile('image1.jpg')
		await expect(viewerPage.container).toBeVisible()
		await expect(viewerPage.loading).toHaveCount(1)

		await viewerPage.openSidebar()

		const sidebar = page.locator('aside.app-sidebar')
		await expect(sidebar).toBeVisible()

		// The image still finishes loading afterwards. Stop holding the previews
		// back first: opening the sidebar resizes the viewer, which requests the
		// preview again, and that request would be delayed as well.
		await page.unroute('**/core/preview*')
		await viewerPage.waitForOpen()
	})
})
