/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { DevicesSessionsSettingsPage } from '../sections/DevicesSessionsSettingsPage.ts'
import { LanguageLocaleSettingsPage } from '../sections/LanguageLocaleSettingsPage.ts'
import { PasswordSettingsPage } from '../sections/PasswordSettingsPage.ts'
import { ProfileContactSettingsPage } from '../sections/ProfileContactSettingsPage.ts'
import { test as userSessionTest } from './random-user-session.ts'

/**
 * Extends the random user session with the personal settings page objects.
 * The user language and locale are pinned to English so that assertions on
 * rendered strings are stable.
 */
export const test = userSessionTest.extend<{
	profileContactPage: ProfileContactSettingsPage
	languageLocalePage: LanguageLocaleSettingsPage
	devicesSessionsPage: DevicesSessionsSettingsPage
	passwordPage: PasswordSettingsPage
}>({
	user: async ({ user: baseUser }, use) => {
		await runOcc(['user:setting', baseUser.userId, 'core', 'lang', 'en'])
		await runOcc(['user:setting', baseUser.userId, 'core', 'locale', 'en_US'])
		await use(baseUser)
	},

	profileContactPage: async ({ page, user }, use) => {
		await use(new ProfileContactSettingsPage(page, user))
	},

	languageLocalePage: async ({ page, user }, use) => {
		await use(new LanguageLocaleSettingsPage(page, user))
	},

	devicesSessionsPage: async ({ page, user }, use) => {
		await use(new DevicesSessionsSettingsPage(page, user))
	},

	passwordPage: async ({ page, user }, use) => {
		await use(new PasswordSettingsPage(page, user))
	},
})
