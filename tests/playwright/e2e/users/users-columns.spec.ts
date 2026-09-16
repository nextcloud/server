/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

test.describe('Settings: Show and hide columns', () => {
	test.beforeEach(async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		// Reset: open settings, uncheck all optional columns, re-enable last-login
		await settingsPage.openSettingsDialog()
		const dialog = settingsPage.settingsDialog()

		// Uncheck both optional columns
		for (const name of ['Show language', 'Show last login']) {
			const checkbox = dialog.getByRole('checkbox', { name })
			if (await checkbox.isChecked()) {
				await checkbox.uncheck({ force: true })
			}
		}

		// Re-enable last-login so each test starts from a known baseline
		await dialog.getByRole('checkbox', { name: 'Show last login' }).check({ force: true })
		await settingsPage.closeSettingsDialog()
	})

	test('can show the Language column', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)

		// Language column must not be visible before the toggle
		await expect(page.getByRole('columnheader', { name: /Language/i })).toHaveCount(0)
		await expect(page.locator('[data-cy-user-list-cell-language]').first()).toHaveCount(0)

		await settingsPage.openSettingsDialog()
		const dialog = settingsPage.settingsDialog()
		const checkbox = dialog.getByRole('checkbox', { name: 'Show language' })
		await expect(checkbox).not.toBeChecked()
		await checkbox.check({ force: true })
		await expect(checkbox).toBeChecked()
		await settingsPage.closeSettingsDialog()

		// Language column header must now be visible
		await expect(page.getByRole('columnheader', { name: /Language/i })).toBeVisible()
		// Every row must have a language cell
		await expect(page.locator('[data-cy-user-list-cell-language]').first()).toBeVisible()

		// Reload to verify the preference is persisted (stored in DB, not just localStorage)
		await page.evaluate(() => localStorage.clear())
		await page.reload()
		await expect(page.getByRole('columnheader', { name: /Language/i })).toBeVisible()
	})

	test('can hide the Last login column', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)

		// Last login column must be visible (enabled in beforeEach)
		await expect(page.getByRole('columnheader', { name: /Last login/i })).toBeVisible()
		await expect(page.locator('[data-cy-user-list-cell-last-login]').first()).toBeVisible()

		await settingsPage.openSettingsDialog()
		const dialog = settingsPage.settingsDialog()
		const checkbox = dialog.getByRole('checkbox', { name: 'Show last login' })
		await expect(checkbox).toBeChecked()
		await checkbox.uncheck({ force: true })
		await expect(checkbox).not.toBeChecked()
		await settingsPage.closeSettingsDialog()

		// Column header must now be gone
		await expect(page.getByRole('columnheader', { name: /Last login/i })).toHaveCount(0)
		await expect(page.locator('[data-cy-user-list-cell-last-login]').first()).toHaveCount(0)
	})
})
