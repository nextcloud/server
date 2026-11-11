/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { installPasswordConfirmationHandler } from '../utils/password-confirmation.ts'
import { retryWithBackoff } from '../utils/retry.ts'
import { test as randomUserTest } from './random-user.ts'

/**
 * Extends the random-user fixture with a `page` logged in as that user.
 * The page runs in an isolated browser context — no admin session leaks in.
 */
export const test = randomUserTest.extend({
	page: async ({ browser, user }, use) => {
		const page = await browser.newPage()
		await retryWithBackoff('Authenticating as random user', () => login(page.request, user))
		await installPasswordConfirmationHandler(page, user.password)
		await use(page)
	},
})
