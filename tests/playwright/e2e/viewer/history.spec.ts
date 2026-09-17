/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

test.describe('Viewer browser history', () => {
	test.beforeEach(async ({ uploadMedia }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await uploadMedia('image2.jpg', 'image2.jpg', 'image/jpeg')
	})

	test('reflects the open file in the URL and re-opens on refresh', async ({ page, filesListPage, openFile, viewerPage }) => {
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		// Opening pushes an openfile URL so a refresh re-triggers the viewer.
		expect(page.url()).toContain('openfile=true')

		await page.reload()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')
	})

	test('back and forward move between the shown files', async ({ page, filesListPage, openFile, viewerPage }) => {
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		const openUrl = page.url()

		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image2.jpg')
		// Navigating pushed a new history entry.
		expect(page.url()).not.toBe(openUrl)

		// Browser back returns to the first file, still inside the viewer.
		await page.goBack()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		// Browser forward returns to the second file.
		await page.goForward()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image2.jpg')
	})

	test('closing resets history so back does not re-open a file', async ({ page, filesListPage, openFile, viewerPage }) => {
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		await viewerPage.next()
		await viewerPage.waitForOpen()

		await viewerPage.close()
		// The openfile flag is gone from the URL…
		expect(page.url()).not.toContain('openfile=true')

		// …and pressing back does not step back into the images.
		await page.goBack()
		await viewerPage.waitForClosed()
	})

	test('navigating out of the openfile range closes the viewer', async ({ page, filesListPage, openFile, viewerPage }) => {
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		// A single back from the first file leaves the openfile range → close.
		await page.goBack()
		await viewerPage.waitForClosed()
	})
})
