/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { SystemTagsFilesListPage } from '../sections/SystemTagsFilesListPage.ts'
import { test as filesTest } from './files-page.ts'

type SystemTagsFixtures = {
	filesListPage: SystemTagsFilesListPage
}

/**
 * Extends the base files-page fixture by replacing `filesListPage` with a
 * {@link SystemTagsFilesListPage}, which adds SystemTagPicker actions and
 * inline-tags assertion helpers on top of the standard file list interactions.
 */
export const test = filesTest.extend<SystemTagsFixtures>({
	filesListPage: async ({ page }, use) => {
		await use(new SystemTagsFilesListPage(page))
	},
})

export { expect } from '../matchers.ts'
