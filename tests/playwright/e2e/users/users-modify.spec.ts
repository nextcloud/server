/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { type User } from '@nextcloud/e2e-test-server'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

const test = adminTest.extend<{ user: User }>({
	user: async ({}, use) => {
		const user = await createRandomUser()
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
})

test.describe('Settings: Change user properties', () => {
	test('can change the display name', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()
		const displayNameInput = dialog.getByLabel('Display name')
		await expect(displayNameInput).toHaveValue(user.userId)
		await displayNameInput.fill('John Doe')

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify backend
		const info = JSON.parse(await runOcc(['user:info', '--output=json', user.userId]))
		expect(info?.display_name).toBe('John Doe')
	})

	test('can change the password', async ({ page, user, context }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()
		const passwordInput = dialog.getByLabel(/New password/i).and(page.locator('input')) // hack because there is no accessible role for input fields with type=password
		await expect(passwordInput).toHaveValue('')
		await passwordInput.fill('newpassword123')

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify by logging in with the new password
		await login(context.request, { ...user, password: 'newpassword123' })
		await page.goto('/apps/dashboard')
		await expect(page).toHaveURL(/\/apps\/dashboard/)
	})

	test('can change the email address', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()
		const emailInput = dialog.getByLabel(/Email/)
		await expect(emailInput).toHaveValue('')
		await emailInput.fill('mymail@example.com')

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify backend
		const info = JSON.parse(await runOcc(['user:info', '--output=json', user.userId]))
		expect(info?.email).toBe('mymail@example.com')
	})

	test('can change the user quota to a predefined value', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()

		// Open the Quota NcSelect and choose 5 GB
		const quotaCombobox = dialog.getByRole('combobox', { name: /Quota/i })
		await quotaCombobox.click()
		await page.getByRole('option', { name: '5 GB' }).click()

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify backend
		const info = JSON.parse(await runOcc(['user:info', '--output=json', user.userId]))
		expect(info?.quota).toBe('5 GB')
	})

	test('can change the user quota to a custom value', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()

		// Type a custom value directly into the combobox
		const quotaCombobox = dialog.getByRole('combobox', { name: /Quota/i })
		await quotaCombobox.fill('4 MB')
		await quotaCombobox.press('Enter')

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify backend (stored as bytes)
		const info = JSON.parse(await runOcc(['user:info', '--output=json', user.userId]))
		expect(info?.quota).not.toBe('none')
	})

	test('can make user a subadmin of a group', async ({ page, user }) => {
		const groupName = crypto.randomUUID().slice(0, 6)
		const shortName = groupName.slice(0, 4)
		await runOcc(['group:add', groupName])

		try {
			const settingsPage = new SettingsUsersPage(page)
			await settingsPage.open()

			await settingsPage.openEditDialog(user.userId)
			const dialog = settingsPage.editUserDialog()

			// Open the subadmin NcSelect and pick the group
			const subadminCombobox = dialog.getByRole('combobox', { name: /Admin of the following groups/i })
			await subadminCombobox.click()

			const waitForSearch = page
				.waitForResponse((r) => r.request().url().includes(`ocs/v2.php/cloud/groups/details?search=${shortName}`))
			await subadminCombobox.fill(shortName)
			await waitForSearch
			await page.getByRole('option', { name: new RegExp(groupName) }).click()

			await settingsPage.saveEditDialog()

			await expect(page.getByText(/Account updated/i)).toBeVisible()

			// Verify backend via OCS API (page shares admin auth state)
			const response = await page.request.get(
				`/ocs/v2.php/cloud/users/${user.userId}/subadmins`,
				{ headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' } },
			)
			const data = await response.json()
			expect(data?.ocs?.data).toContain(groupName)
		} finally {
			await runOcc(['group:delete', groupName])
		}
	})
})
