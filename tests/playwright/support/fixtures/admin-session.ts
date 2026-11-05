/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest } from '@playwright/test'
import { installPasswordConfirmationHandler } from '../utils/password-confirmation.ts'
import { retryWithBackoff } from '../utils/retry.ts'

const admin = new User('admin', 'admin')

export const test = baseTest.extend({
	page: async ({ page, context }, use) => {
		await retryWithBackoff('Authenticating as admin', () => login(context.request, admin))
		await installPasswordConfirmationHandler(page, admin.password)
		await use(page)
	},
})
