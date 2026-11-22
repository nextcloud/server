/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { FilesListPage } from '../../support/sections/FilesListPage.ts'
import type { SharingTab } from '../../support/sections/SharingTab.ts'

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
import { openSharingPanel, SharePermission } from '../../support/utils/sharing.ts'

const { READ, UPDATE, CREATE, DELETE, SHARE } = SharePermission

test.describe('files_sharing: "Allow editing" permission bundle', () => {
	test.beforeEach(async () => {
		await runOcc(['config:app:delete', 'files_sharing', 'shareapi_exclude_reshare_from_edit'])
	})

	test.afterAll(async () => {
		await runOcc(['config:app:delete', 'files_sharing', 'shareapi_exclude_reshare_from_edit'])
	})

	/**
	 * Share an entry with the recipient, picking the "Allow editing" bundle, and
	 * return the permissions the server stored.
	 *
	 * @param name - The entry to share
	 * @param context - The fixtures to drive
	 */
	async function shareWithEditingBundle(
		name: string,
		{ filesListPage, sharingTab, recipient }: {
			filesListPage: FilesListPage
			sharingTab: SharingTab
			recipient: User
		},
	): Promise<number> {
		await filesListPage.open()
		await openSharingPanel(filesListPage, sharingTab, name)
		await sharingTab.pickRecipient(recipient.userId)
		await sharingTab.selectPermissionBundle('upload-edit')

		const share = await sharingTab.save()
		return share.permissions
	}

	test.describe('by default resharing is part of editing', () => {
		test('grants a folder share everything including resharing', async ({ page, user, recipient, filesListPage, sharingTab }) => {
			await mkdir(page.request, user, '/folder-with-share')

			const permissions = await shareWithEditingBundle('folder-with-share', { filesListPage, sharingTab, recipient })

			expect(permissions).toBe(READ | UPDATE | CREATE | DELETE | SHARE)
		})

		test('grants a file share what a file can carry including resharing', async ({ page, user, recipient, filesListPage, sharingTab }) => {
			await uploadContent(page.request, user, 'content', 'text/plain', '/file-with-share.txt')

			const permissions = await shareWithEditingBundle('file-with-share.txt', { filesListPage, sharingTab, recipient })

			// A file share has neither CREATE nor DELETE
			expect(permissions).toBe(READ | UPDATE | SHARE)
		})
	})

	test.describe('with resharing excluded from editing', () => {
		test.beforeEach(async () => {
			await runOcc(['config:app:set', '--value', 'yes', 'files_sharing', 'shareapi_exclude_reshare_from_edit'])
		})

		test('grants a folder share everything but resharing', async ({ page, user, recipient, filesListPage, sharingTab }) => {
			await mkdir(page.request, user, '/folder-no-share')

			const permissions = await shareWithEditingBundle('folder-no-share', { filesListPage, sharingTab, recipient })

			expect(permissions).toBe(READ | UPDATE | CREATE | DELETE)
		})

		test('grants a file share only read and update', async ({ page, user, recipient, filesListPage, sharingTab }) => {
			await uploadContent(page.request, user, 'content', 'text/plain', '/file-no-share.txt')

			const permissions = await shareWithEditingBundle('file-no-share.txt', { filesListPage, sharingTab, recipient })

			expect(permissions).toBe(READ | UPDATE)
		})
	})
})
