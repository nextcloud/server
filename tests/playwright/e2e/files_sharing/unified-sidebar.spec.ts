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
	RECIPIENT_TYPE_USER,
} from '../../support/utils/unifiedSharing.ts'

test.describe('files_sharing: unified share list in the sidebar', () => {
	let fileId: string

	test.beforeEach(async ({ page, user }) => {
		await expectUnifiedSharingRegistered(page.request)
		fileId = await mkdir(page.request, user, '/shared')
	})

	test('lists nothing but the share button for an unshared folder', async ({ filesListPage, unifiedShareList }) => {
		await unifiedShareList.open(filesListPage, 'shared')

		await expect(unifiedShareList.shareButton).toBeVisible()
		await expect(unifiedShareList.rows).toHaveCount(0)
		// The legacy internal/external sections are replaced, not merely hidden.
		await expect(unifiedShareList.root.getByText('Internal shares')).toHaveCount(0)
		await expect(unifiedShareList.root.getByText('External shares')).toHaveCount(0)
	})

	test('shows a placeholder while loading, then the share', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })

		// Hold the list request so the placeholder is observable.
		let release: () => void = () => {}
		const held = new Promise<void>((resolve) => {
			release = resolve
		})
		await page.route(/\/apps\/sharing\/api\/v1\/shares/, async (route) => {
			await held
			await route.continue()
		})

		await unifiedShareList.open(filesListPage, 'shared')
		await expect(unifiedShareList.skeleton).toBeVisible()
		release()
		await expect(unifiedShareList.skeleton).toBeHidden()
		await expect(unifiedShareList.row(recipient.userId)).toBeVisible()
	})

	test('renders a single-recipient share as one row with its permission', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_VIEW,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		await expect(unifiedShareList.rows).toHaveCount(1)
		await expect(unifiedShareList.row(recipient.userId)).toBeVisible()
		await expect(unifiedShareList.subtitle(recipient.userId)).toHaveText('Can view')
		// A single recipient is a plain row, not a group.
		await expect(unifiedShareList.groups).toHaveCount(0)
	})

	test('renders a multi-recipient share as a group, expanded by default', async ({ page, recipient, recipientGroup, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId], [RECIPIENT_TYPE_GROUP, recipientGroup]],
			preset: PRESET_VIEW,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		const summary = unifiedShareList.groups.first()
		await expect(summary).toBeVisible()
		await expect(summary.locator('.sharing-entry__title')).toHaveText('1 person, 1 group')
		// Expanded on open: both recipients are listed, each with its permission.
		await expect(unifiedShareList.recipientRows).toHaveCount(2)
		await expect(unifiedShareList.subtitle(recipient.userId)).toHaveText('Can view')
		// The stack only stands in for the recipients while they are hidden.
		await expect(unifiedShareList.avatarStack).toHaveCount(0)
	})

	test('collapses and expands a share group', async ({ page, recipient, recipientGroup, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId], [RECIPIENT_TYPE_GROUP, recipientGroup]],
		})
		await unifiedShareList.open(filesListPage, 'shared')

		const title = '1 person, 1 group'
		expect(await unifiedShareList.isExpanded(title)).toBe(true)

		await unifiedShareList.toggleRecipients(title)
		expect(await unifiedShareList.isExpanded(title)).toBe(false)
		await expect(unifiedShareList.recipientRows.first()).toBeHidden()
		await expect(unifiedShareList.avatarStack).toBeVisible()

		await unifiedShareList.toggleRecipients(title)
		expect(await unifiedShareList.isExpanded(title)).toBe(true)
		await expect(unifiedShareList.recipientRows.first()).toBeVisible()
	})

	test('orders the shares by their permissions, most permissive first', async ({ page, recipient, secondRecipient, filesListPage, unifiedShareList }) => {
		const viewer = secondRecipient.userId
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, viewer]],
			preset: PRESET_VIEW,
		})
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId]],
			preset: PRESET_EDIT,
		})
		await unifiedShareList.open(filesListPage, 'shared')

		await expect(unifiedShareList.rows).toHaveCount(2)
		await expect(unifiedShareList.rows.first().locator('.sharing-entry__title')).toHaveText(recipient.userId)
		await expect(unifiedShareList.subtitle(recipient.userId)).toHaveText('Can edit')
		await expect(unifiedShareList.subtitle(viewer)).toHaveText('Can view')
	})

	test('deletes a share once the confirmation is accepted', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		const deleted = page.waitForResponse((response) => response.request().method() === 'DELETE'
			&& response.url().includes('/apps/sharing/api/v1/share/'))
		await unifiedShareList.triggerAction(recipient.userId, 'Delete share')
		await unifiedShareList.answerConfirmation(true)
		await deleted

		await expect(unifiedShareList.rows).toHaveCount(0)
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(0)
	})

	test('keeps the share when the delete confirmation is declined', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		await unifiedShareList.triggerAction(recipient.userId, 'Delete share')
		await unifiedShareList.answerConfirmation(false)

		await expect(unifiedShareList.row(recipient.userId)).toBeVisible()
		expect(await getUnifiedShares(page.request, fileId)).toHaveLength(1)
	})

	test('removes a single participant from a share group', async ({ page, recipient, recipientGroup, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, {
			fileId,
			recipients: [[RECIPIENT_TYPE_USER, recipient.userId], [RECIPIENT_TYPE_GROUP, recipientGroup]],
		})
		await unifiedShareList.open(filesListPage, 'shared')

		const removed = page.waitForResponse((response) => response.request().method() === 'DELETE'
			&& response.url().includes('/recipient'))
		await unifiedShareList.triggerAction(recipient.userId, 'Remove participant')
		await removed

		// One recipient left, so the group collapses into a plain row.
		await expect(unifiedShareList.rows).toHaveCount(1)
		await expect(unifiedShareList.row(recipient.userId)).toHaveCount(0)
		const [share] = await getUnifiedShares(page.request, fileId)
		expect(share.recipients).toHaveLength(1)
	})

	test('offers destructive actions only inside the action menu', async ({ page, recipient, filesListPage, unifiedShareList }) => {
		await createUnifiedShare(page.request, { fileId, recipients: [[RECIPIENT_TYPE_USER, recipient.userId]] })
		await unifiedShareList.open(filesListPage, 'shared')

		// No bare delete control on the row itself.
		await expect(unifiedShareList.row(recipient.userId).getByRole('button', { name: /Delete|Remove|Unshare/ })).toHaveCount(0)
		const menu = await unifiedShareList.openMenu(recipient.userId)
		await expect(menu.getByRole('menuitem', { name: 'Delete share' })).toBeVisible()
	})

	test('surfaces an error when the list cannot be loaded', async ({ page, filesListPage, unifiedShareList }) => {
		await page.route(/\/apps\/sharing\/api\/v1\/shares/, (route) => route.fulfill({ status: 500 }))
		await unifiedShareList.open(filesListPage, 'shared')

		await expect(unifiedShareList.error).toBeVisible()
	})
})
