/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

test.describe('Viewer compare API', () => {
	// The compare() API renders two files side by side. It has no Files-app UI
	// (it is used programmatically, e.g. by files_versions), so we capture two
	// real File nodes by opening them, then call the public viewer service.
	test('renders two files side by side', async ({ page, filesListPage, uploadMedia, openFile, viewerPage }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await uploadMedia('image2.jpg', 'image2.jpg', 'image/jpeg')
		await filesListPage.open()

		// Capture the File node passed to the viewer for each opened file.
		await page.evaluate(() => {
			const win = window as unknown as {
				__capturedNodes: unknown[]
				_oca_viewer_service: { open: (...args: unknown[]) => unknown }
			}
			win.__capturedNodes = []
			const service = win._oca_viewer_service
			const original = service.open.bind(service)
			service.open = (nodes: unknown, file: unknown, ...rest: unknown[]) => {
				win.__capturedNodes.push(file)
				return original(nodes, file, ...rest)
			}
		})

		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.close()

		await openFile('image2.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.close()

		// Programmatically compare the two captured nodes.
		await page.evaluate(async () => {
			const win = window as unknown as {
				__capturedNodes: unknown[]
				_oca_viewer_service: { compare: (a: unknown, b: unknown) => Promise<void> }
			}
			await win._oca_viewer_service.compare(win.__capturedNodes[0], win.__capturedNodes[1])
		})

		const comparison = viewerPage.modal.locator('.viewer__comparison')
		await expect(comparison).toBeVisible()
		await expect(comparison.locator('oca-viewer-image')).toHaveCount(2)
	})
})
