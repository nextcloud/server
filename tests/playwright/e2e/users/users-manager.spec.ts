/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { type User } from '@nextcloud/e2e-test-server'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test as adminUserTest } from '../../support/fixtures/admin-with-user.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

const test = adminUserTest.extend<{ manager: User }>({
	manager: async ({}, use) => {
		const manager = await createRandomUser()
		await use(manager)
		await runOcc(['user:delete', manager.userId]).catch(() => {})
	},
})

test.describe('Settings: User Manager Management', () => {
	test('can assign a manager in the user row', async ({ page, user, manager }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'manager')
		await cell.scrollIntoViewIfNeeded()
		await expect(cell.locator('.vs__selected')).toHaveCount(0)

		const updateRequest = page.waitForResponse((response) =>
			response.url().includes(`/ocs/v2.php/cloud/users/${user.userId}`) && response.request().method() === 'PUT')

		// The manager select is appended to the body, so its options live outside the row
		const managerCombobox = cell.getByRole('combobox', { name: 'Set line manager' })
		await managerCombobox.click({ force: true })
		await managerCombobox.fill(manager.userId)
		await page.getByRole('option', { name: manager.userId }).click({ force: true })

		await handlePasswordConfirmation(page)
		await updateRequest

		await expect(cell.locator('.vs__selected')).toContainText(manager.userId)

		// Verify via OCS API (page shares admin auth cookies)
		const response = await page.request.get(
			`/ocs/v2.php/cloud/users/${user.userId}`,
			{ headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' } },
		)
		const data = await response.json()
		expect(data?.ocs?.data?.manager).toBe(manager.userId)
	})

	test('can remove a manager in the user row', async ({ page, user, manager }) => {
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

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'manager')
		await cell.scrollIntoViewIfNeeded()
		await expect(cell.locator('.vs__selected')).toContainText(manager.userId)

		const updateRequest = page.waitForResponse((response) =>
			response.url().includes(`/ocs/v2.php/cloud/users/${user.userId}`) && response.request().method() === 'PUT')

		// Clear the currently-set manager using the NcSelect's clear button
		await cell.getByRole('button', { name: /Clear Selected/i }).click({ force: true })

		await handlePasswordConfirmation(page)
		await updateRequest

		await expect(cell.locator('.vs__selected')).toHaveCount(0)

		// Verify backend: manager must be empty
		const response = await page.request.get(
			`/ocs/v2.php/cloud/users/${user.userId}`,
			{ headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' } },
		)
		const data = await response.json()
		expect(data?.ocs?.data?.manager).toBeFalsy()
	})
})
