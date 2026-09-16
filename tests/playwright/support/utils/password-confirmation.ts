/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

/**
 * Handle the password confirmation dialog if it appears
 *
 * @param page - The Playwright page object
 * @param password - The password to enter (default: 'admin')
 */
export async function handlePasswordConfirmation(page: Page, password = 'admin') {
	const dialog = page.locator('.modal-container:has-text("Authentication required")')

	try {
		// Check if the dialog exists within a short timeout
		await dialog.waitFor({ state: 'visible', timeout: 500 })
	} catch {
		// Dialog didn't appear, which is fine - some operations might not require confirmation
		return
	}

	// Fill the password field
	await dialog.locator('input[type="password"]').fill(password)

	// Click the confirm button
	await dialog.getByRole('button', { name: 'Confirm' }).click()

	// Wait for the dialog to disappear
	await dialog.waitFor({ state: 'hidden' })
}
