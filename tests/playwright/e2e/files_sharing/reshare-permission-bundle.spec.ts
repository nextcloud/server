/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { ALL_PERMISSIONS, createShare, openSharingPanel, SharePermission } from '../../support/utils/sharing.ts'

const { READ, UPDATE, CREATE, SHARE } = SharePermission

/** What the owner hands the resharer: everything but DELETE. */
const WITHOUT_DELETE = READ | UPDATE | CREATE | SHARE

/**
 * The editor's "Allow upload and editing" bundle asks for the full permission
 * set, DELETE included. A resharer who never received DELETE cannot pass it on,
 * so the backend rejected the entire share ("Cannot increase permissions of %s")
 * and the bundle was unusable on a reshare. It is now capped to what the
 * resharer actually holds.
 */
test.describe('files_sharing: the permissions offered on a reshare', () => {
	const RESHARED = 'reshared-folder'
	const OWNED = 'owned-folder'

	test.beforeEach(async ({ page, user, owner, ownerRequest, filesListPage }) => {
		await mkdir(ownerRequest, owner, `/${RESHARED}`)
		await createShare(ownerRequest, `/${RESHARED}`, user.userId, { permissions: WITHOUT_DELETE })
		await mkdir(page.request, user, `/${OWNED}`)
		await filesListPage.open()
	})

	test('drops the delete permission the resharer never received', async ({ filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, RESHARED)
		await sharingTab.pickRecipient('reshare@example.org', { external: true })
		await sharingTab.selectPermissionBundle('upload-edit')

		const share = await sharingTab.save()

		expect(share.permissions).toBe(WITHOUT_DELETE)
	})

	test('still grants everything on a folder the sharer owns', async ({ filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, OWNED)
		await sharingTab.pickRecipient('owned@example.org', { external: true })
		await sharingTab.selectPermissionBundle('upload-edit')

		const share = await sharingTab.save()

		expect(share.permissions).toBe(ALL_PERMISSIONS)
	})

	/**
	 * The atomic checkboxes read the share's own permissions, and a new share
	 * starts from the full set — so "Delete" used to be offered pre-checked on a
	 * reshare and the share was rejected without the user touching anything.
	 */
	test('does not offer delete in the custom permissions of a reshare', async ({ filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, RESHARED)
		await sharingTab.pickRecipient('custom-reshare@example.org', { external: true })
		await sharingTab.selectPermissionBundle('custom')

		await expect(sharingTab.checkbox('Delete')).not.toBeChecked()
		await expect(sharingTab.checkbox('Delete')).toBeDisabled()
	})

	test('still offers delete in the custom permissions of an owned folder', async ({ filesListPage, sharingTab }) => {
		await openSharingPanel(filesListPage, sharingTab, OWNED)
		await sharingTab.pickRecipient('custom-owned@example.org', { external: true })
		await sharingTab.selectPermissionBundle('custom')

		await expect(sharingTab.checkbox('Delete')).toBeChecked()
		await expect(sharingTab.checkbox('Delete')).toBeEnabled()
	})
})
