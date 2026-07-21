/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ExternalStorageSettingsPage } from '../sections/ExternalStorageSettingsPage.ts'
import { test as adminTest } from './admin-session.ts'

type ExternalStorageFixtures = {
	externalStorageSettings: ExternalStorageSettingsPage
}

/**
 * Admin session plus the {@link ExternalStorageSettingsPage} page object. The
 * browser is logged in as admin (external storage configuration is an admin task).
 */
export const test = adminTest.extend<ExternalStorageFixtures>({
	externalStorageSettings: async ({ page }, use) => {
		await use(new ExternalStorageSettingsPage(page))
	},
})

export { expect } from '../matchers.ts'
