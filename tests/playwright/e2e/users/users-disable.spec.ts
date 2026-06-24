/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test as baseTest } from '@playwright/test'
import { type User } from '@nextcloud/e2e-test-server'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

const test = adminTest.extend<{ testUser: User }>({
	testUser: async ({}, use) => {
		const user = await createRandomUser()
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
})

test.describe('Settings: Disable and enable users', () => {
	test('can disable a user', async ({ page, testUser }) => {
		// Ensure user is enabled
		await runOcc(['user:enable', testUser.userId])

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await expect(settingsPage.userRow(testUser.userId)).toBeVisible()

		await settingsPage.openActionsMenu(testUser.userId)
		await page.getByRole('menuitem', { name: 'Disable account' }).click()

		// User should no longer be in the main list
		await expect(settingsPage.userRow(testUser.userId)).toHaveCount(0)

		// Disabled accounts nav link should now appear
		const disabledLink = settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })
		await expect(disabledLink).toBeVisible()

		// Navigate to disabled users
		await disabledLink.click()
		await expect(page).toHaveURL(/\/disabled/)

		// The disabled user should be in the list
		await settingsPage.userList().waitFor({ state: 'visible' })
		await expect(settingsPage.userRow(testUser.userId)).toBeVisible()
	})

	test('can enable a user', async ({ page, testUser }) => {
		// Ensure user is disabled
		await runOcc(['user:disable', testUser.userId])

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		// Navigate to disabled users
		const disabledLink = settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })
		await expect(disabledLink).toBeVisible()
		await disabledLink.click()
		await expect(page).toHaveURL(/\/disabled/)
		await settingsPage.userList().waitFor({ state: 'visible' })

		const waitForEnableRequest = page.waitForResponse((r) => r.request().url().match(/\/ocs\/v2\.php\/cloud\/users\/[^/]+\/enable/) !== null)
		await settingsPage.openActionsMenu(testUser.userId)
		await page.getByRole('menuitem', { name: 'Enable account' }).click()
		await waitForEnableRequest

		// Disabled accounts section should disappear (no more disabled users)
		await expect(settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })).toHaveCount(0)

		// After reload, still no disabled accounts section
		await page.reload()
		await expect(settingsPage.navigation().getByRole('link', { name: /Disabled accounts/i })).toHaveCount(0)
	})
})
