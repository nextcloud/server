/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest } from '@playwright/test'

/**
 * Extends the base test with a freshly-created random `user`.
 * The user is deleted in teardown regardless of test outcome.
 */
export const test = baseTest.extend<{ user: User }>({
	user: async ({}, use) => {
		let user: User
		try {
			user = await createRandomUser()
		} catch {
			// Retry once on transient failure
			await new Promise((resolve) => setTimeout(resolve, 800))
			user = await createRandomUser()
		}
		await use(user)
		await runOcc(['user:delete', user.userId], { failOnError: false })
	},
})
