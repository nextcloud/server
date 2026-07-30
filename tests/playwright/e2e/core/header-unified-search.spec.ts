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
		// A row rendering is not enough: a provider settling later re-renders the
		// list and re-establishes the selection, so read it only once it is final.
		await search.waitForSettledResults()

		const firstOption = search.options().first()
		await expect(firstOption).toBeVisible()

		// The first row is selected on render; the input names it via aria-activedescendant.
		const firstId = await firstOption.getAttribute('id')
		await expect(search.input()).toHaveAttribute('aria-activedescendant', firstId!)
	})

	test('arrow keys move the selection while focus stays in the input', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		await search.waitForSettledResults()
		// Need at least two rows to have somewhere to move to.
		await expect(search.options().nth(1)).toBeVisible()

		const firstId = await search.activeOptionId()

		await page.keyboard.press('ArrowDown')
		// Selection advanced to another row and the input never lost focus.
		await expect(search.input()).not.toHaveAttribute('aria-activedescendant', firstId)
		await expect(search.input()).toBeFocused()

		await page.keyboard.press('ArrowUp')
		await expect(search.input()).toHaveAttribute('aria-activedescendant', firstId)
		await expect(search.input()).toBeFocused()
	})

	test('Enter opens the selected result', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		// Enter activates whatever is selected *at that moment* and does nothing at
		// all when the list is mid-flight (no selection), so the search has to have
		// settled before the key is pressed.
		await search.waitForSettledResults()

		const activeId = await search.activeOptionId()
		// The row is an NcListItem link to the file's short URL (/f/<id>), which the
		// server redirects into the Files viewer. Grab the id and assert we land on it.
		const href = await search.option(activeId).getByRole('link').getAttribute('href')
		const fileId = href?.match(/\/f\/(\d+)/)?.[1]
		expect(fileId).toBeTruthy()

		// Arm the wait before the key press: the URL is only reached after a
		// server-side redirect, which outlives the default assertion timeout on a
		// loaded CI machine.
		const opened = page.waitForURL(new RegExp(`/files/${fileId}(?:[/?]|$)`), { timeout: 15_000 })
		await page.keyboard.press('Enter')
		await opened
	})

	test('Escape closes the open results popover', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		await expect(search.panel()).toBeVisible()

		await page.keyboard.press('Escape')

		await expect(search.panel()).toHaveCount(0)
	})

	test('closing a funnel-opened popover returns focus to the input', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await page.keyboard.press('Control+k')
		await expect(search.input()).toBeFocused()

		// Reveal the filters from the pre-typing funnel, then close with Escape.
		await search.filterToggle().click()
		await expect(search.filters()).toBeVisible()

		await page.keyboard.press('Escape')

		// The trap must hand focus back to the header input, not <body>. Only a real
		// browser can prove this (jsdom focus is unreliable), so it belongs here.
		await expect(search.input()).toBeFocused()
		await expect(search.panel()).toHaveCount(0)
	})

	test('the trailing X clears a query, then dismisses the empty field', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.input().fill(TOKEN)
		await expect(search.panel()).toBeVisible()

		// With a query the X clears the text and keeps focus for continued typing.
		await search.clearButton().click()
		await expect(search.input()).toHaveValue('')
		await expect(search.input()).toBeFocused()

		// Empty but still focused, the same X now dismisses: focus leaves, popover closes.
		await search.closeButton().click()
		await expect(search.input()).not.toBeFocused()
		await expect(search.panel()).toHaveCount(0)
	})
})
