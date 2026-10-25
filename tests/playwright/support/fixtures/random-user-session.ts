/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as randomUserTest } from './random-user.ts'

/**
 * Extends the random-user fixture with a `page` logged in as that user.
 * The page runs in an isolated browser context — no admin session leaks in.
 *
 * The page (and with it the context `browser.newPage()` created for it) is
 * closed in teardown: the runner only closes contexts of the built-in `context`
 * fixture, so a page left open here would keep living in the worker's browser
 * for every following test — each one still polling the server — and the
 * failure snapshot Playwright attaches is taken from the oldest open context,
 * i.e. from some earlier test's page instead of the failing one.
 */
export const test = randomUserTest.extend({
	page: async ({ browser, user }, use) => {
		const page = await browser.newPage()
		try {
			await login(page.request, user)
		} catch (error) {
			console.info('Failed to authenticate as random user, retrying', error)
			await new Promise((resolve) => setTimeout(resolve, 800))
			await login(page.request, user)
		}
		await use(page)
		await page.close()
	},
})
