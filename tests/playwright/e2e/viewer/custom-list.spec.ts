/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { expect, test } from '../../support/fixtures/viewer-page.ts'

/**
 * The viewer can be handed a list of its own rather than the folder it was
 * opened from: Photos and the versions sidebar both do it. These are the
 * Playwright port of the old app's `images-custom-list` specs.
 */

/** Minimum a file needs to be handed back to the viewer */
interface CapturedNode { basename: string }

/**
 * Open one file so the viewer is handed the folder listing, keep the nodes it
 * was given, then close it again. Those nodes are real, so a list built from
 * them exercises the same path a caller would.
 *
 * @param page - The test page
 */
async function captureFolderNodes(page: Page): Promise<void> {
	await page.evaluate(() => {
		const win = window as unknown as {
			__capturedList: unknown[]
			_nc_viewer_scope: Record<string, { service: { open: (...args: unknown[]) => unknown } }>
		}
		win.__capturedList = []
		const service = win._nc_viewer_scope.handlers_v1!.service
		const original = service.open.bind(service)
		// Only the first open is the folder listing; every later one is a list
		// this test handed over, so the wrapper steps aside once it has it
		service.open = (nodes: unknown, file: unknown, ...rest: unknown[]) => {
			win.__capturedList = nodes as unknown[]
			service.open = original
			return original(nodes, file, ...rest)
		}
	})
}

/**
 * Hand the viewer a list of its own, named by basename.
 *
 * @param page - The test page
 * @param names - The files to put in the list, in order
 * @param loadMore - Files to append once navigation reaches the end
 */
async function openList(page: Page, names: string[], loadMore: string[] = []): Promise<void> {
	await page.evaluate(({ wanted, more }) => {
		const win = window as unknown as {
			__capturedList: CapturedNode[]
			_nc_viewer_scope: Record<string, { service: { open: (...args: unknown[]) => Promise<void> } }>
		}
		const pick = (names: string[]) => names
			.map((name) => win.__capturedList.find((node) => node.basename === name))
			.filter((node) => node !== undefined)
		const list = pick(wanted)
		let appended = false
		const marker = window as unknown as { __loadedMore?: boolean }
		marker.__loadedMore = false
		const options = more.length === 0
			? {}
			: {
					loadMore: async () => {
						if (appended) {
							return []
						}
						appended = true
						// The viewer appends what this resolves with, so the test
						// waits for the flag before asking for the next file
						marker.__loadedMore = true
						return pick(more)
					},
				}
		return win._nc_viewer_scope.handlers_v1!.service.open(list, list[0], options)
	}, { wanted: names, more: loadMore })
}

test.describe('Viewer custom list', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia, openFile, viewerPage, page }) => {
		for (const name of ['image1.jpg', 'image2.jpg', 'image3.jpg', 'image4.jpg']) {
			await uploadMedia(name, name, 'image/jpeg')
		}
		await filesListPage.open()

		await captureFolderNodes(page)
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.close()
	})

	test('pages through the list it was handed, not the folder', async ({ page, viewerPage }) => {
		// The folder holds four images; the caller offers two of them
		await openList(page, ['image1.jpg', 'image3.jpg'])
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image3.jpg')

		// image2 and image4 are in the folder but not in the list, so the end of
		// the list wraps straight back to its first entry
		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')
	})

	test('asks for more files when it runs out', async ({ page, viewerPage }) => {
		await openList(page, ['image1.jpg', 'image2.jpg'], ['image3.jpg', 'image4.jpg'])
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image1.jpg')

		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image2.jpg')

		// Reaching the last entry is what asks for more, and the viewer appends
		// them when that resolves: pressing next before it has is what a person
		// could never do and a test does every time
		await page.waitForFunction(() => (window as unknown as { __loadedMore?: boolean }).__loadedMore === true)

		// The end of the given list: what loadMore returned continues it rather
		// than wrapping round to the start
		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image3.jpg')

		await viewerPage.next()
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('image4.jpg')
	})
})
