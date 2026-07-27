/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server'
import { login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { createShare, waitForShare } from '../../support/utils/sharing.ts'

/**
 * With `shareapi_only_share_with_group_members` an existing share stays visible
 * only while the two accounts still have a group in common. These tests share
 * both ways, then remove the recipient from the shared groups one by one.
 *
 * The Cypress original logged in as the same user in both assertions of every
 * step, so it never actually checked the second direction; both directions are
 * verified here.
 */
test.describe('files_sharing: Sharing limited to members of the same group', () => {
	const groups = [`group-a-${crypto.randomUUID()}`, `group-b-${crypto.randomUUID()}`]

	test.beforeAll(async () => {
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_only_share_with_group_members'])
	})

	test.afterAll(async () => {
		await runOcc(['config:app:set', '--value', 'no', 'core', 'shareapi_only_share_with_group_members'])
		for (const group of groups) {
			await runOcc(['group:delete', group], { failOnError: false })
		}
	})

	/**
	 * Put both accounts in two shared groups, then share a file each way.
	 *
	 * @returns the file names, `fromUser` shared by `user`, `fromRecipient` by `recipient`
	 */
	async function seedMutualShares(
		user: User,
		recipient: User,
		userRequest: Parameters<typeof createShare>[0],
		recipientRequest: Parameters<typeof createShare>[0],
	): Promise<{ fromUser: string, fromRecipient: string }> {
		for (const group of groups) {
			await runOcc(['group:add', group], { failOnError: false })
			await runOcc(['group:adduser', group, user.userId])
			await runOcc(['group:adduser', group, recipient.userId])
		}

		const fromUser = 'shared-by-user.txt'
		const fromRecipient = 'shared-by-recipient.txt'
		await uploadContent(userRequest, user, 'share to recipient', 'text/plain', `/${fromUser}`)
		await uploadContent(recipientRequest, recipient, 'share to user', 'text/plain', `/${fromRecipient}`)
		await createShare(userRequest, `/${fromUser}`, recipient.userId)
		await createShare(recipientRequest, `/${fromRecipient}`, user.userId)

		await waitForShare(recipientRequest, recipient, '', fromUser)
		await waitForShare(userRequest, user, '', fromRecipient)

		return { fromUser, fromRecipient }
	}

	test('keeps the shares while one common group is left', async ({ page, user, recipient, recipientRequest, filesListPage }) => {
		const { fromUser, fromRecipient } = await seedMutualShares(user, recipient, page.request, recipientRequest)

		// Leaving one of the two groups is not enough to lose the shares
		await runOcc(['group:removeuser', groups[0]!, recipient.userId])

		await filesListPage.open()
		await expect(filesListPage.getRowForFile(fromRecipient)).toBeVisible()

		await login(page.request, recipient)
		await filesListPage.open()
		await expect(filesListPage.getRowForFile(fromUser)).toBeVisible()
	})

	test('hides the shares once no common group is left', async ({ page, user, recipient, recipientRequest, filesListPage }) => {
		const { fromUser, fromRecipient } = await seedMutualShares(user, recipient, page.request, recipientRequest)

		for (const group of groups) {
			await runOcc(['group:removeuser', group, recipient.userId])
		}

		await filesListPage.open()
		await expect(filesListPage.getRowForFile(fromRecipient)).toHaveCount(0)

		await login(page.request, recipient)
		await filesListPage.open()
		await expect(filesListPage.getRowForFile(fromUser)).toHaveCount(0)
	})
})
