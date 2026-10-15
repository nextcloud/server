/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mergeTests } from '@playwright/test'
import { test as sharingTest } from '../../support/fixtures/files-sharing-page.ts'
import { expect, test as versionsTest } from '../../support/fixtures/files-versions-tab-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, waitForShare } from '../../support/utils/sharing.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const test = mergeTests(versionsTest, sharingTest)

const FOLDER_NAME = 'shared-folder'
const FILE_NAME = 'file.txt'

test.describe('files_versions: versions on shares', () => {
	test('shows the version author display name to the sharee', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab }) => {
		// The owner creates the versions, so the recipient must see the owner as author
		await mkdir(ownerRequest, owner, `/${FOLDER_NAME}`)
		await createShare(ownerRequest, `/${FOLDER_NAME}`, user.userId)
		await seedThreeVersions(ownerRequest, owner, `${FOLDER_NAME}/${FILE_NAME}`)
		await waitForShare(page.request, user, FOLDER_NAME, FILE_NAME)

		await filesListPage.open()
		await filesListPage.navigateToFolder(FOLDER_NAME)
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(3)
		await expect(versionsTab.authorName(0)).toContainText(owner.userId)
	})
})
