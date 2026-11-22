/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AppstorePage } from '../sections/AppstorePage.ts'
import { test as adminSessionTest } from './admin-session.ts'

export const test = adminSessionTest.extend<{ appstorePage: AppstorePage }>({
	appstorePage: async ({ page }, use) => {
		const appstorePage = new AppstorePage(page)
		await use(appstorePage)
	},
})
