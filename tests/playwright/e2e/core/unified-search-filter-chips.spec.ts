/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { UnifiedSearchPage } from '../../support/sections/UnifiedSearchPage.ts'

test.describe('Header: unified search filter chips', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('apps/files')
	})

	test('shows an accessible remove button on applied date filter chips', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.openWithQuery()
		await search.applyDateFilter('Today')

		await expect(search.filterChip('Today')).toBeVisible()
		await expect(search.filterChip('Today').getByRole('button', { name: 'Remove filter: Today' })).toBeVisible()
	})

	test('removes a date filter when clicking the chip remove button', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.openWithQuery()
		await search.applyDateFilter('Last 7 days')

		await expect(search.filterChip('Last 7 days')).toBeVisible()

		await search.removeFilterChip('Last 7 days')

		await expect(search.filterChip('Last 7 days')).toHaveCount(0)
	})

	test('removes a date filter when activating the remove button with Space', async ({ page }) => {
		const search = new UnifiedSearchPage(page)
		await search.openWithQuery()
		await search.applyDateFilter('Today')

		const removeButton = search.filterChip('Today').getByRole('button', { name: 'Remove filter: Today' })
		await removeButton.press('Space')

		await expect(search.filterChip('Today')).toHaveCount(0)
	})
})
