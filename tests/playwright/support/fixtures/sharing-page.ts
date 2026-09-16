/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { SharingTab } from '../sections/SharingTab.ts'
import { test as filesTest } from './files-page.ts'

type SharingFixtures = {
	/**
	 * A second account to share with. It is never logged into the browser — use
	 * {@link recipientRequest} to act as it, or log in with the harness `login()`
	 * on the page's request context to swap sessions mid-test.
	 */
	recipient: User
	/**
	 * A request context authenticated as `recipient` via basic auth, with no
	 * browser session cookies — cookies would otherwise win over basic auth and
	 * the request would run as the logged-in sharer instead.
	 */
	recipientRequest: APIRequestContext
	/** The share editor in the files sidebar. */
	sharingTab: SharingTab
}

/**
 * Files fixtures for driving the share editor: the browser is logged in as
 * `user` (the sharer) and `recipient` is a second account to share with.
 *
 * This mirrors `files-sharing-page.ts`, which is the other way round (the
 * browser is the recipient of a share seeded by `owner`) — pick whichever side
 * the spec drives through the UI.
 */
export const test = filesTest.extend<SharingFixtures>({
	recipient: async ({}, use) => {
		const recipient = await createRandomUser()
		await use(recipient)
		await runOcc(['user:delete', recipient.userId], { failOnError: false })
	},

	recipientRequest: async ({ playwright, recipient, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			// send: 'always' — the OCS API doesn't issue a Basic auth challenge, so
			// credentials must be sent preemptively (DAV would challenge, OCS won't)
			httpCredentials: { username: recipient.userId, password: recipient.password, send: 'always' },
		})
		await use(context)
		await context.dispose()
	},

	sharingTab: async ({ page }, use) => {
		await use(new SharingTab(page))
	},
})

export { expect } from '../matchers.ts'
