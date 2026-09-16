/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-with-user.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

test.describe('Settings: Change user properties', () => {
	test('can change the display name', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'displayname')
		await expect(cell.locator('input')).toHaveValue(user.userId)

		await settingsPage.submitInlineTextField(user.userId, 'displayname', 'John Doe')

		await expect(page.getByText(/Display name was successfully changed/i)).toBeVisible()

		// Verify backend
		const { stdout: jsonList } = await runOcc(['user:info', '--output=json', user.userId])
		const info = JSON.parse(jsonList)
		expect(info?.display_name).toBe('John Doe')
	})

	test('can change the password', async ({ page, user, context }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'password')
		await expect(cell.locator('input')).toHaveValue('')

		await settingsPage.submitInlineTextField(user.userId, 'password', 'newpassword123')

		await expect(page.getByText(/Password was successfully changed/i)).toBeVisible()
		// The password input is emptied once the change went through
		await expect(cell.locator('input')).toHaveValue('')

		// Verify by logging in with the new password
		await login(context.request, { ...user, password: 'newpassword123' })
		await page.goto('/apps/dashboard')
		await expect(page).toHaveURL(/\/apps\/dashboard/)
	})

	test('can change the email address', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'email')
		await expect(cell.locator('input')).toHaveValue('')

		await settingsPage.submitInlineTextField(user.userId, 'email', 'mymail@example.com')

		await expect(page.getByText(/Email was successfully changed/i)).toBeVisible()

		// Verify backend
		const { stdout: jsonList } = await runOcc(['user:info', '--output=json', user.userId])
		const info = JSON.parse(jsonList)
		expect(info?.email).toBe('mymail@example.com')
	})

	test('can change the user quota to a predefined value', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'quota')
		await cell.scrollIntoViewIfNeeded()
		await expect(cell.locator('.vs__selected')).toContainText('Unlimited')

		// The quota select is not appended to the body, so its options stay inside the cell
		const updateRequest = page.waitForResponse((response) =>
			response.url().includes(`/ocs/v2.php/cloud/users/${user.userId}`) && response.request().method() === 'PUT')
		await cell.getByRole('combobox', { name: 'Select account quota' }).click({ force: true })
		await cell.getByRole('option', { name: '5 GB' }).click({ force: true })

		await handlePasswordConfirmation(page)
		await updateRequest

		await expect(cell.locator('.vs__selected')).toContainText('5 GB')

		// Verify backend
		const { stdout: jsonList } = await runOcc(['user:info', '--output=json', user.userId])
		const info = JSON.parse(jsonList)
		expect(info?.quota).toBe('5 GB')
	})

	test('can change the user quota to a custom value', async ({ page, user }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openInlineEdit(user.userId)
		const cell = settingsPage.userRowCell(user.userId, 'quota')
		await cell.scrollIntoViewIfNeeded()
		await expect(cell.locator('.vs__selected')).toContainText('Unlimited')

		const updateRequest = page.waitForResponse((response) =>
			response.url().includes(`/ocs/v2.php/cloud/users/${user.userId}`) && response.request().method() === 'PUT')
		const quotaCombobox = cell.getByRole('combobox', { name: 'Select account quota' })
		await quotaCombobox.fill('4 MB')
		await quotaCombobox.press('Enter')

		await handlePasswordConfirmation(page)
		await updateRequest

		// Verify backend
		const { stdout: jsonList } = await runOcc(['user:info', '--output=json', user.userId])
		const info = JSON.parse(jsonList)
		expect(info?.quota).not.toBe('none')
	})

	test('can make user a subadmin of a group', async ({ page, user }) => {
		const groupName = crypto.randomUUID().slice(0, 6)
		const shortName = groupName.slice(0, 4)
		await runOcc(['group:add', groupName])

		try {
			const settingsPage = new SettingsUsersPage(page)
			await settingsPage.open()

			await settingsPage.openInlineEdit(user.userId)
			const cell = settingsPage.userRowCell(user.userId, 'subadmins')
			await cell.scrollIntoViewIfNeeded()
			await expect(cell.locator('.vs__selected')).toHaveCount(0)

			const subadminCombobox = cell.getByRole('combobox', { name: 'Set account as admin for' })
			await subadminCombobox.click({ force: true })

			const waitForSearch = page
				.waitForResponse((r) => r.request().url().includes(`ocs/v2.php/cloud/groups/details?search=${shortName}`))
			await subadminCombobox.fill(shortName)
			await waitForSearch

			await cell.getByRole('option', { name: new RegExp(groupName) }).click({ force: true })
			await handlePasswordConfirmation(page)

			await expect(cell.locator('.vs__selected')).toContainText(groupName)

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
