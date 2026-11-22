/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-with-user.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

test.describe('Settings: Disable and enable users', () => {
	test('can disable a user', async ({ page, user }) => {
		// Ensure user is enabled
		await runOcc(['user:enable', user.userId])

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await expect(settingsPage.userRow(user.userId)).toBeVisible()

		await settingsPage.openActionsMenu(user.userId)
		await page.getByRole('menuitem', { name: 'Disable account' }).click()

		// User should no longer be in the main list
		await expect(settingsPage.userRow(user.userId)).toHaveCount(0)

		// Disabled accounts nav link should now appear
		const disabledLink = settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })
		await expect(disabledLink).toBeVisible()

		// Navigate to disabled users
		await disabledLink.click()
		await expect(page).toHaveURL(/\/disabled/)

		// The disabled user should be in the list
		await settingsPage.userList().waitFor({ state: 'visible' })
		await expect(settingsPage.userRow(user.userId)).toBeVisible()
	})

	test('can enable a user', async ({ page, user }) => {
		// Ensure user is disabled
		await runOcc(['user:disable', user.userId])

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		// Navigate to disabled users
		const disabledLink = settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })
		await expect(disabledLink).toBeVisible()
		await disabledLink.click()
		await expect(page).toHaveURL(/\/disabled/)
		await settingsPage.userList().waitFor({ state: 'visible' })

		const waitForEnableRequest = page.waitForResponse((r) => r.request().url().match(/\/ocs\/v2\.php\/cloud\/users\/[^/]+\/enable/) !== null)
		await settingsPage.openActionsMenu(user.userId)
		await page.getByRole('menuitem', { name: 'Enable account' }).click()
		await waitForEnableRequest

		// Disabled accounts section should disappear (no more disabled users)
		await expect(settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })).toHaveCount(0)

		// After reload, still no disabled accounts section
		await page.reload()
		await expect(settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })).toHaveCount(0)
	})
})
