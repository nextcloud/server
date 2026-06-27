/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { CopyMoveDialogPage } from '../sections/CopyMoveDialogPage.ts'
import { FilesListPage } from '../sections/FilesListPage.ts'
import { FilesNavigationPage } from '../sections/FilesNavigationPage.ts'
import { FilesSidebarPage } from '../sections/FilesSidebarPage.ts'
import { test as baseTest } from './random-user-session.ts'

type FilesFixtures = {
	filesListPage: FilesListPage
	filesNavigation: FilesNavigationPage
	filesSidebar: FilesSidebarPage
	copyMoveDialog: CopyMoveDialogPage
}

export const test = baseTest.extend<FilesFixtures>({
	filesListPage: async ({ page }, use) => {
		await use(new FilesListPage(page))
	},

	filesNavigation: async ({ page }, use) => {
		await use(new FilesNavigationPage(page))
	},

	filesSidebar: async ({ page }, use) => {
		await use(new FilesSidebarPage(page))
	},

	copyMoveDialog: async ({ page }, use) => {
		await use(new CopyMoveDialogPage(page))
	},
})

export { expect } from '../matchers.ts'
