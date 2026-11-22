/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AdminThemingPage } from '../sections/AdminThemingPage.ts'
import { test as adminSessionTest } from './admin-session.ts'

export const test = adminSessionTest.extend<{ adminThemingPage: AdminThemingPage }>({
	adminThemingPage: async ({ page }, use) => {
		const adminThemingPage = new AdminThemingPage(page)
		await use(adminThemingPage)
	},
})
