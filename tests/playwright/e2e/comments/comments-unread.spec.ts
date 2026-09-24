/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect, test as filesTest } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { createShare } from '../../support/utils/sharing.ts'

const test = filesTest.extend<{ commenter: User, commenterRequest: APIRequestContext }>({
	commenter: async ({}, use) => {
		const commenter = await createRandomUser()
		await use(commenter)
		await runOcc(['user:delete', commenter.userId], { failOnError: false })
	},

	commenterRequest: async ({ playwright, commenter, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			httpCredentials: { username: commenter.userId, password: commenter.password, send: 'always' },
		})
		await use(context)
		await context.dispose()
	},
})

const MARK_READ = /\/remote\.php\/dav\/comments\/files\//

test.describe('Comments: unread badge in the files list', () => {
	test.beforeEach(async ({ page, user, commenter, commenterRequest, filesListPage }) => {
		const fileId = await uploadContent(page.request, user, 'hello', 'text/plain', '/commented-file.txt')
		// Comments can only be added by someone with access to the file
		await createShare(page.request, '/commented-file.txt', commenter.userId)

		const response = await commenterRequest.post(`/remote.php/dav/comments/files/${fileId}`, {
			headers: { 'Content-Type': 'application/json' },
			data: { actorType: 'users', verb: 'comment', message: 'Hey, take a look at this!' },
		})
		expect(response.ok()).toBe(true)

		await filesListPage.open()
	})

	test('shows the unread comments badge when there are unread comments', async ({ filesListPage }) => {
		const badge = filesListPage.getInlineActionEntryForFile('commented-file.txt', 'comments-unread')
		await expect(badge).toBeVisible()
		await expect(badge.getByRole('button')).toHaveAccessibleName(/new comment/)
	})

	test('removes the unread badge after opening the sidebar comments tab', async ({ page, filesListPage, filesSidebar }) => {
		await expect(filesListPage.getInlineActionEntryForFile('commented-file.txt', 'comments-unread')).toBeVisible()

		const markedRead = page.waitForResponse((r) => r.request().method() === 'PROPPATCH' && MARK_READ.test(r.url()))
		await filesListPage.triggerInlineActionForFile('commented-file.txt', 'comments-unread')
		await expect(filesSidebar.sidebar()).toBeVisible()
		await markedRead

		// Badge must be gone without a page reload
		await expect(filesListPage.getInlineActionEntryForFile('commented-file.txt', 'comments-unread')).toHaveCount(0)
	})

	test('badge stays absent after closing and re-opening the sidebar', async ({ page, filesListPage, filesSidebar }) => {
		const markedRead = page.waitForResponse((r) => r.request().method() === 'PROPPATCH' && MARK_READ.test(r.url()))
		await filesListPage.triggerInlineActionForFile('commented-file.txt', 'comments-unread')
		await expect(filesSidebar.sidebar()).toBeVisible()
		await markedRead

		await filesSidebar.close()
		await expect(filesSidebar.sidebar()).toBeHidden()
		await expect(filesListPage.getInlineActionEntryForFile('commented-file.txt', 'comments-unread')).toHaveCount(0)

		await filesListPage.triggerActionForFile('commented-file.txt', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()
		await expect(filesListPage.getInlineActionEntryForFile('commented-file.txt', 'comments-unread')).toHaveCount(0)
	})
})
