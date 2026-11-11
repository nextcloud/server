/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { awaitPasswordGuardedRequest } from '../../support/utils/password-confirmation.ts'

test.describe('Settings: Account management defaults', () => {
	test.afterEach(async () => {
		await runOcc(['config:app:delete', 'files', 'default_quota'])
		await runOcc(['config:app:delete', 'core', 'newUser.sendEmail'])
	})

	test('keeps the default quota', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()
		await settingsPage.openSettingsDialog()

		const quotaRequest = page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('/config/apps/files/default_quota'))
		await settingsPage.settingsDialog().getByRole('combobox', { name: 'Default quota' }).click()
		await page.getByRole('option', { name: '1 GB' }).click()
		await awaitPasswordGuardedRequest(page, quotaRequest)
		await settingsPage.closeSettingsDialog()

		await page.reload()
		await settingsPage.openSettingsDialog()
		await expect(settingsPage.settingsDialog().getByText('1 GB')).toBeVisible()
	})

	test('keeps the welcome email choice', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()
		await settingsPage.openSettingsDialog()

		const toggle = settingsPage.settingsDialog().getByRole('switch', { name: 'Send welcome email to new accounts' })
		await expect(toggle).toBeChecked()
		const preferenceRequest = page.waitForResponse((r) => r.url().includes('/settings/users/preferences/newUser.sendEmail'))
		// The native input of the switch is visually hidden
		await toggle.uncheck({ force: true })
		await preferenceRequest
		await settingsPage.closeSettingsDialog()

		await page.reload()
		await settingsPage.openSettingsDialog()
		await expect(toggle).not.toBeChecked()
	})
})
