/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/personal-settings-page.ts'

// Without --password-from-env the token carries no login password. Fine here: the
// test only revokes it, it never authenticates with it.
async function addAppPassword(userId: string, name: string): Promise<void> {
	await runOcc(['user:auth-tokens:add', userId, '--name', name])
}

test.describe('Settings: Devices & sessions', () => {
	test('revokes every other session but keeps the current one', async ({ page, devicesSessionsPage, user }) => {
		await addAppPassword(user.userId, 'Playwright device')

		await devicesSessionsPage.open()
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(2)
		await expect(devicesSessionsPage.tokenRow('Playwright device')).toBeVisible()

		await devicesSessionsPage.revokeAllOtherSessions()

		await expect(devicesSessionsPage.tokenRow('Playwright device')).toHaveCount(0)
		await expect(devicesSessionsPage.tokenRow('This session')).toBeVisible()
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(1)

		await expect(devicesSessionsPage.revokeAllButton()).toHaveCount(0)

		// A revoked session token would redirect this reload to the login page.
		await page.reload()
		await expect(devicesSessionsPage.heading()).toBeVisible()
		await expect(devicesSessionsPage.tokenRow('This session')).toBeVisible()
	})

	test('revokes several devices at once', async ({ devicesSessionsPage, user }) => {
		await addAppPassword(user.userId, 'First device')
		await addAppPassword(user.userId, 'Second device')

		await devicesSessionsPage.open()
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(3)

		await devicesSessionsPage.revokeAllOtherSessions()

		await expect(devicesSessionsPage.tokenRows()).toHaveCount(1)
		await expect(devicesSessionsPage.tokenRow('This session')).toBeVisible()
	})

	test('offers nothing to revoke when only the current session exists', async ({ devicesSessionsPage }) => {
		await devicesSessionsPage.open()

		await expect(devicesSessionsPage.tokenRows()).toHaveCount(1)
		await expect(devicesSessionsPage.tokenRow('This session')).toBeVisible()
		await expect(devicesSessionsPage.revokeAllButton()).toHaveCount(0)
	})

	test('keeps every session when the confirmation is dismissed', async ({ devicesSessionsPage, user }) => {
		await addAppPassword(user.userId, 'Playwright device')

		await devicesSessionsPage.open()
		const dialog = await devicesSessionsPage.openRevokeAllDialog()
		await dialog.getByRole('button', { name: 'Cancel' }).click()

		await expect(dialog).toHaveCount(0)
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(2)
		await expect(devicesSessionsPage.tokenRow('Playwright device')).toBeVisible()
	})
})
