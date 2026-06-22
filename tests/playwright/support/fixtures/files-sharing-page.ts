/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import type { APIRequestContext } from '@playwright/test'
import type { User } from '@nextcloud/e2e-test-server'
import { test as filesTest } from './files-page.ts'

type SharingFixtures = {
	owner: User
	/**
	 * A request context authenticated as `owner` via basic auth, with no browser
	 * session cookies — needed because cookies would otherwise win over basic auth
	 * and the seeding would run as the logged-in recipient instead.
	 */
	ownerRequest: APIRequestContext
}

/**
 * Files fixtures plus a second `owner` user. The browser is logged in as `user`
 * (the share recipient); `owner` owns and shares the folder via `ownerRequest`
 * and is never logged into the page.
 */
export const test = filesTest.extend<SharingFixtures>({
	owner: async ({}, use) => {
		const owner = await createRandomUser()
		await use(owner)
		await runOcc(['user:delete', owner.userId])
	},

	ownerRequest: async ({ playwright, owner, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			// send: 'always' — the OCS API doesn't issue a Basic auth challenge, so
			// credentials must be sent preemptively (DAV would challenge, OCS won't)
			httpCredentials: { username: owner.userId, password: owner.password, send: 'always' },
		})
		await use(context)
		await context.dispose()
	},
})

export { expect } from '../matchers.ts'
