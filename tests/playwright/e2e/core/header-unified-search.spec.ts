/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { UnifiedSearchPage } from '../../support/sections/UnifiedSearchPage.ts'
import { uploadContent } from '../../support/utils/dav.ts'

// A distinctive token so the files provider is the only thing that matches, which
// keeps the row set (and therefore the selection order) deterministic.
const TOKEN = 'kbdnav'

test.describe('Header: unified search keyboard navigation', () => {
	// Seed a few matching files for this test's own user, then land on the Files
	// app — a page that does not own the Ctrl+F / Ctrl+K shortcut. Read-only per
	// user, so the specs stay parallel-safe.
	test.beforeEach(async ({ page, user }) => {
		const request = page.request
		await uploadContent(request, user, 'content', 'text/plain', `/${TOKEN}-alpha.txt`)
		await uploadContent(request, user, 'content', 'text/plain', `/${TOKEN}-bravo.txt`)
		await uploadContent(request, user, 'content', 'text/plain', `/${TOKEN}-charlie.txt`)
		await page.goto('apps/files')
	})

	test('Ctrl+K focuses the header search input', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await expect(search.input()).not.toBeFocused()

		await page.keyboard.press('Control+k')

		await expect(search.input()).toBeFocused()
	})

	test('Escape drops focus from the empty resting input', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await page.keyboard.press('Control+k')
		await expect(search.input()).toBeFocused()

		// With no query the popover is closed, so the input blurs itself on Escape
		// like a native find bar (the modal only owns Escape while it is open).
		await page.keyboard.press('Escape')

		await expect(search.input()).not.toBeFocused()
	})

	test('typing auto-selects the first result', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)

		const firstOption = search.options().first()
		await expect(firstOption).toBeVisible()

		// The first row is selected on render; the input names it via aria-activedescendant.
		const firstId = await firstOption.getAttribute('id')
		await expect(search.input()).toHaveAttribute('aria-activedescendant', firstId!)
	})

	test('arrow keys move the selection while focus stays in the input', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		// Need at least two rows to have somewhere to move to.
		await expect(search.options().nth(1)).toBeVisible()
		await expect(search.input()).toHaveAttribute('aria-activedescendant', /\w/)

		const firstId = await search.input().getAttribute('aria-activedescendant')

		await page.keyboard.press('ArrowDown')
		// Selection advanced to another row and the input never lost focus.
		await expect(search.input()).not.toHaveAttribute('aria-activedescendant', firstId!)
		await expect(search.input()).toBeFocused()

		await page.keyboard.press('ArrowUp')
		await expect(search.input()).toHaveAttribute('aria-activedescendant', firstId!)
		await expect(search.input()).toBeFocused()
	})

	test('Enter opens the selected result', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		await expect(search.options().first()).toBeVisible()

		const activeId = await search.input().getAttribute('aria-activedescendant')
		// The row is an NcListItem link to the file's short URL (/f/<id>), which the
		// server redirects into the Files viewer. Grab the id and assert we land on it.
		const href = await search.option(activeId!).getByRole('link').getAttribute('href')
		const fileId = href?.match(/\/f\/(\d+)/)?.[1]
		expect(fileId).toBeTruthy()

		await page.keyboard.press('Enter')

		await expect(page).toHaveURL(new RegExp(`/files/${fileId}(?:[/?]|$)`))
	})

	test('Escape closes the open results popover', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		await expect(search.panel()).toBeVisible()

		await page.keyboard.press('Escape')

		await expect(search.panel()).toHaveCount(0)
	})
})
