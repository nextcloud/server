/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect, request } from '@playwright/test'
import { test } from '../../support/fixtures/personal-settings-page.ts'
import { getToast } from '../../support/utils/toast.ts'

const newPassword = 'Another-Passw0rd-for-e2e!'

test.describe('Settings: Change password', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/settings/user/security')
		await expect(page.getByRole('heading', { name: 'Password', level: 2, exact: true })).toBeVisible()
	})

	test('changes the password', async ({ page, user, baseURL }) => {
		await page.getByLabel('Current password').fill(user.password)
		await page.getByLabel('New password').fill(newPassword)
		await page.getByRole('button', { name: 'Change password' }).click()

		await expect(getToast(page, 'Saved')).toBeVisible()
		await expect(page.getByLabel('Current password')).toHaveValue('')
		await expect(page.getByLabel('New password')).toHaveValue('')

		const context = await request.newContext({ baseURL })
		await expect(login(context, user)).rejects.toThrow()
		await login(context, { ...user, password: newPassword })
		await context.dispose()
	})

	test('rejects a wrong current password', async ({ page }) => {
		await page.getByLabel('Current password').fill('not-my-password')
		await page.getByLabel('New password').fill(newPassword)
		await page.getByRole('button', { name: 'Change password' }).click()

		await expect(getToast(page, 'Wrong password')).toBeVisible()
	})
})
