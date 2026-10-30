/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext, Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { FilesListPage } from '../sections/FilesListPage.ts'
import { TransferOwnershipPage } from '../sections/TransferOwnershipPage.ts'
import { test as filesTest } from './files-page.ts'

type TransferOwnershipFixtures = {
	/** A second account, receiving the ownership of the files of `user`. */
	recipient: User
	/**
	 * A request context authenticated as `recipient` via basic auth, with no
	 * browser session cookies — cookies would otherwise win over basic auth and
	 * the request would run as the user logged into `page` instead.
	 */
	recipientRequest: APIRequestContext
	/** A second browser session, logged in as `recipient`. */
	recipientPage: Page
	/** The files list as seen by `recipient`. */
	recipientFilesList: FilesListPage
	/** The ownership transfer form in the personal settings of `user`. */
	transferOwnershipPage: TransferOwnershipPage
}

/**
 * Files fixtures for the ownership transfer: the browser is logged in as `user`,
 * who owns the files and requests the transfer, and `recipient` is the account
 * receiving them.
 */
export const test = filesTest.extend<TransferOwnershipFixtures>({
	recipient: async ({}, use) => {
		let recipient: User
		try {
			recipient = await createRandomUser()
		} catch {
			// Retry once on transient failure, as the `user` fixture does
			await new Promise((resolve) => setTimeout(resolve, 800))
			recipient = await createRandomUser()
		}
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

	recipientPage: async ({ browser, recipient }, use) => {
		const context = await browser.newContext()
		const recipientPage = await context.newPage()
		try {
			await login(recipientPage.request, recipient)
		} catch (error) {
			// Same transient failure the session of `user` is retried for
			console.info('Failed to authenticate as recipient, retrying', error)
			await new Promise((resolve) => setTimeout(resolve, 800))
			await login(recipientPage.request, recipient)
		}
		await use(recipientPage)
		await context.close()
	},

	recipientFilesList: async ({ recipientPage }, use) => {
		await use(new FilesListPage(recipientPage))
	},

	transferOwnershipPage: async ({ page }, use) => {
		await use(new TransferOwnershipPage(page))
	},
})

export { expect } from '../matchers.ts'
