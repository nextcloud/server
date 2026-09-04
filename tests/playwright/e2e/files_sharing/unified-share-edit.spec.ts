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
	PERMISSION_UPDATE,
	PRESET_EDIT,
	PRESET_VIEW,
	RECIPIENT_TYPE_TOKEN,
	RECIPIENT_TYPE_USER,
	setRecipientPermission,
} from '../../support/utils/unifiedSharing.ts'

/** A token recipient value has to be at least 32 characters. */
const linkToken = () => crypto.randomUUID().replaceAll('-', '')

test.describe('files_sharing: editing a share with the unified dialog', () => {
	let fileId: string

	test.beforeEach(async ({ page, user }) => {
		await expectUnifiedSharingRegistered(page.request)
		fileId = await mkdir(page.request, user, '/shared')
	})

	test('hides the share type of a share that has already been sent', async ({ page, recipient, filesListPage, unifiedShareList, sharingDialog }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Edit share')

		await expect(sharingDialog.panel).toBeVisible()
		await expect(sharingDialog.tabBar).toHaveCount(0)
	})

	test('opens an existing link share on the link view and keeps its link', async ({ page, filesListPage, unifiedShareList, sharingDialog }) => {
		const token = linkToken()
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_TOKEN, token]] })
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.rows.first().getByRole('button', { name: 'Actions' }).click()
		await page.getByRole('menu').getByRole('menuitem', { name: 'Edit share' }).click()

		await expect(sharingDialog.copyLinkButton).toBeVisible()
		await sharingDialog.close()

		// Opening the editor must not strip the token, which would destroy the link.
		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients.map((r) => r.value)).toContain(token)
	})

	test('adds a recipient to an existing share and shows it in the sidebar', async ({ page, recipient, secondRecipient, filesListPage, unifiedShareList, sharingDialog }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Edit share')
		await sharingDialog.addRecipient(secondRecipient.userId)
		await sharingDialog.close()

		// The single row became a group of two once the dialog closed.
		await expect(unifiedShareList.groups.first()).toBeVisible()
		await expect(unifiedShareList.row(secondRecipient.userId)).toBeVisible()
		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients).toHaveLength(2)
	})

	test('deletes the share from the settings view', async ({ page, recipient, filesListPage, unifiedShareList, sharingDialog }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Edit share')
		await sharingDialog.settingsButton.click()
		await expect(sharingDialog.deleteButton).toBeVisible()

		await sharingDialog.deleteButton.click()
		await sharingDialog.answerConfirmation('Delete share', 'Delete share')
		await sharingDialog.dialog.waitFor({ state: 'detached' })

		await expect(unifiedShareList.rows).toHaveCount(0)
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(0)
	})

	test('changes the permission of one recipient only', async ({ page, recipient, secondRecipient, filesListPage, unifiedShareList, sharingDialog }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId], [RECIPIENT_TYPE_USER, secondRecipient.userId]],
			preset: PRESET_EDIT,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction('2 people', 'Edit share')
		// Both start on the share default, which the row marks as such.
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText('Can edit (default)')
		await sharingDialog.setRecipientPreset(recipient.userId, /^Can view/)

		// Only the edited recipient moves off the default.
		await expect(sharingDialog.recipientPermission(recipient.userId)).toHaveText('Can view')
		await expect(sharingDialog.recipientPermission(secondRecipient.userId)).toHaveText('Can edit (default)')

		await sharingDialog.close()
		await expect(unifiedShareList.subtitle(recipient.userId)).toHaveText('Can view')
		await expect(unifiedShareList.subtitle(secondRecipient.userId)).toHaveText('Can edit')
	})

	test('marks the share default in the per-recipient menu', async ({ page, recipient, filesListPage, unifiedShareList, sharingDialog }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_VIEW,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Edit share')
		const menu = await sharingDialog.openRecipientMenu(recipient.userId)

		await expect(menu.getByRole('menuitem', { name: 'Can view (default)' })).toBeVisible()
		await expect(menu.getByRole('menuitem', { name: 'Custom permissions' })).toBeVisible()
		await expect(menu.getByRole('menuitem', { name: 'Remove participant' })).toBeVisible()
	})

	test('caps the recipient toggles at the permissions the share grants', async ({ page, recipient, filesListPage, unifiedShareList, sharingDialog }) => {
		// A view-only share cannot grant editing to any of its recipients.
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_VIEW,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Edit share')
		const menu = await sharingDialog.openRecipientMenu(recipient.userId)
		await menu.getByRole('menuitem', { name: 'Custom permissions' }).click()

		const modal = sharingDialog.recipientPermissionsModal
		await expect(modal).toBeVisible()
		// The toggles are revealed straight away, and what the share withholds
		// cannot be granted here.
		await expect(modal.getByRole('switch', { name: 'Edit files' })).toBeDisabled()
		await expect(modal.getByRole('switch', { name: 'View files' })).toBeChecked()
		await expect(modal.getByText(/You can only grant the same or fewer permissions/)).toBeVisible()
	})

	test('shows a recipient permission that was set outside the dialog', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		const share = await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_EDIT,
		})
		// Recipient permissions are overrides on the share's, so revoking one here
		// must show up as a narrower permission on the row.
		await setRecipientPermission(page.request, share.id, [RECIPIENT_TYPE_USER, recipient.userId], PERMISSION_UPDATE, false)

		await unifiedShareList.open(filesListPage, 'shared')
		await expect(unifiedShareList.subtitle(recipient.userId)).not.toHaveText('Can edit')
	})
})
