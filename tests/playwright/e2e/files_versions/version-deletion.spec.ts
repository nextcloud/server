/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mergeTests } from '@playwright/test'
import { test as sharingTest } from '../../support/fixtures/files-sharing-page.ts'
import { expect, test as versionsTest } from '../../support/fixtures/files-versions-tab-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { ALL_PERMISSIONS, createShare, SharePermission, waitForShare } from '../../support/utils/sharing.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const test = mergeTests(versionsTest, sharingTest)

const FOLDER_NAME = 'shared_folder'
const FILE_NAME = 'file.txt'
const FILE_PATH = `/${FOLDER_NAME}/${FILE_NAME}`

test.describe('files_versions: versions deletion', () => {
	test('deletes the initial version of an own file', async ({ page, user, filesListPage, versionsTab }) => {
		await mkdir(page.request, user, `/${FOLDER_NAME}`)
		await seedThreeVersions(page.request, user, FILE_PATH)

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(3)
		// The initial version is the oldest (last) entry
		await versionsTab.delete(2)
		await expect(versionsTab.versions()).toHaveCount(2)
	})

	test('deletes versions of a shared file with delete permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId)
		// Wait for the delete permission (D) to reach the recipient's listing
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => p.includes('D'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(3)
		await versionsTab.delete(2)
		await expect(versionsTab.versions()).toHaveCount(2)
	})

	test('cannot delete versions of a shared file without delete permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId, { permissions: ALL_PERMISSIONS & ~SharePermission.DELETE })
		// Wait for the delete restriction (no D) to reach the recipient's listing
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => !p.includes('D'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(3)
		await versionsTab.expectActionMissing(0, 'delete')
		await versionsTab.expectActionMissing(1, 'delete')
		await versionsTab.expectActionMissing(2, 'delete')
	})
})
