/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer delete action', () => {
	// Deleting the shown file advances the viewer to the next file, then the
	// previous one, then closes when nothing is left.
	test('advances to the next file, then closes when the list is empty', async ({ filesListPage, uploadMedia, openFile, viewerPage }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await uploadMedia('image2.jpg', 'image2.jpg', 'image/jpeg')
		await filesListPage.open()
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		// Delete the first file → viewer moves to the second, staying open.
		await viewerPage.runAction('Delete file')
		await viewerPage.waitForOpen()
		await expect(async () => {
			expect(await viewerPage.currentName()).toBe('image2.jpg')
		}).toPass()

		// Delete the only remaining file → viewer closes.
		await viewerPage.runAction('Delete file')
		await viewerPage.waitForClosed()
	})
})
