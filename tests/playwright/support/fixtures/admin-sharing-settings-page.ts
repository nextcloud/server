/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AdminSharingSettingsPage } from '../sections/AdminSharingSettingsPage.ts'
import { test as adminSessionTest } from './admin-session.ts'

/**
 * Admin session plus the {@link AdminSharingSettingsPage} page object.
 */
export const test = adminSessionTest.extend<{ sharingSettings: AdminSharingSettingsPage }>({
	sharingSettings: async ({ page }, use) => {
		await use(new AdminSharingSettingsPage(page))
	},
})

export { expect } from '@playwright/test'
