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

const FOLDER_NAME = 'share'
const FILE_NAME = 'file.txt'
const FILE_PATH = `${FOLDER_NAME}/${FILE_NAME}`

test.describe('files_versions: versions naming', () => {
	test('names the versions of an own file', async ({ page, user, filesListPage, versionsTab }) => {
		await mkdir(page.request, user, `/${FOLDER_NAME}`)
		await seedThreeVersions(page.request, user, FILE_PATH)

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		await versionsTab.nameVersion(2, 'v1')
		await expect(versionsTab.version(2)).toContainText('v1')
		await expect(versionsTab.version(2)).not.toContainText('Initial version')

		await versionsTab.nameVersion(1, 'v2')
		await expect(versionsTab.version(1)).toContainText('v2')

		await versionsTab.nameVersion(0, 'v3')
		await expect(versionsTab.version(0)).toContainText('v3 (Current version)')
	})

	test('names the versions of a shared file with edit permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId)
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => p.includes('W'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		await versionsTab.nameVersion(2, 'v1 - shared')
		await expect(versionsTab.version(2)).toContainText('v1 - shared')
		await expect(versionsTab.version(2)).not.toContainText('Initial version')

		await versionsTab.nameVersion(1, 'v2 - shared')
		await expect(versionsTab.version(1)).toContainText('v2 - shared')

		await versionsTab.nameVersion(0, 'v3 - shared')
		await expect(versionsTab.version(0)).toContainText('v3 - shared (Current version)')
	})

	test('cannot name versions of a shared file without edit permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId, { permissions: ALL_PERMISSIONS & ~SharePermission.UPDATE })
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => !p.includes('W'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		// Without edit permission the current version offers no actions menu, and
		// the older versions offer no label action
		await versionsTab.expectNoActionsMenu(0)
		await versionsTab.expectActionMissing(1, 'label')
		await versionsTab.expectActionMissing(2, 'label')
	})
})
