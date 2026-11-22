/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test } from '../../support/fixtures/viewer-page.ts'

test.describe('Viewer error handling', () => {
	// A media file that fails to load must show an error, not stay stuck on the
	// loading spinner (regression guard for the loading-gate / onError path).
	test('shows an error when the media fails to load', async ({ page, filesListPage, uploadMedia, openFile, viewerPage }) => {
		await uploadMedia('video1.mp4', 'video1.mp4', 'video/mp4')

		// Make every request for the video fail: the direct source and the E2EE
		// fallback fetch both hit the dav endpoint for this file.
		await page.route('**/video1.mp4', (route) => route.abort())
		await page.route('**/video1.mp4?**', (route) => route.abort())

		await filesListPage.open()
		await openFile('video1.mp4')

		await viewerPage.expectError()
	})
})
