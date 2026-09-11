/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer close on click outside', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await filesListPage.open()
	})

	// Regression for nextcloud/viewer#2166: clicking outside the image closes it.
	test('closes when clicking outside the media', async ({ openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		await viewerPage.clickOutside()

		await viewerPage.waitForClosed()
	})
})
