/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/unified-sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import {
	expectUnifiedSharingRegistered,
	getUnifiedShares,
	RECIPIENT_TYPE_TOKEN,
} from '../../support/utils/unifiedSharing.ts'

test.describe('files_sharing: creating a share with the unified dialog', () => {
	let fileId: string

	test.beforeEach(async ({ page, user, filesListPage, unifiedShareList }) => {
		await expectUnifiedSharingRegistered(page.request)
		fileId = await mkdir(page.request, user, '/shared')
		await unifiedShareList.open(filesListPage, 'shared')
		await unifiedShareList.shareButton.click()
	})

	test('opens a draft for the current folder with both share types offered', async ({ sharingDialog }) => {
		await expect(sharingDialog.title).toHaveText('Share "shared"')
		await expect(sharingDialog.panel).toBeVisible()
		// The share type is still open to change while the share is a draft.
		await expect(sharingDialog.tabBar).toBeVisible()
		await expect(sharingDialog.tab('Invited people')).toBeChecked()
	})

	test('adds a recipient carrying the share default permission', async ({ recipient, sharingDialog }) => {
		await sharingDialog.addRecipient(recipient.userId)

		await expect(sharingDialog.recipientRow(recipient.userId)).toBeVisible()
		// A new share defaults to view-only, and the row says so.
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText(/^Can view/)
	})

	test('adds several recipients to one share', async ({ recipient, secondRecipient, sharingDialog }) => {
		await sharingDialog.addRecipient(recipient.userId)
		await sharingDialog.addRecipient(secondRecipient.userId)

		await expect(sharingDialog.recipientRows).toHaveCount(2)
	})

	test('sends the share and shows it in the sidebar', async ({ page, recipient, sharingDialog, unifiedShareList }) => {
		await sharingDialog.addRecipient(recipient.userId)
		await sharingDialog.send()
		await sharingDialog.done()

		// The sidebar picks up whatever the dialog created once it closes.
		await expect(unifiedShareList.row(recipient.userId)).toBeVisible()
		const shares = await getUnifiedShares(page.request, fileId)
		expect(shares).toHaveLength(1)
		expect(shares[0].state).toBe('active')
	})

	test('cannot be sent before it has a recipient', async ({ page, recipient, sharingDialog }) => {
		await expect(sharingDialog.sendButton).toBeDisabled()
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(0)

		await sharingDialog.addRecipient(recipient.userId)
		await expect(sharingDialog.sendButton).toBeEnabled()
	})

	test('keeps the invited people when the switch to a public link is declined', async ({ recipient, sharingDialog }) => {
		await sharingDialog.addRecipient(recipient.userId)

		await sharingDialog.selectTab('Anyone')
		await sharingDialog.answerConfirmation('Share with anyone', 'Cancel')

		// Neither the recipient nor the active tab may change.
		await expect(sharingDialog.recipientRow(recipient.userId)).toBeVisible()
		await expect(sharingDialog.tab('Invited people')).toBeChecked()
	})

	test('drops the invited people when the switch to a public link is confirmed', async ({ recipient, sharingDialog }) => {
		await sharingDialog.addRecipient(recipient.userId)

		await sharingDialog.selectTab('Anyone')
		await sharingDialog.answerConfirmation('Share with anyone', 'Continue')

		await expect(sharingDialog.tab('Anyone')).toBeChecked()
		await expect(sharingDialog.recipientRows).toHaveCount(0)
		await expect(sharingDialog.copyLinkButton).toBeVisible()
	})

	test('creates a public link share and hands out its link', async ({ page, sharingDialog }) => {
		await sharingDialog.selectTab('Anyone')
		// Switching to a link adds the token recipient that makes it public.
		await expect(sharingDialog.copyLinkButton).toBeEnabled()

		await sharingDialog.send()
		await expect(sharingDialog.confirmationLink).toHaveValue(/\/s\/\w+/)
		await sharingDialog.done()

		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients.map((r) => r.class)).toContain(RECIPIENT_TYPE_TOKEN)
	})

	test('lists a newly shared recipient without collapsing the existing rows', async ({ page, recipient, secondRecipient, sharingDialog, unifiedShareList }) => {
		// Two recipients so the sidebar renders an expandable group.
		await sharingDialog.addRecipient(recipient.userId)
		await sharingDialog.addRecipient(secondRecipient.userId)
		await sharingDialog.send()
		await sharingDialog.done()

		const group = unifiedShareList.groups.first()
		await expect(group).toBeVisible()
		expect(await unifiedShareList.isExpanded('2 people')).toBe(true)

		// Editing the same share again must refresh in place, not reset the rows.
		await unifiedShareList.triggerAction('2 people', 'Edit share')
		await expect(sharingDialog.recipientRows).toHaveCount(2)
		await sharingDialog.close()
		expect(await unifiedShareList.isExpanded('2 people')).toBe(true)
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(1)
	})
})
