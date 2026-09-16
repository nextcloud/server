/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mergeTests } from '@playwright/test'
import { test as sharingTest } from '../../support/fixtures/files-sharing-page.ts'
import { expect, test as versionsTest } from '../../support/fixtures/files-versions-tab-page.ts'
import { createShare, DOWNLOAD_DISABLED_ATTRIBUTE, waitForShare } from '../../support/utils/sharing.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const test = mergeTests(versionsTest, sharingTest)

const FILE_NAME = 'download.txt'

test.describe('files_versions: versions download', () => {
	test('downloads versions of an own file and asserts their content', async ({ page, user, filesListPage, versionsTab }) => {
		await seedThreeVersions(page.request, user, `/${FILE_NAME}`)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		expect(await versionsTab.getVersionContent(0)).toBe('v3')
		expect(await versionsTab.getVersionContent(1)).toBe('v2')
		expect(await versionsTab.getVersionContent(2)).toBe('v1')
	})

	test('downloads versions of a shared file with download permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await seedThreeVersions(ownerRequest, owner, `/${FILE_NAME}`)
		await createShare(ownerRequest, `/${FILE_NAME}`, user.userId)
		await waitForShare(page.request, user, '', FILE_NAME)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		expect(await versionsTab.getVersionContent(0)).toBe('v3')
		expect(await versionsTab.getVersionContent(1)).toBe('v2')
		expect(await versionsTab.getVersionContent(2)).toBe('v1')
	})

	test('does not offer download of a shared file without download permission', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		await seedThreeVersions(ownerRequest, owner, `/${FILE_NAME}`)
		await createShare(ownerRequest, `/${FILE_NAME}`, user.userId, { attributes: DOWNLOAD_DISABLED_ATTRIBUTE })
		await waitForShare(page.request, user, '', FILE_NAME)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		// The current version's only possible actions (label, download) are both
		// unavailable here, so it offers no actions menu at all
		await versionsTab.expectNoActionsMenu(0)
		await versionsTab.expectActionMissing(1, 'download')
		await versionsTab.expectActionMissing(2, 'download')
	})
})
