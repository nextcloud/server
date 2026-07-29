/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * A toast message shown by `@nextcloud/dialogs`.
 *
 * Toasts are appended to the document body, so they cannot be scoped to a page
 * object's container, and they carry no accessible name — the toastify class is
 * the only stable handle.
 *
 * @param page - The page showing the toast
 * @param message - Text the toast must contain
 * @return Locator matching all toasts containing `message`
 */
export function getToast(page: Page, message: string | RegExp): Locator {
	return page.locator('.toastify').filter({ hasText: message })
}
