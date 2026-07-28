/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { addUser, runOcc } from '@nextcloud/e2e-test-server/docker'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, waitForShare } from '../../support/utils/sharing.ts'

test.describe('files_sharing: Sharing status action', () => {
	/**
	 * Regression test of https://github.com/nextcloud/server/issues/45723: a
	 * purely numerical user id used to be mistaken for a share, so an unshared
	 * folder was flagged as shared.
	 */
	test('shows no sharing status for a numerical user id without shares', async ({ page, filesListPage }) => {
		const uid = crypto.getRandomValues(new Uint32Array(1))[0].toString()
		const numericalUser = new User(uid, uid, 'en')
		await addUser(numericalUser)

		try {
			await login(page.request, numericalUser)
			await mkdir(page.request, numericalUser, '/folder')
			await filesListPage.open()

			await expect(filesListPage.getRowForFile('folder')).toBeVisible()
			await expect(filesListPage.getRowForFile('folder').getByRole('button', { name: 'Shared' })).toHaveCount(0)
		} finally {
			await runOcc(['user:delete', uid], { failOnError: false })
		}
	})

	test('offers a quick sharing action that opens the sharing tab', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/folder')
		await filesListPage.open()

		await filesListPage.getRowForFile('folder').hover()
		await filesListPage.getRowForFile('folder')
			.getByRole('button', { name: /Sharing options/ })
			.click({ force: true })

		// The sidebar opens straight on the Sharing tab
		await expect(page.getByRole('tab', { name: 'Sharing', selected: true })).toBeVisible()
	})

	test.describe('for a shared folder', () => {
		test.beforeEach(async ({ page, user, recipient, recipientRequest }) => {
			await mkdir(page.request, user, '/folder')
			await createShare(page.request, '/folder', recipient.userId)
			await waitForShare(recipientRequest, recipient, '', 'folder')
		})

		test('names the recipient for the sharer', async ({ filesListPage }) => {
			await filesListPage.open()

			const status = filesListPage.getInlineActionEntryForFile('folder', 'sharing-status')
			await expect(status).toBeVisible()
			await expect(status).toHaveAttribute('aria-label', /^Shared with /)
			await expect(status).toHaveAttribute('title', /^Shared with /)
		})

		test('names the recipient for the sharer in grid view', async ({ filesListPage }) => {
			await filesListPage.open()
			await filesListPage.enableGridView()

			const menu = await filesListPage.openActionsMenuForFile('folder')
			await expect(menu.getByRole('menuitem', { name: /shared with/i })).toBeVisible()
		})

		test('names the owner for the recipient', async ({ page, user, recipient, filesListPage }) => {
			await login(page.request, recipient)
			await filesListPage.open()

			const status = filesListPage.getInlineActionEntryForFile('folder', 'sharing-status')
			await expect(status).toBeVisible()
			await expect(status).toHaveAttribute('aria-label', `Shared by ${user.userId}`)
		})

		test('names the owner for the recipient in grid view', async ({ page, user, recipient, filesListPage }) => {
			await login(page.request, recipient)
			await filesListPage.open()
			await filesListPage.enableGridView()

			const menu = await filesListPage.openActionsMenuForFile('folder')
			await expect(menu.getByRole('menuitem', { name: `Shared by ${user.userId}` })).toBeVisible()
		})
	})
})
