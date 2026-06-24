/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

test.describe('Settings: Create and delete accounts', () => {
	test('can create a user with username and password', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openNewUserDialog()

		const dialog = settingsPage.newUserDialog()
		await dialog.getByLabel(/Account name/).fill('newuser-basic')
		await dialog.getByLabel(/Password/).and(page.locator('input')).fill('password123')

		await dialog.getByRole('button', { name: 'Add new account' }).click()
		await handlePasswordConfirmation(page)
		await dialog.waitFor({ state: 'hidden' })

		await expect(settingsPage.userRow('newuser-basic')).toContainText('newuser-basic')

		await runOcc(['user:delete', 'newuser-basic'])
	})

	test('can create a user with display name and email', async ({ page }) => {
		const newUserId = crypto.randomUUID()
		try {
			const settingsPage = new SettingsUsersPage(page)
			await settingsPage.open()

			await settingsPage.openNewUserDialog()

			const dialog = settingsPage.newUserDialog()
			await dialog.getByLabel(/Account name/).fill(newUserId)
			await dialog.getByLabel('Display name').fill('John Smith')
			await dialog.getByLabel(/Email/).fill('john@example.org')
			await dialog.getByLabel(/Password/).and(page.locator('input')).fill('password123')

			await dialog.getByRole('button', { name: 'Add new account' }).click()
			await handlePasswordConfirmation(page)
			await dialog.waitFor({ state: 'hidden' })

			await expect(settingsPage.userRow(newUserId)).toContainText(newUserId)
		} finally {
			await runOcc(['user:delete', newUserId])
		}
	})

	test('can delete a user', async ({ page }) => {
		const testUser = await createRandomUser()
		const settingsPage = new SettingsUsersPage(page)

		try {
			await settingsPage.open()
			await expect(settingsPage.userRow(testUser.userId)).toBeVisible()

			await settingsPage.openActionsMenu(testUser.userId)
			await page.getByRole('menuitem', { name: 'Delete account' }).click()
			await handlePasswordConfirmation(page)

			// Confirm the deletion in the confirmation dialog
			await page.getByRole('dialog').getByRole('button', { name: `Delete ${testUser.userId}` }).click()

			await expect(settingsPage.userRow(testUser.userId)).toHaveCount(0)
		} finally {
			await runOcc(['user:delete', testUser.userId]).catch(() => {})
		}
	})
})
