/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

import { test as baseTest } from '@playwright/test'

type AdminRequestFixtures = {
	/**
	 * A request context authenticated as the administrator via basic auth, with no
	 * browser session cookies — needed because cookies of the page under test would
	 * otherwise win over basic auth and the request would run as that account.
	 */
	adminRequest: APIRequestContext
}

export const test = baseTest.extend<AdminRequestFixtures>({
	adminRequest: async ({ playwright, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			// send: 'always' — Nextcloud does not issue a Basic auth challenge for
			// app routes, so the credentials must be sent preemptively
			httpCredentials: { username: 'admin', password: 'admin', send: 'always' },
		})
		await use(context)
		await context.dispose()
	},
})
