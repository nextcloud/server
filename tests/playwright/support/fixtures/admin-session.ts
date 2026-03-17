/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest } from '@playwright/test'

const admin = new User('admin', 'admin')

export const test = baseTest.extend({
	page: async ({ page, context }, use) => {
		await login(context.request, admin)
		await use(page)
	},
})
