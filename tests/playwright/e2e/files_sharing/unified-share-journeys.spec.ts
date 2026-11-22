/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/unified-sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import {
	createUnifiedShare,
	expectUnifiedSharingRegistered,
	getUnifiedShares,
	PRESET_EDIT,
	PRESET_VIEW,
	RECIPIENT_TYPE_GROUP,
	RECIPIENT_TYPE_TOKEN,
	RECIPIENT_TYPE_USER,
	setSharePreset,
} from '../../support/utils/unifiedSharing.ts'

/**
 * Whole journeys through the sharing UI, as a user would run them, rather than
 * one interaction per test.
 */
test.describe('files_sharing: unified sharing journeys', () => {
	let fileId: string

	test.beforeEach(async ({ page, user }) => {
		await expectUnifiedSharingRegistered(page.request)
		fileId = await mkdir(page.request, user, '/shared')
	})

	test('shares with a group, then grants one person more than the group', async ({ page, recipientGroup, secondRecipient, unifiedShareList, sharingDialog, filesListPage }) => {
		await unifiedShareList.open(filesListPage, 'shared')

		// Share the folder with a group, view only.
		await unifiedShareList.shareButton.click()
		await sharingDialog.addRecipient(recipientGroup)
		await sharingDialog.send()
		await sharingDialog.done()
		await expect(unifiedShareList.subtitle(recipientGroup)).toHaveText('Can view')

		// Someone needs to edit, so raise the share, which is the cap for everyone.
		// Through the API: the share-level select rebuilds its options on every
		// share sync, which makes picking from it unreliable (see the note in the
		// PR), and this journey is about the per-recipient permissions.
		const [draft] = await getUnifiedShares(page.request, fileId)
		await setSharePreset(page.request, draft.id, PRESET_EDIT)

		// Whoever is added now inherits the new default.
		await unifiedShareList.triggerAction(recipientGroup, 'Edit share')
		await sharingDialog.addRecipient(secondRecipient.userId)
		await expect(sharingDialog.recipientPermission(secondRecipient.userId)).toHaveText('Can edit (default)')

		// The group should stay on view only, so put it back.
		await sharingDialog.setRecipientPreset(recipientGroup, /^Can view/)
		await expect(sharingDialog.recipientPermission(recipientGroup)).toHaveText('Can view')
		await sharingDialog.close()

		// One share, two recipients, two different permissions.
		await expect(unifiedShareList.subtitle(recipientGroup)).toHaveText('Can view')
		await expect(unifiedShareList.subtitle(secondRecipient.userId)).toHaveText('Can edit')

		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients).toHaveLength(2)
	})

	test('reaches both a named person and everyone with the link', async ({ page, recipient, unifiedShareList, sharingDialog, filesListPage }) => {
		// An invited share first…
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_EDIT,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		// …then a public link next to it, from the same sidebar.
		await unifiedShareList.shareButton.click()
		await sharingDialog.selectTab('Anyone')
		await sharingDialog.send()
		await expect(sharingDialog.confirmationLink).toHaveValue(/\/s\/\w+/)
		await sharingDialog.done()

		await expect(unifiedShareList.rows).toHaveCount(2)

		// Dropping the link leaves the invited share untouched.
		const shares = await getUnifiedShares(page.request, fileId)
		const linkName = shares
			.flatMap((s) => s.recipients)
			.find((r) => r.class === RECIPIENT_TYPE_TOKEN)!.display_name
		await unifiedShareList.triggerAction(linkName, 'Delete share')
		await unifiedShareList.answerConfirmation(true)

		await expect(unifiedShareList.row(recipient.userId)).toBeVisible()
		// Only read the backend once the deletion has landed in the list.
		await expect(unifiedShareList.rows).toHaveCount(1)
		const remaining = await getUnifiedShares(page.request, fileId)
		expect(remaining).toHaveLength(1)
		expect(remaining[0].recipients.map((r) => r.class)).not.toContain(RECIPIENT_TYPE_TOKEN)
	})

	test('leaves nothing behind when the dialog is abandoned', async ({ page, recipient, unifiedShareList, sharingDialog, filesListPage }) => {
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.shareButton.click()
		await sharingDialog.addRecipient(recipient.userId)
		// Closing without sending leaves the share a draft.
		await sharingDialog.close()

		await expect(unifiedShareList.rows).toHaveCount(0)
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(0)
	})

	test('narrows one recipient by hand and keeps it after reopening', async ({ page, recipient, secondRecipient, unifiedShareList, sharingDialog, filesListPage }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId], [RECIPIENT_TYPE_USER, secondRecipient.userId]],
			preset: PRESET_EDIT,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		// Both start on the share default; take one of them down to view only.
		await unifiedShareList.triggerAction('2 people', 'Edit share')
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText('Can edit (default)')
		await sharingDialog.setRecipientPreset(recipient.userId, /^Can view/)
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText('Can view')
		await sharingDialog.close()

		// It survives closing the dialog, in the list and on reopening.
		await expect(unifiedShareList.subtitle(recipient.userId)).toHaveText('Can view')
		await expect(unifiedShareList.subtitle(secondRecipient.userId)).toHaveText('Can edit')

		await unifiedShareList.triggerAction('2 people', 'Edit share')
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText('Can view')
		await expect(sharingDialog.recipientPermission(secondRecipient.userId)).toHaveText('Can edit (default)')

		// The narrowing is an override on the recipient, the share is untouched.
		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.permission_preset).toBe(PRESET_EDIT)
	})

	test('adds a group to a share that already has a person', async ({ page, recipientGroup, secondRecipient, unifiedShareList, sharingDialog, filesListPage }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, secondRecipient.userId]],
			preset: PRESET_VIEW,
		})
		await unifiedShareList.open(filesListPage, 'shared')
		// A single recipient renders as a plain row.
		await expect(unifiedShareList.groups).toHaveCount(0)

		await unifiedShareList.triggerAction(secondRecipient.userId, 'Edit share')
		await sharingDialog.addRecipient(recipientGroup)
		await sharingDialog.close()

		// It becomes a group of two, and the new group members can see it.
		await expect(unifiedShareList.groups.first()).toBeVisible()
		await expect(unifiedShareList.row(recipientGroup)).toBeVisible()
		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients.map((r) => r.class)).toContain(RECIPIENT_TYPE_GROUP)
	})
})
