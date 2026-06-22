/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { type User } from '@nextcloud/e2e-test-server'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

const test = adminTest.extend<{ user: User, manager: User }>({
	user: async ({}, use) => {
		const u = await createRandomUser()
		await use(u)
		await runOcc(['user:delete', u.userId])
	},
	manager: async ({}, use) => {
		const u = await createRandomUser()
		await use(u)
		await runOcc(['user:delete', u.userId])
	},
})

test.describe('Settings: User Manager Management', () => {
	test('can assign a manager through the edit dialog', async ({ page, user, manager }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()

		const managerCombobox = dialog.getByRole('combobox', { name: /Manager/i })
		await managerCombobox.fill(manager.userId)
		await page.getByRole('option', { name: manager.userId }).click()

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify via OCS API (page shares admin auth cookies)
		const response = await page.request.get(
			`/ocs/v2.php/cloud/users/${user.userId}`,
			{ headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' } },
		)
		const data = await response.json()
		expect(data?.ocs?.data?.manager).toBe(manager.userId)
	})

	test('can remove a manager through the edit dialog', async ({ page, user, manager }) => {
		// Set manager via OCC first
		await runOcc([
			'user:setting',
			user.userId,
			'settings',
			'manager',
			`["${manager.userId}"]`,
		])

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openEditDialog(user.userId)
		const dialog = settingsPage.editUserDialog()

		// Clear the currently-set manager using the NcSelect's clear button
		await dialog.getByRole('button', { name: /Clear Selected/i }).click()

		await handlePasswordConfirmation(page)
		await settingsPage.saveEditDialog()

		await expect(page.getByText(/Account updated/i)).toBeVisible()

		// Verify backend: manager must be empty
		const response = await page.request.get(
			`/ocs/v2.php/cloud/users/${user.userId}`,
			{ headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' } },
		)
		const data = await response.json()
		expect(data?.ocs?.data?.manager).toBeFalsy()
	})
})
