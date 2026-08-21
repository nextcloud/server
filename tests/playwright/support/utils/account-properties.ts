/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page, Response } from '@playwright/test'

import { handlePasswordConfirmation } from './password-confirmation.ts'

/**
 * Wait for the request that persists an account property or its scope.
 *
 * Register the listener before triggering the change - account properties are
 * saved with a debounce - and await it after the change and any password
 * confirmation.
 *
 * @param page - The Playwright page object
 */
export function waitForPropertySave(page: Page): Promise<Response> {
	return page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().includes('/ocs/v2.php/cloud/users/'))
}

/**
 * Wait for the request that persists the profile visibility of a property.
 *
 * @param page - The Playwright page object
 */
export function waitForVisibilitySave(page: Page): Promise<Response> {
	return page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().includes('/ocs/v2.php/profile/'))
}

/**
 * Perform an account-property change and wait for its save request.
 *
 * @param page - The Playwright page object
 * @param password - Password used when confirmation is requested
 * @param change - Action that triggers the save request
 * @param waitForSave - Request matcher for the property being changed
 */
export async function saveAccountProperty(
	page: Page,
	password: string,
	change: () => Promise<void>,
	waitForSave: (page: Page) => Promise<Response> = waitForPropertySave,
): Promise<void> {
	const saved = waitForSave(page)
	await change()
	await handlePasswordConfirmation(page, password)
	await saved
}
