/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MediaKind } from '../../support/sections/ViewerPage.ts'

import { expect, test } from '../../support/fixtures/viewer-page.ts'

export interface MediaItem {
	/** Fixture file name in `e2e/viewer/fixtures`. */
	fixture: string
	/** Content type used when uploading the fixture. */
	mime: string
	/** Media kind handled by the viewer. */
	kind: MediaKind
	/** The handler custom element tag name. */
	handlerTag: string
	/**
	 * If set, the standard tests are skipped with this reason.
	 * Used for codecs unsupported by Playwright's bundled Chromium.
	 */
	skip?: string
}

/** Images ported from the Cypress image specs and the image mixin. */
export const IMAGE_MEDIA: MediaItem[] = [
	{ fixture: 'image1.jpg', mime: 'image/jpeg', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image.png', mime: 'image/png', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image.gif', mime: 'image/gif', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image.webp', mime: 'image/webp', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image.svg', mime: 'image/svg+xml', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image.ico', mime: 'image/x-icon', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image-apng.png', mime: 'image/png', kind: 'image', handlerTag: 'oca-viewer-image' },
	{ fixture: 'image-small.png', mime: 'image/png', kind: 'image', handlerTag: 'oca-viewer-image' },
]

/** Videos ported from the Cypress video specs and the video mixin. */
export const VIDEO_MEDIA: MediaItem[] = [
	{ fixture: 'video1.mp4', mime: 'video/mp4', kind: 'video', handlerTag: 'oca-viewer-video' },
	{ fixture: 'video.webm', mime: 'video/webm', kind: 'video', handlerTag: 'oca-viewer-video' },
	{ fixture: 'video.mkv', mime: 'video/x-matroska', kind: 'video', handlerTag: 'oca-viewer-video' },
	{
		fixture: 'video.ogv',
		mime: 'video/ogg',
		kind: 'video',
		handlerTag: 'oca-viewer-video',
		// Ogg/Theora is not supported by Playwright's bundled Chromium.
		skip: 'Ogg/Theora codec is unsupported by Playwright bundled Chromium',
	},
]

/** Audios ported from the Cypress audio specs and the audio mixin. */
export const AUDIO_MEDIA: MediaItem[] = [
	{ fixture: 'audio.mp3', mime: 'audio/mpeg', kind: 'audio', handlerTag: 'oca-viewer-audio' },
	{ fixture: 'audio.ogg', mime: 'audio/ogg', kind: 'audio', handlerTag: 'oca-viewer-audio' },
]

/**
 * Register the standard open → load → assert → close test for a media item.
 *
 * This is the Playwright port of the Cypress `image`, `video` and `audio`
 * mixins: it uploads a single fixture, opens it from the files list, waits for
 * the viewer to finish loading, asserts the correct handler is rendered and
 * that a single file shows no navigation arrows, then closes the viewer.
 *
 * @param media - The media items to generate tests for
 */
export function createMediaViewerTests(media: MediaItem[]): void {
	for (const item of media) {
		test(`opens ${item.fixture} in the viewer`, async ({ filesListPage, viewerPage, uploadMedia, openFile }) => {
			test.skip(Boolean(item.skip), item.skip ?? '')

			await uploadMedia(item.fixture, item.fixture, item.mime)
			await filesListPage.open()

			await expect(filesListPage.getRowForFile(item.fixture)).toBeVisible()

			await openFile(item.fixture)
			await viewerPage.waitForOpen()

			expect(await viewerPage.currentName()).toBe(item.fixture)
			await viewerPage.expectHandler(item.kind)

			await viewerPage.close()
		})
	}
}
