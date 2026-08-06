/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * The fixed per-item heights the files virtual list renders with, mirroring the
 * `itemHeight` getter in `apps/files/src/components/VirtualList.vue`. Using the
 * product's own constants keeps the viewport maths in step with the component
 * instead of measuring a rendered row (which is unreliable in grid mode).
 */
const ITEM_HEIGHT = { list: 44, grid: 166 + 32 + 16 + 16 + 16 }

/** The list widths that yield a stable column count: 1 column in list mode, 3 in grid mode. */
const WIDTH = { list: 1280, grid: 768 }

/**
 * Size the viewport so the files list shows exactly `rows` item rows, forcing the
 * rest to be virtualized off-screen. This makes "scroll to the selected file"
 * assertions deterministic regardless of the runner's default window size.
 *
 * The list fills a flex area, so the chrome above/around it is constant: we
 * measure it once, then set the height to `chrome + stickyHeader + rows * itemHeight`.
 * The viewport persists across navigations, so callers set it once before visiting.
 *
 * @param page - The Playwright page
 * @param rows - The number of item rows that should fit
 * @param gridMode - Whether the list is in grid mode (taller items, 3 columns)
 */
export async function fitFilesListToRows(page: Page, rows: number, gridMode = false): Promise<void> {
	const width = gridMode ? WIDTH.grid : WIDTH.list
	const itemHeight = gridMode ? ITEM_HEIGHT.grid : ITEM_HEIGHT.list

	await page.setViewportSize({ width, height: 720 })
	await page.locator('[data-cy-files-list-tbody] tr').first().waitFor({ state: 'visible' })

	// `visibleRows` in VirtualList is floor((tableHeight - thead - filters) / itemHeight),
	// where tableHeight is the list's own height and the chrome around it is fixed.
	const chromeAndHeader = await page.evaluate(() => {
		const height = (selector: string) => document.querySelector<HTMLElement>(selector)?.offsetHeight ?? 0
		const list = document.querySelector<HTMLElement>('[data-cy-files-list]')
		const chrome = window.innerHeight - (list?.offsetHeight ?? 0)
		return chrome + height('[data-cy-files-list-thead]') + height('.files-list__filters')
	})

	await page.setViewportSize({ width, height: Math.ceil(chromeAndHeader + rows * itemHeight) })
}

/**
 * Whether the locator's box lies fully within the current viewport (top-to-bottom
 * and left-to-right). Playwright's `toBeVisible` only checks the DOM/paint state,
 * not the scroll position, so this is what tells a scrolled-off buffered row from
 * a genuinely on-screen one.
 *
 * @param locator - The element to test
 */
export async function isFullyInViewport(locator: Locator): Promise<boolean> {
	await expect(locator).toHaveCount(1) // ensure the locator resolves to a single element

	const box = await locator.boundingBox()
	if (!box) {
		return false
	}
	const size = locator.page().viewportSize()
	if (!size) {
		return false
	}
	return box.x >= 0
		&& box.y >= 0
		&& box.x + box.width <= size.width
		&& box.y + box.height <= size.height
}
