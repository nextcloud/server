/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'

/**
 * The settings navigation is rendered by `apps/settings/templates/settings/frame.php`.
 * Both captions are only emitted when there is an administration section to
 * separate from, so a regular account sees neither of them.
 *
 * @param page - The page to query
 */
function settingsNavigation(page: Page) {
	return page.locator('#app-navigation')
}

userTest.describe('Settings: Access levels – regular user', () => {
	userTest('cannot see the Administration section in the settings navigation', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Settings').getByRole('link').click()
		await expect(page).toHaveURL(/\/settings\/user$/)

		const navigation = settingsNavigation(page)
		await expect(navigation).toBeVisible()
		await expect(navigation.getByRole('link', { name: /Personal info/i })).toBeVisible()
		// Regular users must not see the Administration section
		await expect(navigation.locator('#app-navigation-caption-personal')).toHaveCount(0)
		await expect(navigation.locator('#app-navigation-caption-administration')).toHaveCount(0)
	})
})

adminTest.describe('Settings: Access levels – admin user', () => {
	adminTest('can see the Administration section in the settings navigation', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Personal settings').getByRole('link').click()
		await expect(page).toHaveURL(/\/settings\/user$/)

		const navigation = settingsNavigation(page)
		await expect(navigation).toBeVisible()
		await expect(navigation.getByRole('link', { name: /Personal info/i })).toBeVisible()
		// Admins must see both sections
		await expect(navigation.locator('#app-navigation-caption-personal')).toBeVisible()
		await expect(navigation.locator('#app-navigation-caption-administration')).toBeVisible()
		await expect(navigation.getByRole('link', { name: /Overview/i })).toBeVisible()
	})
})
