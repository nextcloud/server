/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { VersionsTab } from '../sections/VersionsTab.ts'
import { test as filesTest } from './files-page.ts'

type VersionsFixtures = {
	versionsTab: VersionsTab
}

/** Files fixtures plus the `versionsTab` page object, for single-user version tests. */
export const test = filesTest.extend<VersionsFixtures>({
	versionsTab: async ({ page }, use) => {
		await use(new VersionsTab(page))
	},
})

export { expect } from '../matchers.ts'
