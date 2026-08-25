/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * Get a toast notification by its message.
 *
 * @param page - The Playwright page object
 * @param text - Text the toast contains
 */
export function getToast(page: Page, text: string | RegExp): Locator {
	return page
		.locator('[role="alert"], [role="status"]')
		.filter({ hasText: text })
}
