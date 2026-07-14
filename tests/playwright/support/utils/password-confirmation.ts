/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * Handle the password confirmation dialog if it appears
 *
 * @param page - The Playwright page object
 * @param password - The password to enter (default: 'admin')
 */
export async function handlePasswordConfirmation(page: Page, password = 'admin') {
	const dialog = page.locator('.modal-container:has-text("Authentication required")')

	await expect(dialog).toBeVisible({ timeout: 500 })
		.then(async () => {
			await dialog.locator('input[type="password"]').fill(password)
			await dialog.getByRole('button', { name: 'Confirm' }).click()
			await expect(dialog).toBeHidden()
		})
		.catch(() => {
			// Dialog didn't appear — some operations don't require confirmation.
		})
}
