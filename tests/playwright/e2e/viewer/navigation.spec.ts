/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

const IMAGES = ['image1.jpg', 'image2.jpg', 'image3.jpg', 'image4.jpg']

test.describe('Viewer navigation', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia }) => {
		for (const image of IMAGES) {
			await uploadMedia(image, image, 'image/jpeg')
		}
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()
	})

	test('navigates forward through the list and loops back to the first image', async ({ openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		// The list has several images: navigation arrows are shown.
		await expect(viewerPage.nextButton).toBeVisible()
		await expect(viewerPage.previousButton).toBeVisible()

		for (const image of ['image2.jpg', 'image3.jpg', 'image4.jpg']) {
			await viewerPage.next()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(image)
			await viewerPage.expectHandler('image')
		}

		// Looping from the last image back to the first.
		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')
	})

	test('navigates backward and loops from the first image to the last', async ({ openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		await viewerPage.previous()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image4.jpg')
	})

	test('closes the viewer when navigating back in the browser', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.isVisible()

		await page.goBack()
		await expect(viewerPage.container).toBeHidden()
	})
})
