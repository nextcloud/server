/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { SystemTagsFilesListPage } from '../sections/SystemTagsFilesListPage.ts'
import { tagNameFixture } from '../utils/systemtags.ts'
import { test as filesTest } from './files-page.ts'

type SystemTagsFixtures = {
	filesListPage: SystemTagsFilesListPage
	tagName: () => string
}

/**
 * Extends the base files-page fixture by replacing `filesListPage` with a
 * {@link SystemTagsFilesListPage}, which adds SystemTagPicker actions and
 * inline-tags assertion helpers on top of the standard file list interactions,
 * and provides `tagName` to generate tag names that are cleaned up after the test.
 */
export const test = filesTest.extend<SystemTagsFixtures>({
	filesListPage: async ({ page }, use) => {
		await use(new SystemTagsFilesListPage(page))
	},
	tagName: tagNameFixture,
})

export { expect } from '../matchers.ts'
