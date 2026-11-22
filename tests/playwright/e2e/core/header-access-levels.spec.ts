/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'

// Regular user tests — the page fixture is logged in as a fresh random user.
userTest.describe('Header: Settings menu – regular user', () => {
	userTest('can see the basic items', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()

		// A standard installation presents exactly 6 items for regular users.
		await expect(accountMenu.entries()).toHaveCount(6)
		await expect(accountMenu.entry('View profile')).toBeVisible()
		await expect(accountMenu.entry('Set status')).toBeVisible()
		await expect(accountMenu.entry('Appearance and accessibility')).toBeVisible()
		// Regular users see "Settings" (personal settings shortcut), not the
		// separate "Personal settings" / "Administration settings" split.
		await expect(accountMenu.entry('Settings')).toBeVisible()
		await expect(accountMenu.entry('Help')).toBeVisible()
		await expect(accountMenu.entry('Log out')).toBeVisible()
	})

	userTest('cannot see admin-level items', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()

		await expect(accountMenu.entry('Users')).toHaveCount(0)
		await expect(accountMenu.entry('Administration settings')).toHaveCount(0)
	})
})

// Admin tests — the page fixture is logged in as the built-in admin user.
adminTest.describe('Header: Settings menu – admin user', () => {
	adminTest('can see the admin-level items', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()

		// A standard installation presents exactly 9 items for the admin.
		await expect(accountMenu.entries()).toHaveCount(9)
		await expect(accountMenu.entry('View profile')).toBeVisible()
		await expect(accountMenu.entry('Set status')).toBeVisible()
		await expect(accountMenu.entry('Appearance and accessibility')).toBeVisible()
		// Admins see the explicit split between personal and admin sections.
		await expect(accountMenu.entry('Personal settings')).toBeVisible()
		await expect(accountMenu.entry('Administration settings')).toBeVisible()
		await expect(accountMenu.entry('Apps')).toBeVisible()
		await expect(accountMenu.entry('Accounts')).toBeVisible()
		await expect(accountMenu.entry('Help')).toBeVisible()
		await expect(accountMenu.entry('Log out')).toBeVisible()
	})
})
