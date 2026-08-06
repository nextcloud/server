/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { NavigationHeaderPage } from '../../support/sections/NavigationHeaderPage.ts'

// Regular-user tests — logged in as a fresh random user.
userTest.describe('Header: App menu (waffle launcher) – regular user', () => {
	userTest('opens the popover and navigates when a tile is clicked', async ({ page }) => {
		await page.goto('/')
		const navigationHeader = new NavigationHeaderPage(page)

		await navigationHeader.openMenu()
		await expect(navigationHeader.popover()).toBeVisible()

		const firstEntry = navigationHeader.navigationEntries().first()
		await expect(firstEntry).toBeVisible()

		const href = await firstEntry.getAttribute('href')
		expect(href).toMatch(/\/apps\//)

		await firstEntry.click()
		await expect(page).toHaveURL(/\/apps\//)
	})

	userTest('has the correct app navigation items', async ({ page }) => {
		await page.goto('/')
		const navigationHeader = new NavigationHeaderPage(page)
		await expectWaffleMenuContainsApps(navigationHeader, [
			{ name: 'Files', href: '/apps/files' },
			{ name: 'Dashboard', href: '/apps/dashboard' },
		])
	})
})

// Admin tests — logged in as the built-in admin user.
adminTest.describe('Header: App menu (waffle launcher) – admin', () => {
	adminTest('shows the "More apps" tile for admins', async ({ page }) => {
		await page.goto('/')
		const navigationHeader = new NavigationHeaderPage(page)
		await navigationHeader.openMenu()

		await expect(navigationHeader.popover()).toBeVisible()
		await expect(navigationHeader.popover().getByRole('menuitem', { name: 'More apps' })).toBeVisible()
	})

	adminTest('has the correct app navigation items', async ({ page }) => {
		await page.goto('/')
		const navigationHeader = new NavigationHeaderPage(page)
		await expectWaffleMenuContainsApps(navigationHeader, [
			{ name: 'Files', href: '/apps/files' },
			{ name: 'Dashboard', href: '/apps/dashboard' },
			{ name: 'Appstore', href: '/settings/apps' },
		])
	})
})

/**
 * Open the waffle menu and assert that each expected app is present
 * with a matching name and href.
 */
async function expectWaffleMenuContainsApps(
	navigationHeader: NavigationHeaderPage,
	apps: Array<{ name: string, href: string }>,
): Promise<void> {
	await navigationHeader.openMenu()
	await expect(navigationHeader.popover()).toBeVisible()

	for (const app of apps) {
		const entry = navigationHeader.navigationEntries().filter({ hasText: app.name })
		await expect(entry).toBeVisible()
		const href = await entry.getAttribute('href')
		// href may include a query string or a trailing slash
		expect(href).toMatch(new RegExp(`${app.href}(\\?.+|/?$)`))
	}
}
