/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as randomUserTest } from './random-user.ts'

/**
 * Extends the random-user fixture with a `page` logged in as that user.
 * The page runs in an isolated browser context — no admin session leaks in.
 */
export const test = randomUserTest.extend({
	page: async ({ browser, user }, use) => {
		const page = await browser.newPage()
		try {
			await login(page.request, user)
		} catch {
			// Retry once on transient auth failure
			await new Promise((resolve) => setTimeout(resolve, 800))
			await login(page.request, user)
		}
		await use(page)
	},
})
