/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

import { CopyMoveDialogPage } from '../sections/CopyMoveDialogPage.ts'
import { FilesListPage } from '../sections/FilesListPage.ts'
import { PublicSharePage } from '../sections/PublicSharePage.ts'
import { test as randomUserTest } from './random-user.ts'

type PublicShareFixtures = {
	/**
	 * A request context authenticated as the share `owner` via basic auth. Seed
	 * the shared content and the share itself with this — the browser page stays
	 * a guest, so it must never carry the owner's session.
	 */
	ownerRequest: APIRequestContext
	/** The public share page (header actions, guest identification, file drop). */
	publicShare: PublicSharePage
	/** The files list as rendered on the public share. */
	filesListPage: FilesListPage
	/** The file picker of the list's "Move or copy" action. */
	copyMoveDialog: CopyMoveDialogPage
}

/**
 * Fixtures for public (link) shares. Unlike the other files fixtures the `page`
 * here is a plain, **not logged in** browser context — that is what a guest
 * visiting a share link is. The share owner exists as `user` and is only acted
 * on through {@link PublicShareFixtures.ownerRequest}.
 */
export const test = randomUserTest.extend<PublicShareFixtures>({
	ownerRequest: async ({ playwright, user, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			// send: 'always' — the OCS API doesn't issue a Basic auth challenge, so
			// credentials must be sent preemptively (DAV would challenge, OCS won't)
			httpCredentials: { username: user.userId, password: user.password, send: 'always' },
		})
		await use(context)
		await context.dispose()
	},

	publicShare: async ({ page }, use) => {
		await use(new PublicSharePage(page))
	},

	filesListPage: async ({ page }, use) => {
		await use(new FilesListPage(page))
	},

	copyMoveDialog: async ({ page }, use) => {
		await use(new CopyMoveDialogPage(page))
	},
})

export { expect } from '../matchers.ts'
