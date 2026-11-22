/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

const GUARDED_REQUEST_TIMEOUT = 30_000

/**
 * The password confirmation dialog.
 *
 * @param page - The Playwright page object
 */
export function passwordConfirmationDialog(page: Page): Locator {
	return page.getByRole('dialog', { name: 'Authentication required' })
}

/**
 * Answer the password confirmation dialog whenever it blocks this page.
 *
 * The server only asks again once the session's last confirmation has expired,
 * so a test cannot know upfront whether the dialog will show up. A locator
 * handler covers both outcomes without probing for it.
 *
 * @param page - The Playwright page object
 * @param password - The password of the account the page is logged in as
 */
export async function installPasswordConfirmationHandler(page: Page, password: string): Promise<void> {
	const dialog = passwordConfirmationDialog(page)

	await page.addLocatorHandler(dialog, async (confirmation) => {
		// password inputs have no accessible role
		await confirmation.locator('input[type="password"]').fill(password)
		await confirmation.getByRole('button', { name: 'Confirm' }).click()
	})
}

/**
 * Await a request that a password confirmation may gate.
 *
 * Locator handlers only run while Playwright performs an action or an
 * auto-waiting assertion, never while a response promise is awaited on its own,
 * so the dialog would stay unanswered until the test times out.
 *
 * @param page - The Playwright page object
 * @param pending - Response promise, registered before the request was triggered
 */
export async function awaitPasswordGuardedRequest<T>(page: Page, pending: Promise<T>): Promise<T> {
	let settled = false
	const tracked = pending.finally(() => {
		settled = true
	})
	// the poll below can give up before `pending` settles
	tracked.catch(() => {})

	const dialog = passwordConfirmationDialog(page)
	await expect(async () => {
		await expect(dialog).toBeHidden({ timeout: 1_000 })
		expect(settled, 'the guarded request did not complete').toBe(true)
	}).toPass({ timeout: GUARDED_REQUEST_TIMEOUT })

	return tracked
}
