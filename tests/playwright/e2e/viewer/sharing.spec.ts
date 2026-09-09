/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, publicShareTest as test, uploadMediaFile } from '../../support/fixtures/viewer-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createLinkShare } from '../../support/utils/sharing.ts'

test.describe('Viewer on public link shares', () => {
	test('opens a single shared image in the viewer', async ({ user, ownerRequest, publicShare, viewerPage }) => {
		await uploadMediaFile(ownerRequest, user, 'image1.jpg', '/image1.jpg', 'image/jpeg')
		const share = await createLinkShare(ownerRequest, '/image1.jpg')

		// A single-file public share opens the viewer automatically. On a public
		// share the node is exposed under the share token, so we only assert the
		// correct handler renders, not the (token) file name.
		await publicShare.open(share.url)
		await viewerPage.waitForOpen()

		await viewerPage.expectHandler('image')
	})

	test('opens a single shared video in the viewer', async ({ user, ownerRequest, publicShare, viewerPage }) => {
		await uploadMediaFile(ownerRequest, user, 'video1.mp4', '/video1.mp4', 'video/mp4')
		const share = await createLinkShare(ownerRequest, '/video1.mp4')

		// A single-file public share opens the viewer automatically.
		await publicShare.open(share.url)
		await viewerPage.waitForOpen()

		await viewerPage.expectHandler('video')
		// The public share serves the video from the public WebDAV endpoint.
		await expect(viewerPage.mediaElement('video'))
			.toHaveAttribute('src', new RegExp(`/public\\.php/dav/files/${share.token}`))
	})

	test('navigates through a shared folder of images', async ({ user, ownerRequest, publicShare, filesListPage, viewerPage }) => {
		await mkdir(ownerRequest, user, '/Photos')
		const images = ['image1.jpg', 'image2.jpg', 'image3.jpg', 'image4.jpg']
		for (const image of images) {
			await uploadMediaFile(ownerRequest, user, image, `/Photos/${image}`, 'image/jpeg')
		}
		const share = await createLinkShare(ownerRequest, '/Photos')

		await publicShare.open(share.url)
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()

		await filesListPage.getRowNameLinkForFile('image1.jpg').click()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		await expect(viewerPage.nextButton).toBeVisible()
		await expect(viewerPage.previousButton).toBeVisible()

		for (const image of ['image2.jpg', 'image3.jpg', 'image4.jpg']) {
			await viewerPage.next()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(image)
			await viewerPage.expectHandler('image')
		}
	})
})
