/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * Get a toast notification by its message.
 *
 * The toasts of `@nextcloud/dialogs` 7.x are rendered by `toastify-js`,
 * which only sets `aria-live` on the toast but no role, so match the toast element itself.
 *
 * @param page - The Playwright page object
 * @param text - Text the toast contains
 */
export function getToast(page: Page, text: string | RegExp): Locator {
	return page
		.locator('.toastify')
		.filter({ hasText: text })
}
