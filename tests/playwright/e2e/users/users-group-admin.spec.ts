/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest, expect } from '@playwright/test'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

const test = baseTest.extend<{ subadmin: User, group: string }>({
	group: async ({}, use) => {
		const groupName = crypto.randomUUID()
		await runOcc(['group:add', groupName])
		await use(groupName)
		await runOcc(['group:delete', groupName]).catch(() => {})
	},
	subadmin: async ({ group, request }, use) => {
		const user = await createRandomUser()
		await runOcc(['group:adduser', group, user.userId])
		// Grant subadmin rights via OCS API authenticated as admin
		await request.post(`/ocs/v2.php/cloud/users/${user.userId}/subadmins`, {
			headers: {
				'OCS-APIRequest': 'true',
				Authorization: 'Basic ' + Buffer.from('admin:admin').toString('base64'),
			},
			form: { groupid: group },
		})
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
})

test.describe('Settings: Create accounts as a group admin', () => {
	test('can create a user with the group pre-filled', async ({ page, context, subadmin, group }) => {
		// Log in as the subadmin (not as admin)
		await login(context.request, subadmin)

		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		await settingsPage.openNewUserDialog()
		const dialog = settingsPage.newUserDialog()

		// The subadmin's single group must be pre-selected in the groups field.
		// NcSelect renders selected values as .vs__selected (no accessible role).
		await expect(dialog.locator('.vs__selected').filter({ hasText: group })).toBeVisible()

		// Fill in the new user details and submit
		const newUserId = crypto.randomUUID()
		await dialog.getByLabel(/Account name/).fill(newUserId)
		await dialog.getByLabel(/Password/).and(page.locator('input')).fill('password123')

		await dialog.getByRole('button', { name: 'Add new account' }).click()
		await handlePasswordConfirmation(page, subadmin.password)
		await dialog.waitFor({ state: 'hidden' })

		await expect(settingsPage.userRow(newUserId)).toContainText(newUserId)

		await runOcc(['user:delete', newUserId])
	})
})
