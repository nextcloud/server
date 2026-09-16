/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-versions-tab-page.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const FILE_NAME = 'creation.txt'

test.describe('files_versions: versions creation', () => {
	test.beforeEach(async ({ page, user, filesListPage, versionsTab }) => {
		await seedThreeVersions(page.request, user, `/${FILE_NAME}`)
		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
	})

	test('opens the versions panel and shows the three versions', async ({ versionsTab }) => {
		await expect(versionsTab.versions()).toHaveCount(3)
		await expect(versionsTab.version(0)).toContainText('Current version')
		await expect(versionsTab.version(2)).toContainText('Initial version')
	})

	test('shows yourself as the version author', async ({ versionsTab }) => {
		await expect(versionsTab.versions()).toHaveCount(3)
		await expect(versionsTab.authorName(0)).toContainText('You')
	})
})
