/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

// Zero-padded so the natural file-list order matches the numeric order.
const COUNT = 30
const name = (index: number): string => `image-${String(index).padStart(3, '0')}.jpg`

test.describe('Viewer in a large folder', () => {
	// Regression for nextcloud/viewer#3015: opening a file in a directory with
	// many files used to hang (O(n^2) sort). The viewer now trusts the caller's
	// order, so opening and navigating stays responsive.
	test('opens quickly and navigates in a folder with many files', async ({ filesListPage, uploadMedia, openFile, viewerPage }) => {
		await Promise.all(Array.from({ length: COUNT }, (_, index) => uploadMedia('image1.jpg', name(index), 'image/jpeg')))
		await filesListPage.open()
		await expect(filesListPage.getRowForFile(name(0))).toBeVisible()

		const start = Date.now()
		await openFile(name(0))
		await viewerPage.waitForOpen()
		// The prev/next controls become available well within a sane budget.
		expect(Date.now() - start).toBeLessThan(15000)

		expect(await viewerPage.currentName()).toBe(name(0))

		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe(name(1))
	})
})
