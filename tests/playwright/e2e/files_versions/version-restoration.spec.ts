/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VersionsTab } from '../../support/sections/VersionsTab.ts'

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

/**
 * Assert the versions list after restoring the initial version ("v1"): the
 * restored content becomes the current version, the previous current ("v3") and
 * "v2" follow.
 *
 * @param versionsTab - The versions tab page object
 */
async function expectRestoredToInitial(versionsTab: VersionsTab): Promise<void> {
	await expect(versionsTab.versions()).toHaveCount(3)
	await expect(versionsTab.version(0)).toContainText('Current version')
	await expect(versionsTab.version(2)).not.toContainText('Initial version')

	expect(await versionsTab.getVersionContent(0)).toBe('v1')
	expect(await versionsTab.getVersionContent(1)).toBe('v3')
	expect(await versionsTab.getVersionContent(2)).toBe('v2')
}

test.describe('files_versions: versions restoration', () => {
	test('restores the initial version of an own file', async ({ page, user, filesListPage, versionsTab }) => {
		await mkdir(page.request, user, `/${FOLDER_NAME}`)
		await seedThreeVersions(page.request, user, FILE_PATH)

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		// The current version cannot be restored onto itself
		await versionsTab.expectActionMissing(0, 'restore')
		await versionsTab.restore(2)

		await expectRestoredToInitial(versionsTab)
	})

	test('restores versions of a shared file with update permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId)
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => p.includes('W'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		await versionsTab.restore(2)

		await expectRestoredToInitial(versionsTab)
	})

	test('cannot restore versions of a shared file without update permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await seedThreeVersions(ownerRequest, owner, FILE_PATH)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId, { permissions: ALL_PERMISSIONS & ~SharePermission.UPDATE })
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME, (p) => !p.includes('W'))

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		// Without update permission the current version offers no actions menu, and
		// the older versions offer no restore action
		await versionsTab.expectNoActionsMenu(0)
		await versionsTab.expectActionMissing(1, 'restore')
		await versionsTab.expectActionMissing(2, 'restore')
	})
})
