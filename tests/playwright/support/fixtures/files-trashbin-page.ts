/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext, PlaywrightWorkerArgs } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { TrashbinListPage } from '../sections/TrashbinListPage.ts'
import { test as filesTest } from './files-page.ts'

/**
 * Build a request context authenticated as `user` via basic auth, with no
 * browser cookies (cookies would otherwise win over basic auth). Used to seed
 * data as a given user without driving the UI.
 */
function basicAuthContext(
	playwright: PlaywrightWorkerArgs['playwright'],
	baseURL: string | undefined,
	user: User,
): Promise<APIRequestContext> {
	return playwright.request.newContext({
		baseURL,
		// send: 'always' — OCS issues no Basic auth challenge, so send credentials preemptively
		httpCredentials: { username: user.userId, password: user.password, send: 'always' },
	})
}

type TrashbinFixtures = {
	/**
	 * A request context authenticated as `user` (the trashbin owner, "alice") via
	 * basic auth, with no browser cookies — used to seed the group share without
	 * the (flaky) sharing sidebar.
	 */
	aliceRequest: APIRequestContext
	/** A second user ("bob") who receives the group share and deletes a file in it. */
	bob: User
	/**
	 * A request context authenticated as `bob` via basic auth, with no browser
	 * cookies — bob deletes the shared file (and sets his display name) through it.
	 */
	bobRequest: APIRequestContext
	/** A group containing `bob`, used to share a folder with him. */
	group: string
	/** FilesListPage extended with trashbin-specific column accessors. */
	filesListPage: TrashbinListPage
}

/**
 * Files fixtures for the trashbin "file row" scenarios. The browser is logged in
 * as `user` (the owner, "alice") who views the trash; `bob` and the `group` model
 * a file deleted by a sharee. All fixtures are lazy, so the simpler single-user
 * trashbin tests pull none of this setup.
 */
export const test = filesTest.extend<TrashbinFixtures>({
	filesListPage: async ({ page }, use) => {
		await use(new TrashbinListPage(page))
	},

	aliceRequest: async ({ playwright, user, baseURL }, use) => {
		const context = await basicAuthContext(playwright, baseURL, user)
		await use(context)
		await context.dispose()
	},

	bob: async ({}, use) => {
		const bob = await createRandomUser()
		await use(bob)
		await runOcc(['user:delete', bob.userId])
	},

	bobRequest: async ({ playwright, bob, baseURL }, use) => {
		const context = await basicAuthContext(playwright, baseURL, bob)
		await use(context)
		await context.dispose()
	},

	group: async ({ bob }, use) => {
		// Derive the group id from bob's random id so parallel workers never collide
		const group = `trashbin-group-${bob.userId}`
		await runOcc(['group:add', group])
		await runOcc(['group:adduser', group, bob.userId])
		await use(group)
		await runOcc(['group:delete', group])
	},
})

export { expect } from '../matchers.ts'
