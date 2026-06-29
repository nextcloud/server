/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest } from '@playwright/test'
import { CopyMoveDialogPage } from '../sections/CopyMoveDialogPage.ts'
import { FilesListPage } from '../sections/FilesListPage.ts'
import { FilesNavigationPage } from '../sections/FilesNavigationPage.ts'
import { FilesSidebarPage } from '../sections/FilesSidebarPage.ts'

type FilesFixtures = {
	user: User
	filesListPage: FilesListPage
	filesNavigation: FilesNavigationPage
	filesSidebar: FilesSidebarPage
	copyMoveDialog: CopyMoveDialogPage
}

export const test = baseTest.extend<FilesFixtures>({
	user: async ({ context }, use) => {
		const user = await createRandomUser()
		try {
			await login(context.request, user)
		} catch {
			// Retry once on transient auth failure
			await new Promise((resolve) => setTimeout(resolve, 800))
			await login(context.request, user)
		}
		await use(user)
		await runOcc(['user:delete', user.userId])
	},

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
