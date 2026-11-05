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
	test.beforeEach(async ({ passwordPage }) => {
		await passwordPage.open()
	})

	test('changes the password', async ({ page, passwordPage, user, baseURL }) => {
		await passwordPage.changePassword(user.password, newPassword)

		await expect(getToast(page, 'Saved')).toBeVisible()
		await expect(passwordPage.currentPasswordInput()).toHaveValue('')
		await expect(passwordPage.newPasswordInput()).toHaveValue('')

		const context = await request.newContext({ baseURL })
		await expect(login(context, user)).rejects.toThrow()
		await login(context, { ...user, password: newPassword })
		await context.dispose()
	})

	test('rejects a wrong current password', async ({ page, passwordPage }) => {
		await passwordPage.changePassword('not-my-password', newPassword)

		await expect(getToast(page, 'Wrong password')).toBeVisible()
	})
})
