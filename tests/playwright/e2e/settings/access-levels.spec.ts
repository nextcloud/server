/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'

userTest.describe('Settings: Access levels – regular user', () => {
	userTest('cannot see the Administration section in the settings navigation', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Settings').getByRole('link').click()
		await expect(page).toHaveURL(/\/settings\/user$/)

		const appNavigation = page.locator('#app-navigation-vue')
		await expect(appNavigation.getByRole('list', { name: 'Personal' })).toBeVisible()
		await expect(appNavigation.getByRole('link', { name: /Personal info/i })).toBeVisible()
		// Regular users must not see the Administration section
		await expect(appNavigation.getByRole('list', { name: 'Administration' })).toHaveCount(0)
	})
})

adminTest.describe('Settings: Access levels – admin user', () => {
	adminTest('can see the Administration section in the settings navigation', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Personal settings').getByRole('link').click()
		await expect(page).toHaveURL(/\/settings\/user$/)

		const appNavigation = page.locator('#app-navigation-vue')
		await expect(appNavigation.getByRole('list', { name: 'Personal' })).toBeVisible()
		await expect(appNavigation.getByRole('link', { name: /Personal info/i })).toBeVisible()
		// Admins must see the Administration section
		await expect(appNavigation.getByRole('list', { name: 'Administration' })).toBeVisible()
		await expect(appNavigation.getByRole('link', { name: /Overview/i })).toBeVisible()
	})
})
