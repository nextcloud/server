/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest } from '@playwright/test'

export const test = baseTest.extend({
	page: async ({ page, context }, use) => {
		const user = await createRandomUser()
		await login(context.request, user)

		await use(page)

		await runOcc(['user:delete', user.userId])
	},
})
