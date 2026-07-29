/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mergeTests } from '@playwright/test'
import { EncryptionPersonalSettingsPage } from '../sections/EncryptionPersonalSettingsPage.ts'
import { test as adminRequestTest } from './admin-request.ts'
import { test as randomUserTest } from './random-user.ts'

type EncryptionFixtures = {
	encryptionSettings: EncryptionPersonalSettingsPage
}

/**
 * A random `user`, an `adminRequest` context and the personal encryption settings
 * page object.
 *
 * The page is deliberately *not* logged in: the encryption app sets up and unlocks
 * the account keys during log-in, so every test has to control when the session is
 * created. Use `loginAs()` from `utils/users.ts` once the instance is prepared.
 */
export const test = mergeTests(randomUserTest, adminRequestTest).extend<EncryptionFixtures>({
	encryptionSettings: async ({ page }, use) => {
		await use(new EncryptionPersonalSettingsPage(page))
	},
})

export { expect } from '../matchers.ts'
