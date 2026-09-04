/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext, APIResponse } from '@playwright/test'

import { test as randomUserTest } from '../../support/fixtures/random-user.ts'
import { expect } from '../../support/matchers.ts'

export interface ApiFixtures {
	/** Unauthenticated request context. */
	guestRequest: APIRequestContext
	/** Request context authenticated as `user`. */
	userRequest: APIRequestContext
}

/**
 * Build the basic auth header for a user.
 *
 * The header is set explicitly instead of through Playwright's
 * `httpCredentials`, which only attaches credentials after the server answered
 * `401`. Endpoints that are not behind an authentication check never issue that
 * challenge, so the credentials would never be sent and the request would be
 * handled as anonymous.
 *
 * @param user - The user to authenticate as
 */
function basicAuthHeader(user: User): string {
	return 'Basic ' + Buffer.from(`${user.userId}:${user.password}`).toString('base64')
}

/**
 * Request contexts for API tests: one anonymous, one authenticated as the
 * random `user` provided by the base fixture. Neither is tied to a browser
 * session, so they share no cookies and no browser is launched.
 */
export const test = randomUserTest.extend<ApiFixtures>({
	guestRequest: async ({ playwright, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			extraHTTPHeaders: { 'OCS-APIRequest': 'true' },
		})
		await use(context)
		await context.dispose()
	},

	userRequest: async ({ playwright, baseURL, user }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			extraHTTPHeaders: {
				'OCS-APIRequest': 'true',
				Authorization: basicAuthHeader(user),
			},
		})
		await use(context)
		await context.dispose()
	},
})

/**
 * Assert the HTTP status of a response and report the body when it differs,
 * which is usually where the reason for an unexpected status is.
 *
 * @param response - The response to check
 * @param expectedStatus - The expected HTTP status code
 * @param message - Explains why this status is expected
 */
export async function expectStatus(response: APIResponse, expectedStatus: number, message = ''): Promise<void> {
	const status = response.status()
	if (status !== expectedStatus) {
		const body = (await response.text()).trim()
		message = `${message}\nResponse body: ${body === '' ? '<empty>' : body.slice(0, 1000)}`.trim()
	}

	expect(status, message).toBe(expectedStatus)
}

export { expect }
